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
  private $_doi = '';
  private $_volume = '';
  private $_issue = '';
  private $_fpage = '';
  private $_lpage = '';
  private $_enTitle = '';
  private $_category = '';
  private $_journalId  = '';
  private $_issn  = '';
  private $_publisher  = '';
  private $_abbreviatedTitle  = '';
  // TODO: Make
  private $_license = '';





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

    $context = $this->_request->getContext(); // Journal context
    $this->_journalId = $context->getLocalizedSetting('acronym');
    $this->_issn = $context->getSetting('printIssn');
    $this->_publisher = $context->getSetting('publisherInstitution');
    $this->_abbreviatedTitle = $context->getLocalizedSetting('abbreviation');

    $this->extractContent();
  }
  private function extractContent()
  {
    $articleContent = array();
    foreach (self::$xpath->evaluate("/article/front/article-meta/kwd-group") as $kwdGroupNode) {
      $kwGroupFound = new KeywordGroup($kwdGroupNode, self::$xpath);
      ChromePhp::log('title FOund and saved');
      ChromePhp::log($kwGroupFound->getTitle());
      ChromePhp::log($this->_pdfDocument->getAliasNumPage());
      $articleContent[] = $kwGroupFound;
    }
    $this->keywords = $articleContent;

    foreach (self::$xpath->evaluate("/article/front/article-meta/title-group/article-title") as $node) {
      $this->_title = $node->nodeValue;
    }

    foreach (self::$xpath->evaluate("/article/front/article-meta/article-categories/subj-group/subject") as $node) {
      $this->_category = $node->nodeValue;
    }

    foreach (self::$xpath->evaluate("/article/front/article-meta/title-group/trans-title-group/trans-title") as $node) {
      $this->_enTitle = $node->nodeValue;
    }


    foreach (self::$xpath->evaluate("//article-id") as $node) {
      $this->_doi = $node->nodeValue;
    }

    foreach (self::$xpath->evaluate("//volume") as $key => $node) {
      if ($key == 0) {
        $this->_volume = $node->nodeValue;
      }
    }

    foreach (self::$xpath->evaluate("//issue") as $key => $node) {
      if ($key == 0) {
        $this->_issue = $node->nodeValue;
      }
    }

    foreach (self::$xpath->evaluate("//fpage") as $key => $node) {
      if ($key == 0) {
        $this->_fpage = $node->nodeValue;
      }
    }

    foreach (self::$xpath->evaluate("//lpage") as $key => $node) {
      if ($key == 0) {
        $this->_lpage = $node->nodeValue;
      }
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
    // TODO: Lograr que esto funcione, ahorita no hace nada 

    // HTML preparation
    $context = $this->_request->getContext(); /* @var $context Journal */

    //$this->imageUrlReplacement($xmlGalley, $xpath);
    //$this->ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request);

    // extends TCPDF object
    $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
    $userGroups = $userGroupDao->getByContextId($context->getId())->toArray();

    $articleDataString = $this->_getArticleDataString($this->_publication, $this->_request, $this->_localeKey);
    // $pdfHeaderLogo = $this->_getHeaderLogo($this->_request);
    $pdfHeaderLogo = $this->_pluginPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logoUcr.png';
    $this->_pdfDocument->SetCreator(PDF_CREATOR);
    $journal = $this->_request->getContext();

    $this->_pdfDocument->setPrintHeader(false);
    $this->_setTitle($this->_pdfDocument);
    $this->_pdfDocument->SetAuthor($this->_publication->getAuthorString($userGroups));
    $this->_pdfDocument->SetSubject($this->_publication->getLocalizedData('subject', $this->_localeKey));

    $this->_pdfDocument->SetHeaderData($pdfHeaderLogo, 20, $this->_title, $articleDataString);
    $this->_setFundamentalVisualizationParamters($this->_pdfDocument);
    $this->_pdfDocument->setPageFormat('LETTER', "P"); // Recibe el formato y la orientación del documento como parámetros.

    $this->_pdfDocument->AddPage();
    $this->_createFrontPage();

    $this->_createTitleSection();
    $this->_pdfDocument->setPrintHeader(true);
    $this->_createAuthorsSection();
    $this->_createAbstractSection();
    $this->_pdfDocument->AddPage();
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
    // TODO Cambiar la variable de _abbreviatedTitle porque lo que va es el journal name
    $footer = '<b>License (open-acces) •</b> ' . $this->_abbreviatedTitle . ' <b>•</b> ' . $this->_publisher . ' <b>• Volume: </b>' . $this->_volume . ' <b>• Issue: </b>' . $this->_issue . '<b>•</b> <b>ISSN (print): </b>' . $this->_issn . ' <b>• Pages</b> ';
    // TODO: Estos parámetros permiten modificar aspectos fundamentales del pdf, como los margenes, fuentes o el ratio de escalado de las imágenes
    // Los parámetros pueden ser modifcados en las constantes definidas en el archivo tcpdf_autoconfig  
    $pdfDocument->setHeaderFont(array('times', '', 10));
    $pdfDocument->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdfDocument->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdfDocument->SetMargins(PDF_MARGIN_LEFT, 31.75, PDF_MARGIN_RIGHT);
    $pdfDocument->SetHeaderMargin(25);
    $pdfDocument->SetFooterMargin(-15.23492);
    $pdfDocument->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdfDocument->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdfDocument->setFooterHtml($footer);
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
    // $header_x = $this->_pdfDocument->original_lMargin;
    // $this->_pdfDocument->SetY();
    // $this->_pdfDocument->SetX($header_x);

    $context = $this->_request->getContext(); // Journal context

    $logoUcr = $this->_pluginPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logoUcr.png';
    $logoRevista = $this->_pluginPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logoRevistaMaderYBosques.png';

    $this->_pdfDocument->Image($logoUcr, 25.4, 3, 40);
    // TODO Que Jorge evalue si le parece porque no se ve bien dos logos juntos
    // $this->_pdfDocument->Image($logoRevista, 90, 5, 60);

    // header title
    $journalName = $context->getLocalizedSetting('name');

    $this->_pdfDocument->SetY(26);
    $this->_pdfDocument->SetX(25.4);

    $this->_pdfDocument->SetFont('times', '', 19);
    $this->_pdfDocument->MultiCell(0, 0, $journalName, 0, '', 0, 1, '', '', true, 0, false, true, 0, 'M', false);

    $this->_pdfDocument->SetY(35.56);
    $this->_pdfDocument->SetX(25.4);
    $this->_pdfDocument->Cell(0, 0, '', 'T', 0, 'C');

    $this->_pdfDocument->Ln(9);
    ChromePhp::log($context);
    $this->_pdfDocument->SetFillColor(255, 255, 255); //rgb
    $this->_pdfDocument->SetFont('times', 'B', 15);
    $this->_pdfDocument->setCellHeightRatio(1.2);
    $this->_pdfDocument->MultiCell('', '', 'Journal Information', 0, 'R', 1, 1, '', '', true);
    $this->_printPairInfo('Journal ID (publisher-id):', $context->getLocalizedSetting('acronym')); //Localized es para objetos
    $this->_printPairInfo('Abbreviated Title:', $context->getLocalizedSetting('abbreviation'));
    $this->_printPairInfo('ISSN (print):', $context->getSetting('printIssn')); // setting normal es para strings
    $this->_printPairInfo('Publisher:', $context->getSetting('publisherInstitution'));

    $this->_pdfDocument->SetFont('times', 'B', 15);
    $this->_pdfDocument->Ln(1);
    $this->_pdfDocument->MultiCell('', '', 'Article/Issue Information', 0, 'R', 1, 1, '', '', true);
    $this->_printPairInfo('Volume:', $this->_volume);
    $this->_printPairInfo('Issue:', $this->_issue);
    $this->_printPairInfo('Pages:', "$this->_fpage - $this->_lpage");
    $this->_printPairInfo('DOI:', $this->_doi);
    // $this->_printPairInfo('Funded by:', 'DGAPA-UNAM');
    // $this->_printPairInfw('Award ID:', '203316');

    $this->_pdfDocument->Ln(9);
    // $title = $this->_publication->getLocalizedFullTitle($this->_localeKey);
    $this->_pdfDocument->SetFont('times', 'B', 21);
    $this->_pdfDocument->MultiCell('', '', $this->_title, 0, 'C', 1, 1, '', '', true);
    $this->_pdfDocument->Ln(10);
    $this->_pdfDocument->SetFont('times', 'B', 12);
    $this->_pdfDocument->MultiCell('', '', 'Translated Title (en)', 0, 'C', 1, 1, '', '', true);
    $this->_pdfDocument->SetFont('times', 'B', 21);
    $this->_pdfDocument->MultiCell('', '', $this->_enTitle, 0, 'C', 1, 1, '', '', true);


    $this->_pdfDocument->Ln(9);
    $this->_pdfDocument->SetFont('times', 'B', 14);
    $this->_pdfDocument->MultiCell('', '', 'Categorías', 0, 'R', 1, 1, '', '', true);
    $this->_pdfDocument->SetFont('times', '', 9);
    $textToWrite = '<b>' . 'Tipo: ' . ' </b>' . $this->_category;
    $this->_pdfDocument->writeHTML($textToWrite, true, false, false, false, 'R');
    $this->_pdfDocument->Ln(10);

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
      $this->_pdfDocument->setFont('times', 'B', 11);
      $this->_pdfDocument->MultiCell('', '', 'Abstract', 0, 'L', 1, 1, '', '', true);
      $this->_pdfDocument->setCellPaddings(5, 5, 5, 5);
      $this->_pdfDocument->SetFillColor(255, 255, 255); // Color de fondo del abstract
      $this->_pdfDocument->SetFont('times', '', 9);
      $this->_pdfDocument->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 255, 255)));  // Tipo de linea divisoria y color
      $this->_pdfDocument->writeHTMLCell('', '', '', '', $abstract, 'B', 1, 1, true, 'J', true);
      $this->_pdfDocument->Ln(4);
    }
  }

  private function _createAuthorsSection(): void
  {
    $this->_pdfDocument->AddPage();
    $authors = $this->_publication->getData('authors');
    $counter = 1;
    if (count($authors) > 0) {
      /* @var $author Author */
      // En este ciclo se itera en la lista de autores del documento, acá se puden modificar ciertos estilos.
      $this->_pdfDocument->SetFont('times', '', 16);
      foreach ($authors as $author) {
        $authorName = htmlspecialchars($author->getGivenName($this->_localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($this->_localeKey));
        $affiliation = htmlspecialchars($author->getAffiliation($this->_localeKey));
        $authorName = $authorName . ' ' . $counter;
        // Writing affiliations into cells
        // $this->_pdfDocument->MultiCell($authorLineWidth, 0, $authorName, 0, 'L', 1, 0, 19, '', true, 0, false, true, 0, "T", true);
        $this->_pdfDocument->MultiCell('', '', $authorName, 0, 'R', 1, 1, '', '', true);
        $counter++;
      }
      $this->_pdfDocument->Ln(6);
      // $this->_pdfDocument->AddPage();
    }

    $counter = 1;
    if (count($authors) > 0) {
      /* @var $author Author */
      // En este ciclo se itera en la lista de autores del documento, acá se puden modificar ciertos estilos.
      $this->_pdfDocument->SetFont('times', '', 8);
      foreach ($authors as $author) {
        $affiliation = htmlspecialchars($author->getAffiliation($this->_localeKey));
        // $this->_pdfDocument->MultiCell($authorLineWidth, 0, $authorName, 0, 'L', 1, 0, 19, '', true, 0, false, true, 0, "T", true);
        $affiliation = $counter . ' ' . $affiliation;
        $this->_pdfDocument->MultiCell('', '', $affiliation, 0, 'J', 1, 1, '', '', true);
        $counter++;
      }
      $this->_pdfDocument->Ln(6);
      // $this->_pdfDocument->AddPage();
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
