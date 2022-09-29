<?php

use JATSParser\PDF\TCPDFDocument;

import('plugins.generic.jatsParser.ChromePhp');
/**
 * This class is in charge of the pdf making
 * Uses TCPDF library
 */
class PdfGenerator
{
	/**
	 * @param $article Submission
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issue Issue
	 * @param
	 */
	public function createPdf(string $htmlString, Publication $publication, Request $request, string $localeKey, string $pluginPath): string
	{
		// HTML preparation
		$context = $request->getContext(); /* @var $context Journal */
		$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getBySubmissionId($submission->getId(), $context->getId());

		//$this->imageUrlReplacement($xmlGalley, $xpath);
		//$this->ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request);

		ChromePhp::log('asd');
		// extends TCPDF object
		$pdfDocument = new TCPDFDocument();

		$pdfDocument->setTitle($publication->getLocalizedFullTitle($localeKey));

		// get the logo
		$journal = $request->getContext();
		$thumb = $journal->getLocalizedData('journalThumbnail');
		if (!empty($thumb)) {
			$journalFilesPath = __DIR__ . '/../../../' . Config::getVar('files', 'public_files_dir') . '/journals/' . $journal->getId() . '/'; // TCPDF accepts only relative path
			$pdfHeaderLogo = $journalFilesPath . $thumb['uploadName'];
		} else {
			$pdfHeaderLogo = __DIR__ . "/JATSParser/logo/logo.jpg";
		}

		$pdfDocument->SetCreator(PDF_CREATOR);
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$userGroups = $userGroupDao->getByContextId($context->getId())->toArray();
		$pdfDocument->SetAuthor($publication->getAuthorString($userGroups));
		$pdfDocument->SetSubject($publication->getLocalizedData('subject', $localeKey));

		$articleDataString = '';

		if ($issue && $issueIdentification = $issue->getIssueIdentification()) {
			$articleDataString .= $issueIdentification;
		}

		if ($pages = $publication->getLocalizedData('subject', $localeKey)) {
			$articleDataString .= ", " . $pages;
		}

		if ($doi = $publication->getData('pub-id::doi')) {
			$articleDataString .= "\n" . __('plugins.pubIds.doi.readerDisplayName', null, $localeKey) . ': ' . $doi;
		}

		$pdfDocument->SetHeaderData($pdfHeaderLogo, PDF_HEADER_LOGO_WIDTH, $journal->getName($localeKey), $articleDataString);

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

		$pdfDocument->AddPage();

		// Article title
		// Esta función modifica el color de fondo de la sección del título y de la sección de autores 
		$pdfDocument->SetFillColor(255, 255, 255);

		$pdfDocument->SetFont('times', 'B', 10); // Esta función permite cambiar la fuente.
		/* El primer parámetro es la fuente a utilizar, por defecto se pueden elgir las siguientes opciones:
			times (Times-Roman), timesb (Times-Bold), timesi (Times-Italic), timesbi (Times-BoldItalic), 
			helvetica (Helvetica), helveticab (Helvetica-Bold),helveticai (Helvetica-Oblique), 
			helveticabi (Helvetica-BoldOblique), courier (Courier), courierb (Courier-Bold), courieri (Courier-Oblique),
			courierbi (Courier-BoldOblique), symbol (Symbol), zapfdingbats (ZapfDingbats)
			El segundo parámetro es el estilo, se puede elegir entre las siguientes opciones: 
			empty string: regular, B: bold, I: italic, U: underline, D: line trough, O: overline
			El tercer parámetro es el tamaño de la fuente.
		*/

		// Con el quinto parámetro se puede cambiar la alineación del título, L = left , R = right, C = Center, J = Justify
		$pdfDocument->MultiCell('', '', $publication->getLocalizedFullTitle($localeKey), 0, 'L', 1, 1, '', '', true);
		$pdfDocument->Ln(6);

		// Article's authors
		$authors = $publication->getData('authors');
		if (count($authors) > 0) {
			/* @var $author Author */
			// En este ciclo se itera en la lista de autores del documento, acá se puden modificar ciertos estilos.
			foreach ($authors as $author) {
				$pdfDocument->SetFont('dejavuserif', 'I', 10);

				// Calculating the line height for author name and affiliation
				$authorName = htmlspecialchars($author->getGivenName($localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($localeKey));
				$affiliation = htmlspecialchars($author->getAffiliation($localeKey));

				$authorLineWidth = 60;
				$authorNameStringHeight = $pdfDocument->getStringHeight($authorLineWidth, $authorName);

				$affiliationLineWidth = 110;
				$afilliationStringHeight = $pdfDocument->getStringHeight(110, $affiliation);

				$authorNameStringHeight > $afilliationStringHeight ? $cellHeight = $authorNameStringHeight : $cellHeight = $afilliationStringHeight;

				// Writing affiliations into cells
				$pdfDocument->MultiCell($authorLineWidth, 0, $authorName, 0, 'L', 1, 0, 19, '', true, 0, false, true, 0, "T", true);
				$pdfDocument->SetFont('dejavuserif', '', 10);
				$pdfDocument->MultiCell($affiliationLineWidth, $cellHeight, $affiliation, 0, 'L', 1, 1, '', '', true, 0, false, true, 0, "T", true);
			}
			$pdfDocument->Ln(6);
		}

		// Abstract
		// TODO: En esta seccion se puede modificar el estilo del abstract
		if ($abstract = $publication->getLocalizedData('abstract', $localeKey)) {
			$pdfDocument->setCellPaddings(5, 5, 5, 5);
			$pdfDocument->SetFillColor(204, 255, 255); // Color de fondo del abstract
			$pdfDocument->SetFont('dejavuserif', '', 10); // Letra
			$pdfDocument->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(65, 163, 231)));  // Tipo de linea divisoria y color
			$pdfDocument->writeHTMLCell('', '', '', '', $abstract, 'B', 1, 1, true, 'J', true);
			$pdfDocument->Ln(4);
		}

		// Text (goes from JATSParser
		$pdfDocument->setCellPaddings(0, 0, 0, 0);
		$pdfDocument->SetFont('dejavuserif', '', 10);

		$htmlString .= "\n" . '<style>' . "\n" . file_get_contents($pluginPath . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
		$htmlString = $this->_prepareForPdfGalley($htmlString);
		//  TODO: En el ultimo parametro es donde se escoge la alineacion del texto
		// Se puede escoger entre: R, L, C, J   ||  R = Right, L = Left, C = Center, J = Justified
		$pdfDocument->writeHTML($htmlString, true, false, true, false, 'J');

		return $pdfDocument->Output('article.pdf', 'S');
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
