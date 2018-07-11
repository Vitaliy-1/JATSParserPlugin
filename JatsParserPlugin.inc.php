<?php
/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.inc.php
 *
 * Copyright (c) 2017 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserSettingsForm
 * @ingroup plugins_generic_jatsParser
 *
 */
	
require_once __DIR__ . '/JATSParser/vendor/autoload.php';

import('lib.pkp.classes.plugins.GenericPlugin');

use JATSParser\Body\Document as Document;
use JATSParser\PDF\TCPDFDocument as TCPDFDocument;
use JATSParser\HTML\Document as HTMLDocument;

define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin {
	
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled()) {
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleViewCallback'));
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'pdfViewCallback'));
				HookRegistry::register('ArticleHandler::download', array($this, 'xmlDownload'));
				$this->_registerTemplateResource();
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
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		return $this->getTemplateResourceName() . ':templates/';
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
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
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
		
		// TODO Check if Lens Plugin is enabled
		/*
		$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
		$context = PKPApplication::getRequest()->getContext();
		$contextId = $context ? $context->getId() : 0;
		print_r($pluginSettingsDAO->getPluginSettings($contextId, 'LensGalleyPlugin'));
		*/
		
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
		$htmlDocument = $this->htmlCreation($templateMgr, $jatsDocument, $xmlGalley);
		
		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
		$generatePdfUrl = $request->getCompleteUrl() . "?" . CREATE_PDF_QUERY;
		
		$templateMgr->addStyleSheet('styles', $baseUrl . '/app/app.min.css');
		$templateMgr->addStyleSheet('fontawesome', 'https://use.fontawesome.com/releases/v5.1.0/css/all.css');
		$templateMgr->addStyleSheet('googleFonts', 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&amp;subset=cyrillic');
		$templateMgr->addJavaScript('javascript', $baseUrl . '/app/app.js');
		
		$templateMgr->assign(array(
			'issue' => $issue,
			'article' => $article,
			'galley' => $galley,
			'htmlDocument' => $htmlDocument->saveHTML(),
			'pluginUrl' => $baseUrl,
			'generatePdfUrl' => $generatePdfUrl,
		));
		
		$templateMgr->display($this->getTemplatePath() . 'articleView.tpl');
		
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
		$htmlDocument = new HTMLDocument($jatsDocument);
		
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
	private function pdfCreation(PublishedArticle $article, Request $request, HTMLDocument $htmlDocument, Issue $issue, ArticleGalley $xmlGalley): void
	{
		// HTML preparation
		$xpath = new \DOMXPath($htmlDocument);
		$this->imageUrlReplacement($xmlGalley, $xpath);
		
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
	 * @param $jatsDocument Document
	 * @param $templateMgr TemplateManager
	 * @return HTMLDocument HTMLDocument
	 * @brief preparation of HTML file
	 */
	private function htmlCreation($templateMgr, $jatsDocument, $embeddedXml): HTMLDocument
	{
		// HTML DOM
		$htmlDocument = new HTMLDocument($jatsDocument);
		$xpath = new \DOMXPath($htmlDocument);
		
		$this->imageUrlReplacement($embeddedXml, $xpath);
		
		
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
}