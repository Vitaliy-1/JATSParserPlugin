<?php
/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.inc.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserSettingsForm
 * @ingroup plugins_generic_jatsParser
 *
 */

require_once __DIR__ . '/JATSParser/vendor/autoload.php';

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.jatsParser.classes.JATSParserDocument');

use JATSParser\Body\Document as Document;
use JATSParser\PDF\TCPDFDocument as TCPDFDocument;

define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin {

	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {

			// Return false if Lens is enabled
			$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
			$context = PKPApplication::getRequest()->getContext();
			$contextId = $context ? $context->getId() : 0;
			$lensSettings = $pluginSettingsDAO->getPluginSettings($contextId, 'LensGalleyPlugin');

			if ($this->getEnabled() && !$lensSettings['enabled']) {
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleViewCallback'));
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'pdfViewCallback'));
				HookRegistry::register('ArticleHandler::download', array($this, 'xmlDownload'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.jatsParser.displayName');
	}

	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.jatsParser.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}
 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('JatsParserSettingsForm');
				$form = new JatsParserSettingsForm($this, $context->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * Present the article wrapper page.
	 * @param $hookName string
	 * @param $args array
	 * @return bool
	 */
	function articleViewCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		if ($request->getQueryString() === CREATE_PDF_QUERY) return false;

		$xmlGalley = null;
		if ($galley && ($galley->getFileType() === "application/xml" || $galley->getFileType() == 'text/xml')) {
			$xmlGalley = $galley;
		}

		if (!$xmlGalley) return false;

		$submissionFile = $xmlGalley->getFile();
		$jatsDocument = new Document($submissionFile->getFilePath());

		$templateMgr = TemplateManager::getManager($request);

		//creating HTML Document
		$htmlDocument = $this->htmlCreation($article, $templateMgr, $jatsDocument, $xmlGalley, $request);

		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
		$generatePdfUrl = $request->getCompleteUrl() . "?" . CREATE_PDF_QUERY;

		$templateMgr->addStyleSheet('styles', $baseUrl . '/app/app.min.css');
		$templateMgr->addStyleSheet('googleFonts', 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&amp;subset=cyrillic');
		$templateMgr->addJavaScript('fontawesome', 'https://use.fontawesome.com/releases/v5.2.0/js/all.js');
		$templateMgr->addJavaScript('javascript', $baseUrl . '/app/app.min.js');

		$orcidImage = $this->getPluginPath() . '/templates/images/orcid.png';

		$templateMgr->assign(array(
			'issue' => $issue,
			'article' => $article,
			'galley' => $galley,
			'htmlDocument' => $htmlDocument->saveHTML(),
			'pluginUrl' => $baseUrl,
			'generatePdfUrl' => $generatePdfUrl,
			'jatsParserOrcidImage' => $orcidImage,
		));

		$templateMgr->display($this->getTemplateResource('articleView.tpl'));

		return true;
	}

	/**
	 * Hadnling request to PDF download page.
	 * @param $hookName string
	 * @param $args array
	 * @return bool
	 *
	 */
	function pdfViewCallback($hookName, $args) {
		/* @var $request Request */
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		if ($request->getQueryString() !== CREATE_PDF_QUERY) return false;

		$xmlGalley = null;
		if ($galley && ($galley->getFileType() === "application/xml" || $galley->getFileType() == 'text/xml')) {
			$xmlGalley = $galley;
		}

		if (!$xmlGalley) return false;

		$submissionFile = $xmlGalley->getFile();
		$jatsDocument = new Document($submissionFile->getFilePath());

		$parseReferences = $this->getSetting($request->getContext()->getId(), 'references');

		if ($parseReferences === "ojsReferences") {
			$htmlDocument = new JATSParserDocument($jatsDocument, false);
		} else {
			$htmlDocument = new JATSParserDocument($jatsDocument);
		}

		$this->pdfCreation($article, $request, $htmlDocument, $issue, $xmlGalley);

		return true;
	}

	/**
	 * Present XML.
	 * @param $hookName string
	 * @param $args array (PublishedArticle, SubmissionFile, submission file id)
	 * @return bool
	 */
	function xmlDownload ($hookName, $args) {
		$galley =& $args[1];
		$fileId =& $args[2];

		$embeddedXml = null;

		if ($galley && ($galley->getFileType() === "application/xml" || $galley->getFileType() ==="text/xml") && $galley->getFileId() == $fileId) {
			$embeddedXml = $galley;
		}

		if (!$embeddedXml) return false;

	}

	/**
	 * @param $article PublishedArticle
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issue Issue
	 * @param
	 */
	private function pdfCreation(PublishedArticle $article, Request $request, JATSParserDocument $htmlDocument, Issue $issue, ArticleGalley $xmlGalley): void
	{
		// HTML preparation
		$xpath = new \DOMXPath($htmlDocument);
		$templateMgr = TemplateManager::getManager($request);

		$this->imageUrlReplacement($xmlGalley, $xpath);
		$this->ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request);

		// Special treatment for table head
		$tableHeadRows = $xpath->evaluate("//thead/tr");
		foreach ($tableHeadRows as $tableHeadRow) {
			$tableHeadRow->setAttribute("align", "center");
			$tableHeadRow->setAttribute("style", "background-color:#f2e6ff;");
		}


		// extends TCPDF object
		$pdfDocument = new TCPDFDocument();

		$pdfDocument->setTitle($article->getLocalizedFullTitle());

		// get the logo

		$journal = $request->getContext();
		$pdfHeaderLogo = __DIR__ . "/JATSParser/logo/logo.jpg";

		$pdfDocument->SetCreator(PDF_CREATOR);
		$pdfDocument->SetAuthor($article->getAuthorString());
		$pdfDocument->SetSubject($article->getLocalizedSubject());


		$articleDataString = $issue->getIssueIdentification();
		if ($article->getPages()) {
			$articleDataString .= ", ". $article->getPages();
		}

		if ($article->getSectionTitle()) {
			$articleDataString .= "\n" . $article->getSectionTitle();
		}

		$pdfDocument->SetHeaderData($pdfHeaderLogo, PDF_HEADER_LOGO_WIDTH, $journal->getLocalizedName(), $articleDataString);

		$pdfDocument->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdfDocument->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$pdfDocument->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdfDocument->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdfDocument->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdfDocument->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdfDocument->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdfDocument->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$pdfDocument->AddPage();

		/* An example of using translations inside PHP */
		//$translate = $templateMgr->smartyTranslate(array('key' =>'common.abstract'), $templateMgr);

		// Article title

		$pdfDocument->SetFillColor(255, 255, 255);
		$pdfDocument->SetFont('dejavuserif', 'B', 20);
		$pdfDocument->MultiCell('', '', $article->getLocalizedFullTitle(), 0, 'L', 1, 1, '' ,'', true);
		$pdfDocument->Ln(6);

		// Article's authors
		if (count($article->getAuthors()) > 0) {
			/* @var $author Author */
			foreach ($article->getAuthors() as $author) {
				$pdfDocument->SetFont('dejavuserif', 'I', 10);

				// Calculating the line height for author name and affiliation

				$authorLineWidth = 60;
				$authorNameStringHeight = $pdfDocument->getStringHeight($authorLineWidth, htmlspecialchars($author->getFullName()));

				$affiliationLineWidth = 110;
				$afilliationStringHeight = $pdfDocument->getStringHeight(110, htmlspecialchars($author->getLocalizedAffiliation()));

				$authorNameStringHeight > $afilliationStringHeight ? $cellHeight = $authorNameStringHeight : $cellHeight = $afilliationStringHeight;

				// Writing affiliations into cells
				$pdfDocument->MultiCell($authorLineWidth, 0, htmlspecialchars($author->getFullName()), 0, 'L', 1, 0, 19, '', true, 0, false, true, 0, "T", true);
				$pdfDocument->SetFont('dejavuserif', '', 10);
				$pdfDocument->MultiCell($affiliationLineWidth, $cellHeight, htmlspecialchars($author->getLocalizedAffiliation()), 0, 'L', 1, 1, '', '', true, 0, false, true, 0, "T", true);
			}
			$pdfDocument->Ln(6);
		}

		// Abstract
		if ($article->getLocalizedAbstract()) {
			$pdfDocument->setCellPaddings(5, 5, 5, 5);
			$pdfDocument->SetFillColor(248, 248, 255);
			$pdfDocument->SetFont('dejavuserif', '', 10);
			$pdfDocument->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 140, 0)));
			$pdfDocument->writeHTMLCell('', '', '', '', $article->getLocalizedAbstract(), 'B', 1, 1, true, 'J', true);
			$pdfDocument->Ln(4);
		}

		// Text (goes from JATSParser
		$pdfDocument->setCellPaddings(0, 0, 0, 0);
		$pdfDocument->SetFont('dejavuserif', '', 10);

		$htmlString = $htmlDocument->getHtmlForTCPDF();

		$pdfDocument->writeHTML($htmlString, true, false, true, false, '');

		$pdfDocument->Output('article.pdf', 'I');
	}

	/**
	 * @param $article PublishedArticle
	 * @param $jatsDocument Document
	 * @param $templateMgr TemplateManager
	 * @param $request Request
	 * @return $htmlDocument JATSParserDocument
	 * @brief preparation of HTML file
	 */
	private function htmlCreation($article, $templateMgr, $jatsDocument, $embeddedXml, $request): JATSParserDocument
	{
		$context = $request->getContext();

		$parseReferences = $this->getSetting($context->getId(), 'references');
		if ($parseReferences === "ojsReferences") {
			$htmlDocument = new JATSParserDocument($jatsDocument, false);
		} else {
			$htmlDocument = new JATSParserDocument($jatsDocument, true);
		}

		// HTML DOM
		$xpath = new \DOMXPath($htmlDocument);

		$this->imageUrlReplacement($embeddedXml, $xpath);

		$this->ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request);

		// Localization of reference list title
		$referenceTitles = $xpath->evaluate("//h2[@id='reference-title']");
		$translateReference = $templateMgr->smartyTranslate(array('key' =>'submission.citations'), $templateMgr);
		if ($referenceTitles->lenght > 0) {
			foreach ($referenceTitles as $referenceTitle) {
				$referenceTitle->nodeValue = $translateReference;
			}
		}


		return $htmlDocument;
	}

	/**
	 * @param $embeddedXml
	 * @param $xpath
	 * @brief replacement for url to figures with actuall path
	 */
	private function imageUrlReplacement($embeddedXml, $xpath): void
	{
		$submissionFile = $embeddedXml->getFile();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // Constants
		$embeddableFiles = array_merge(
			$submissionFileDao->getLatestRevisions($submissionFile->getSubmissionId(), SUBMISSION_FILE_PROOF),
			$submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $submissionFile->getFileId(), $submissionFile->getSubmissionId(), SUBMISSION_FILE_DEPENDENT)
		);
		$referredArticle = null;
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$imageUrlArray = array();
		foreach ($embeddableFiles as $embeddableFile) {
			$params = array();
			if ($embeddableFile->getFileType() == 'image/png' || $embeddableFile->getFileType() == 'image/jpeg') {
				// Ensure that the $referredArticle object refers to the article we want
				if (!$referredArticle || $referredArticle->getId() != $embeddedXml->getSubmissionId()) {
					$referredArticle = $articleDao->getById($embeddedXml->getSubmissionId());
				}
				$fileUrl = Application::getRequest()->url(null, 'article', 'download', array($referredArticle->getBestArticleId(), $embeddedXml->getBestGalleyId(), $embeddableFile->getFileId()), $params);
				$imageUrlArray[$embeddableFile->getOriginalFileName()] = $fileUrl;
			}
		}

		// Replace link with actual path
		$imageLinks = $xpath->evaluate("//img");
		foreach ($imageLinks as $imageLink) {
			if ($imageLink->hasAttribute("src")) {
				array_key_exists($imageLink->getAttribute("src"), $imageUrlArray);
				$imageLink->setAttribute("src", $imageUrlArray[$imageLink->getAttribute("src")]);
			}
		}
	}

	/**
	 * @param $article
	 * @param $templateMgr
	 * @param $htmlDocument
	 * @param $request Request
	 */
	private function ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request): void
	{
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$parsedCitations = $citationDao->getBySubmissionId($article->getId());
		$parseReferences = $this->getSetting($request->getContext()->getId(), 'references');

		if (($htmlDocument->useOjsReferences() && $parsedCitations && $parseReferences !== 'jatsReferences') || ($parseReferences === 'ojsReferences' && $parsedCitations)) {
			$referenceTitle = $htmlDocument->createElement('h2');
			$referenceTitle->setAttribute('id', 'reference-title');
			$referenceTitle->setAttribute('class', 'article-section-title');
			$referenceTitle->nodeValue = $templateMgr->smartyTranslate(array('key' =>'submission.citations'), $templateMgr);

			$htmlDocument->appendChild($referenceTitle);
			$referenceList = $htmlDocument->createElement('ol');
			$htmlDocument->appendChild($referenceList);
			while ($parsedCitation = $parsedCitations->next()) {
				$referenceItem = $htmlDocument->createElement('li');
				$referenceItem->nodeValue = $templateMgr->smartyEscape($parsedCitation->getRawCitation());
				$referenceList->appendChild($referenceItem);
			}

		}
	}
}
