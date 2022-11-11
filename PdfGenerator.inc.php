<?php

use JATSParser\Back\Journal;
use JATSParser\Body\Figure;
use JATSParser\Body\KeywordGroup;
use JATSParser\PDF\TCPDFDocument;
use JATSParser\Body\Section;

import('plugins.generic.jatsParser.ChromePhp');
import('plugins.generic.jatsParser.KeywordGroup');

// import('plugins.generic.jatsParser.KeywordGroup');
// import('plugins.generic.jatsParser.PdfGenerator');
/**
 * This class is in charge of the pdf making
 * Uses TCPDF library
 */
class PdfGenerator
{
  private string $_htmlString;
  private Publication $_publication;
  private Request $_request;
  private string $_localeKey;
  private string $_pluginPath;
  private TCPDFDocument $_pdfDocument;


  /* @var $document \DOMDocument */
  private $document;

  /* @var $xpath \DOMXPath */
  private static $xpath;

  /* var $articleSections array */
  private $keywords = array();
  private $_title = '';


  public function __construct(string $htmlString, Publication $publication, Request $request, string $localeKey, string $pluginPath, $submissionPluginPath)
  {

    $this->_htmlString = $htmlString;
    $this->_publication = $publication;
    $this->_request = $request;
    $this->_localeKey = $localeKey;
    $this->_pluginPath = $pluginPath;
    $this->_pdfDocument = new TCPDFDocument();
    ChromePhp::log('Reciviendo path del xml');
    ChromePhp::log($submissionPluginPath);
    $document = new \DOMDocument;
    $this->document = $document->load($submissionPluginPath);
    self::$xpath = new \DOMXPath($document);


    $this->extractContent();
  }
  private function extractContent()
  {
    $articleContent = array();
    foreach (self::$xpath->evaluate("/article/front/article-meta/kwd-group") as $kwdGroupNode) {
      $kwGroupFound = new KeywordGroup($kwdGroupNode, self::$xpath);
      ChromePhp::log('title FOund and saved');
      ChromePhp::log($kwGroupFound->getTitle());
      ChromePhp::log($kwGroupFound->getContent());
      $articleContent[] = $kwGroupFound;
    }
    $this->keywords = $articleContent;
    foreach (self::$xpath->evaluate("//article-title") as $node) {
      $this->_title = $node->nodeValue;
    }
  }

  public function createPdf(): string
  {
    $data = file_get_contents($this->_pluginPath . DIRECTORY_SEPARATOR . "pdfStyleTemplates" . DIRECTORY_SEPARATOR . "prueba.json");
    $prueba = json_decode($data, true);
    //TODO agregar journal como atrbuto de clase

    // $article =& $record->getData('article');
    // $journal =& $record->getData('journal');
    // $section =& $record->getData('section');
    // $issue =& $record->getData('issue');
    // $galleys =& $record->getData('galleys');
    // $articleId = $article->getId();
    // $publication = $article->getCurrentPublication();

    // $request = Application::get()->getRequest();

    // $abbreviation = $journal->getLocalizedSetting('abbreviation');
    // $printIssn = $journal->getSetting('printIssn');
    // $onlineIssn = $journal->getSetting('onlineIssn');
    // $articleLocale = $article->getLocale();


    // ChromePhp::log($issue);
    // ChromePhp::log($prueba);


    // HTML preparation
    $context = $this->_request->getContext(); /* @var $context Journal */

    //$this->imageUrlReplacement($xmlGalley, $xpath);
    //$this->ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request);

    // extends TCPDF object
    $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
    $userGroups = $userGroupDao->getByContextId($context->getId())->toArray();

    $articleDataString = $this->_getArticleDataString($this->_publication, $this->_request, $this->_localeKey);
    $pdfHeaderLogo = $this->_getHeaderLogo($this->_request);
    $this->_pdfDocument->SetCreator(PDF_CREATOR);
    $journal = $this->_request->getContext();

    $this->_setTitle($this->_pdfDocument);
    $this->_pdfDocument->SetAuthor($this->_publication->getAuthorString($userGroups));
    $this->_pdfDocument->SetSubject($this->_publication->getLocalizedData('subject', $this->_localeKey));
    $this->_pdfDocument->SetHeaderData($pdfHeaderLogo, PDF_HEADER_LOGO_WIDTH, $journal->getName($this->_localeKey), $articleDataString);
    $this->_setFundamentalVisualizationParamters($this->_pdfDocument);
    $this->_pdfDocument->setPageFormat('A4', "P"); // Recibe el formato y la orientación del documento como parámetros.

    $this->_pdfDocument->AddPage();

    $this->_createFrontPage();

    $this->_createTitleSection();
    $this->_createAuthorsSection();
    $this->_createAbstractSection();
    $this->_createTextSection();

    return $this->_pdfDocument->Output('article.pdf', 'S');
  }

  private function _createKeywordsSection()
  {
    $keywordIndex = 1;
    $keywordPrintString = '';
    foreach ($this->keywords as $key => $keywordGroup) {
      $this->_pdfDocument->setFont('times', '', 21);
      $this->_pdfDocument->MultiCell('', '', $keywordGroup->getTitle(), 0, 'C', 1, 1, '', '', true);
      $this->_pdfDocument->setFont('times', '', 12);
      foreach ($keywordGroup->getContent() as $key => $keyword) {
        if ($keywordIndex % 3 == 0) {
          $keywordPrintString = $keywordPrintString . '<br>';
          // $keywordPrintString = $keywordPrintString . '|';
        } else {
          $keywordPrintString = $keywordPrintString . $keyword . ' ';
        }
        $keywordIndex++;
      }
      $this->_pdfDocument->writeHTML($keywordPrintString, true, false, false, false, 'C');
      $keywordPrintString = '';
    }
  }

  private function _setTitle(TCPDFDocument $pdfDocument): void
  {
    $pdfDocument->setTitle($this->_publication->getLocalizedFullTitle($this->_localeKey));
  }

  private function _setFundamentalVisualizationParamters(TCPDFDocument $pdfDocument): void
  {
    // TODO: Estos parámetros permiten modificar aspectos fundamentales del pdf, como los margenes, fuentes o el ratio de escalado de las imágenes
    // Los parámetros pueden ser modifcados en las constantes definidas en el archivo tcpdf_autoconfig  
    $pdfDocument->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdfDocument->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdfDocument->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdfDocument->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdfDocument->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdfDocument->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdfDocument->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdfDocument->setImageScale(PDF_IMAGE_SCALE_RATIO);
  }

  private function _getHeaderLogo(Request $request): string
  {
    $journal = $request->getContext();
    $thumb = $journal->getLocalizedData('journalThumbnail');
    if (!empty($thumb)) {
      $journalFilesPath = __DIR__ . '/../../../' . Config::getVar('files', 'public_files_dir') . '/journals/' . $journal->getId() . '/'; // TCPDF accepts only relative path
      $pdfHeaderLogoLocation = $journalFilesPath . $thumb['uploadName'];
    } else {
      $pdfHeaderLogoLocation = __DIR__ . "/JATSParser/logo/logo.jpg";
    }
    return $pdfHeaderLogoLocation;
  }

  private function _printPairInfo(string $name, string $info)
  {
    $this->_pdfDocument->SetFont('times', '', 7);
    $a = '<b>' . $name . ' </b>' . $info;
    $this->_pdfDocument->writeHTML($a, true, false, false, false, 'R');
  }

  private function _createFrontPage(): void
  {
    $context = $this->_request->getContext(); // Journal context
    ChromePhp::log($context);
    $this->_pdfDocument->SetFillColor(255, 255, 255); //rgb
    $this->_pdfDocument->SetFont('times', 'B', 15);
    $this->_pdfDocument->setCellHeightRatio(1.2);
    $this->_pdfDocument->MultiCell('', '', 'Journal Informationn', 0, 'R', 1, 1, '', '', true);
    $this->_printPairInfo('Journal ID (publisher-id):', $context->getLocalizedSetting('acronym')); //Localized es para objetos
    $this->_printPairInfo('Abbreviated Title:', $context->getLocalizedSetting('abbreviation'));
    $this->_printPairInfo('ISSN (print):', $context->getSetting('printIssn')); // setting normal es para strings
    $this->_printPairInfo('Publisher:', $context->getSetting('publisherInstitution'));

    $this->_pdfDocument->SetFont('times', 'B', 15);
    $this->_pdfDocument->Ln(1);
    $this->_pdfDocument->MultiCell('', '', 'Article/Issue Information', 0, 'R', 1, 1, '', '', true);
    $this->_printPairInfo('Volume:', '23');
    $this->_printPairInfo('Issue:', '3');
    $this->_printPairInfo('Pages:', '07-14');
    $this->_printPairInfo('DOI:', '10.21829/myb.2017.2331418  ');
    $this->_printPairInfo('Funded by:', 'DGAPA-UNAM');
    $this->_printPairInfo('Award ID:', '203316');

    $this->_pdfDocument->Ln(4);
    // $title = $this->_publication->getLocalizedFullTitle($this->_localeKey);
    $this->_pdfDocument->SetFont('times', 'B', 21);
    $this->_pdfDocument->MultiCell('', '', $this->_title, 0, 'C', 1, 1, '', '', true);
    $this->_pdfDocument->Ln(8);

    $this->_createKeywordsSection();
  }

  private function _createTitleSection(): void
  {
    $this->_pdfDocument->SetFillColor(255, 255, 255); //rgb
    $this->_pdfDocument->SetFont('times', 'B', 10);
    // Con el quinto parámetro se puede cambiar la alineación del título, L = left , R = right, C = Center, J = Justify
    $this->_pdfDocument->Ln(6);
    // $this->_pdfDocument->MultiCell('', '', $this->_publication->getLocalizedFullTitle($this->_localeKey), 0, 'L', 1, 1, '', '', true);
  }

  private function _createAbstractSection(): void
  {
    // TODO: En esta seccion se puede modificar el estilo del abstract
    if ($abstract = $this->_publication->getLocalizedData('abstract', $this->_localeKey)) {
      $this->_pdfDocument->setCellPaddings(5, 5, 5, 5);
      $this->_pdfDocument->SetFillColor(255, 255, 255); // Color de fondo del abstract
      $this->_pdfDocument->SetFont('times', '', 11);
      $this->_pdfDocument->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 255, 255)));  // Tipo de linea divisoria y color
      $this->_pdfDocument->writeHTMLCell('', '', '', '', $abstract, 'B', 1, 1, true, 'J', true);
      $this->_pdfDocument->Ln(4);
    }
  }

  private function _createAuthorsSection(): void
  {
    $authors = $this->_publication->getData('authors');
    if (count($authors) > 0) {
      /* @var $author Author */
      // En este ciclo se itera en la lista de autores del documento, acá se puden modificar ciertos estilos.
      foreach ($authors as $author) {
        $this->_pdfDocument->SetFont('times', 'I', 10);

        // Calculating the line height for author name and affiliation
        $authorName = htmlspecialchars($author->getGivenName($this->_localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($this->_localeKey));
        $affiliation = htmlspecialchars($author->getAffiliation($this->_localeKey));

        $authorLineWidth = 60;
        $authorNameStringHeight = $this->_pdfDocument->getStringHeight($authorLineWidth, $authorName);

        $affiliationLineWidth = 110;
        $afilliationStringHeight = $this->_pdfDocument->getStringHeight(110, $affiliation);

        $authorNameStringHeight > $afilliationStringHeight ? $cellHeight = $authorNameStringHeight : $cellHeight = $afilliationStringHeight;

        // Writing affiliations into cells
        $this->_pdfDocument->MultiCell($authorLineWidth, 0, $authorName, 0, 'L', 1, 0, 19, '', true, 0, false, true, 0, "T", true);
        $this->_pdfDocument->SetFont('times', '', 10);
        $this->_pdfDocument->MultiCell($affiliationLineWidth, $cellHeight, $affiliation, 0, 'L', 1, 1, '', '', true, 0, false, true, 0, "T", true);
      }
      $this->_pdfDocument->Ln(6);
    }
  }

  private function _createTextSection(): void
  {
    // Text (goes from JATSParser
    $this->_pdfDocument->setCellPaddings(0, 0, 0, 0);
    $this->_pdfDocument->SetFont('times', '', 12);
    // $this->_pdfDocument->setCellHeightRatio(1.5);

    $this->_htmlString .= "\n" . '<style>' . "\n" . file_get_contents($this->_pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
    $htmlString = $this->_prepareForPdfGalley($this->_htmlString);
    //  TODO: En el ultimo parametro es donde se escoge la alineacion del texto
    // Se puede escoger entre: R, L, C, J   ||  R = Right, L = Left, C = Center, J = Justified
    $this->_pdfDocument->writeHTML($htmlString, true, false, true, false, 'J');
  }

  private function _getArticleDataString(Publication $publication, Request $request, string $localeKey): string
  {
    //TODO Probar esto en la compu de charlie
    $articleDataString = '';
    //TODO agregar journal como atrbuto de clase
    $context = $request->getContext(); /* @var $context Journal */
    $submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
    $issueDao = DAORegistry::getDAO('IssueDAO');
    $issue = $issueDao->getBySubmissionId($submission->getId(), $context->getId());

    if ($issue && $issueIdentification = $issue->getIssueIdentification()) {
      $articleDataString .= $issueIdentification;
    }
    if ($pages = $publication->getLocalizedData('subject', $localeKey)) {
      $articleDataString .= ", " . $pages;
    }
    if ($doi = $publication->getData('pub-id::doi')) {
      $articleDataString .= "\n" . __('plugins.pubIds.doi.readerDisplayName', null, $localeKey) . ': ' . $doi;
    }

    $printIssn = $context->getSetting('printIssn');
    ChromePhp::log($printIssn);

    return $articleDataString;
  }


  /**
   * @param string $htmlString
   * @return string Preprocessed HTML string for TCPDF
   */
  private function _prepareForPdfGalley(string $htmlString): string

  {

    $dom = new DOMDocument('1.0', 'utf-8');
    $htmlHead = "\n";
    $htmlHead .= '<head>';
    $htmlHead .= "\t" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
    $htmlHead .= "\n";
    $htmlHead .= '</head>';
    $dom->loadHTML($htmlHead . $htmlString);

    // set style for figures and table
    $xpath = new \DOMXPath($dom);

    $tableNodes = $xpath->evaluate('//table');
    foreach ($tableNodes as $tableNode) {
      $tableNode->setAttribute('border', '1');
      $tableNode->setAttribute('cellpadding', '2');
    }

    $captionNodes = $xpath->evaluate('//figure/p[@class="caption"]|//table/caption');
    foreach ($captionNodes as $captionNode) {
      $captionParts = $xpath->evaluate('span[@class="label"]|span[@class="title"]', $captionNode);
      foreach ($captionParts as $captionPart) {
        $emptyTextNode = $dom->createTextNode(' ');
        $captionPart->appendChild($emptyTextNode);
      }
    }

    // TCPDF doesn't recognize display property, insert div
    $tableCaptions = $xpath->evaluate('//table/caption');
    foreach ($tableCaptions as $tableCaption) {
      /* @var $tableNode \DOMNode */
      $tableNode = $tableCaption->parentNode;
      $divNode = $dom->createElement('div');
      $divNode->setAttribute('class', 'caption');
      $nextToTableNode = $tableNode->nextSibling;
      if ($nextToTableNode) {
        $tableNode->parentNode->insertBefore($divNode, $nextToTableNode);
      }
      $divNode->appendChild($tableCaption);
    }

    // Remove redundant whitespaces before caption label
    $modifiedHtmlString = $dom->saveHTML();
    $modifiedHtmlString = preg_replace('/<caption>\s*/', '<br>' . '<caption>', $modifiedHtmlString);
    $modifiedHtmlString = preg_replace('/<p class="caption">\s*/', '<p class="caption">', $modifiedHtmlString);

    return $modifiedHtmlString;
  }
}
