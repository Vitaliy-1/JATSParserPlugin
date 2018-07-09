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

class JatsParserPlugin extends GenericPlugin {
	
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled()) {
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleViewCallback'));
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
		
		$xmlGalley = null;
		if ($galley && ($galley->getFileType() === "application/xml" || $galley->getFileType() == 'text/xml')) {
			$xmlGalley = $galley;
		}
		
		if (!$xmlGalley) return false;
		
		$submissionFile = $xmlGalley->getFile();
		$jatsDocument = new Document($submissionFile->getFilePath());
		
		$templateMgr = TemplateManager::getManager($request);
		$htmlDocument = $this->htmlCreation($templateMgr, $jatsDocument, $xmlGalley);
		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
		
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
		));
		
		$templateMgr->display($this->getTemplatePath() . 'articleView.tpl');
		
		return true;
	}
	
	/**
	 * Present rewritten XML.
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
		
		
		
		
		/* PHP Object model of JATS XML
		 * @var $submissionFile  SubmissionFile
		 */
		//$jatsDocument = new Document($submissionFile->getFilePath());
		// HTML DOM
		//$htmlDocument = $this->htmlCreation($templateMgr, $jatsDocument, $embeddedXml);
		// assigning DOM as a string to Smarty
		
		//$templateMgr->assign("htmlGalley", $htmlDocument->getHmtlForGalley());
		
		// Handling PDFs; don't do anything if article already has downloaded PDF
		//if ($boolEmbeddedPdf || !$embeddedXml) return false;
		// The string for PDF generating requests
		/*
		$generatePdfUrl = $request->getCompleteUrl() . "?" . CREATE_PDF_QUERY;
		$templateMgr->assign("generatePdfUrl", $generatePdfUrl);
		
		if ($request->getQueryString() !== CREATE_PDF_QUERY) return false;
		$this->pdfCreation($articleArrays, $request, $htmlDocument, $issueArrays, $templateMgr);
		*/
		
		// TODO Display JATS XML as HTML
		/*
		$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
		$context = PKPApplication::getRequest()->getContext();
		$contextId = $context ? $context->getId() : 0;
		print_r($pluginSettingsDAO->getPluginSettings($contextId, 'LensGalleyPlugin'));
		*/
	}
	
	/**
	 * @param $articleArrays PublishedArticle
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issueArrays Issue
	 * @param $templateMgr TemplateManager
	 */
	private function pdfCreation($articleArrays, $request, $htmlDocument, $issueArrays, $templateMgr): void
	{
		$journal = $request->getJournal();
		
		// extends TCPDF object
		$pdfDocument = new TCPDFDocument();
		
		$pdfDocument->setTitle($articleArrays->getLocalizedFullTitle());
		
		// get the logo
		
		$journal = $request->getContext();
		$pdfHeaderLogo = __DIR__ . "/jatsParser/logo/logo.jpg";
		
		$pdfDocument->SetCreator(PDF_CREATOR);
		$pdfDocument->SetAuthor($articleArrays->getAuthorString());
		$pdfDocument->SetSubject($articleArrays->getLocalizedSubject());
		
		
		$articleDataString = $issueArrays->getIssueIdentification();
		if ($articleArrays->getPages()) {
			$articleDataString .= ", ". $articleArrays->getPages();
		}
		
		if ($articleArrays->getSectionTitle()) {
			$articleDataString .= "\n" . $articleArrays->getSectionTitle();
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
		$pdfDocument->MultiCell('', '', $articleArrays->getLocalizedFullTitle(), 0, 'L', 1, 1, '' ,'', true);
		$pdfDocument->Ln(6);
		
		// Article's authors
		if (count($articleArrays->getAuthors()) > 0) {
			/* @var $author Author */
			foreach ($articleArrays->getAuthors() as $author) {
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
		if ($articleArrays->getLocalizedAbstract()) {
			$pdfDocument->setCellPaddings(5, 5, 5, 5);
			$pdfDocument->SetFillColor(248, 248, 255);
			$pdfDocument->SetFont('dejavuserif', '', 10);
			$pdfDocument->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 140, 0)));
			$pdfDocument->writeHTMLCell('', '', '', '', $articleArrays->getLocalizedAbstract(), 'B', 1, 1, true, 'J', true);
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
		
		// Add the link to images
		
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
		$xpath = new \DOMXPath($htmlDocument);
		$imageLinks = $xpath->evaluate("//img");
		foreach ($imageLinks as $imageLink) {
			if ($imageLink->hasAttribute("src")) {
				array_key_exists($imageLink->getAttribute("src"), $imageUrlArray);
				$imageLink->setAttribute("src", $imageUrlArray[$imageLink->getAttribute("src")]);
			}
		}
		
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
}