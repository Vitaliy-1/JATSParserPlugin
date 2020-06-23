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
import('plugins.generic.jatsParser.classes.components.forms.PublicationJATSUploadForm');

use JATSParser\Body\Document as Document;
use JATSParser\PDF\TCPDFDocument as TCPDFDocument;
use JATSParser\HTML\Document as HTMLDocument;

define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin {

	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {

			// Return false if Lens is enabled
			$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
			$context = $this->getRequest()->getContext();
			$contextId = $context ? $context->getId() : 0;
			$lensSettings = $pluginSettingsDAO->getPluginSettings($contextId, 'LensGalleyPlugin');

			if ($this->getEnabled() && !$lensSettings['enabled']) {
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleViewCallback'));
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'pdfViewCallback'));
			}


			// Add data to the publication
			HookRegistry::register('Template::Workflow::Publication', array($this, 'publicationTemplateData'));
			HookRegistry::register('Schema::get::publication', array($this, 'addToSchema'));
			HookRegistry::register('TemplateManager::display', array($this, 'previewFullTextCall'));
			HookRegistry::register('LoadHandler', array($this, 'loadPreviewHandler'));
			HookRegistry::register('Publication::edit', array($this, 'editPublication'));
			HookRegistry::register('Templates::Article::Main', array($this, 'displayFullText'));
			HookRegistry::register('TemplateManager::display', array($this, 'themeSpecificStyles'));

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
	 * @deprecated
	 */
	function articleViewCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		$context = $request->getContext();
		$contextId = $context->getId();

		if (($request->getQueryString() === CREATE_PDF_QUERY) && ($this->getSetting($contextId, 'convertToPdf'))) return false;

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

		// Recording a view
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($article->getContextId(), $article->getId());
		$submissionFileManager->recordView($xmlGalley->getFile());

		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

		$templateMgr->addStyleSheet('jatsParserStyles', $baseUrl . '/resources/styles/jatsParser.css');
		$templateMgr->addStyleSheet('googleFonts', 'https://fonts.googleapis.com/css?family=PT+Serif:400,700&amp;subset=cyrillic');
		$templateMgr->addJavaScript('jatsParserJavascript', $baseUrl . '/resources/javascript/jatsParser.js');

		$orcidImage = $this->getPluginPath() . '/templates/images/orcid.png';

		$templateMgr->assign(array(
			'issue' => $issue,
			'article' => $article,
			'galley' => $galley,
			'htmlDocument' => $htmlDocument->saveHTML(),
			'pluginUrl' => $baseUrl,
			'jatsParserOrcidImage' => $orcidImage,
		));

		if ($this->getSetting($contextId, 'convertToPdf')) {
			$generatePdfUrl = $request->getCompleteUrl() . "?" . CREATE_PDF_QUERY;
			$templateMgr->assign('generatePdfUrl', $generatePdfUrl);
		}

		$templateMgr->display($this->getTemplateResource('articleGalleyView.tpl'));

		return false;
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

		if (($request->getQueryString() !== CREATE_PDF_QUERY) || (!$this->getSetting($request->getContext()->getId(), 'convertToPdf'))) return false;

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

		return false;
	}

	/**
	 * @param $article Submission
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issue Issue
	 * @param
	 */
	private function pdfCreation(Submission $article, Request $request, JATSParserDocument $htmlDocument, Issue $issue, ArticleGalley $xmlGalley): void
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
	 * @param $article Submission
	 * @param $jatsDocument Document
	 * @param $templateMgr TemplateManager
	 * @param $request Request
	 * @return $htmlDocument JATSParserDocument
	 * @brief preparation of HTML file
	 * @deprecated
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
		if ($referenceTitles->length > 0) {
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
	 * @deprecated
	 */
	private function imageUrlReplacement($embeddedXml, $xpath): void
	{
		$submissionFile = $embeddedXml->getFile();
		$submissionId = $submissionFile->getSubmissionId();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // Constants
		$embeddableFiles = array_merge(
			$submissionFileDao->getLatestRevisions($submissionFile->getSubmissionId(), SUBMISSION_FILE_PROOF),
			$submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $submissionFile->getFileId(), $submissionFile->getSubmissionId(), SUBMISSION_FILE_DEPENDENT)
		);
		$referredArticle = null;
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$imageUrlArray = array();
		foreach ($embeddableFiles as $embeddableFile) {
			$params = array();
			if ($embeddableFile->getFileType() == 'image/png' || $embeddableFile->getFileType() == 'image/jpeg') {
				// Ensure that the $referredArticle object refers to the article we want
				if (!$referredArticle || $referredArticle->getId() != $submissionId) {
					$referredArticle = $submissionDao->getById($submissionId);
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
		$citations = $article->getCurrentPublication()->getData("citationsRaw");
		$parseReferences = $this->getSetting($request->getContext()->getId(), 'references');

		if (($htmlDocument->useOjsReferences() && $citations && $parseReferences !== 'jatsReferences') || ($parseReferences === 'ojsReferences' && $citations)) {
			$referenceTitle = $htmlDocument->createElement('h2');
			$referenceTitle->setAttribute('id', 'reference-title');
			$referenceTitle->setAttribute('class', 'article-section-title');
			$referenceTitle->nodeValue = $templateMgr->smartyTranslate(array('key' =>'submission.citations'), $templateMgr);

			$htmlDocument->appendChild($referenceTitle);

			$parsedCitations = DAORegistry::getDAO('CitationDAO')->getByPublicationId($article->getId())->toArray();
			if (!empty($parsedCitations)) {
				foreach ($parsedCitations as $parsedCitation) {
					$referenceList = $htmlDocument->createElement('ol');
					$htmlDocument->appendChild($referenceList);
					$referenceItem = $htmlDocument->createElement('li');
					$referenceItem->nodeValue = $templateMgr->smartyEscape($parsedCitation->getRawCitation());
					$referenceList->appendChild($referenceItem);
				}
			} else {
				$resultArray = explode("\n", $citations);
				$referenceList = $htmlDocument->createElement('p');
				$htmlDocument->appendChild($referenceList);
				foreach ($resultArray as $result) {
					$textNode = $htmlDocument->createTextNode($templateMgr->smartyEscape($result));
					$referenceList->appendChild($textNode);
					$referenceList->appendChild($htmlDocument->createElement("br"));
					$htmlDocument->appendChild($referenceList);
				}
			}

		}
	}

	/**
	 * Add a property to the publication schema
	 *
	 * @param $hookName string `Schema::get::publication`
	 * @param $args [[
	 * 	@option object Publication schema
	 * ]]
	 */
	public function addToSchema($hookName, $args) {
		$schema = $args[0];
		$propId = '{
			"type": "integer",
			"multilingual": true,
			"apiSummary": true,
			"validation": [
				"nullable"
			]
		}';
		$propText = '{
			"type": "string",
			"multilingual": true,
			"apiSummary": true,
			"validation": [
				"nullable"
			]
		}';
		$schema->properties->{'jatsParser::fullTextFileId'} = json_decode($propId);
		$schema->properties->{'jatsParser::fullText'} = json_decode($propText);
	}

	/**
	 * @param string $hookname
	 * @param array $args [string, TemplateManager]
	 */
	function publicationTemplateData(string $hookname, array $args): void {
		/**
		 * @var $templateMgr TemplateManager
		 * @var $submission Submission
		 * @var $submissionFileDao SubmissionFileDAO
		 * @var $submissionFile SubmissionFile
		 */
		$templateMgr = $args[1];
		$request = $this->getRequest();
		$context = $request->getContext();
		$submission = $templateMgr->getTemplateVars('submission');
		$latestPublication = $submission->getLatestPublication();
		$latestPublicationApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());

		$supportedSubmissionLocales = $context->getSupportedSubmissionLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedSubmissionLocales);

		$submissionFileDao = DAORegistry::getDAO("SubmissionFileDAO");
		$submissionFiles = $submissionFileDao->getLatestRevisions($submission->getId(), SUBMISSION_FILE_PRODUCTION_READY);
		$submissionFilesXML = array();
		foreach ($submissionFiles as $submissionFile) {
			if (in_array($submissionFile->getFileType(), array("application/xml", "text/xml"))) {
				$submissionFilesXML[] = $submissionFile;
			}
		}

		$dispatcher = $request->getDispatcher();
		$submissionProps = Services::get('submission')->getProperties($submission, array('stageId'), array('request' => $request));
		$currentPath = $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'fullTextPreview', $submission->getId(), $submissionProps);
		if (!empty($submissionFiles)) {
			$msg = $templateMgr->smartyTranslate(array(
				'key' => 'plugins.generic.jatsParser.publication.jats.description',
				'params' => array("previewPath" => $currentPath)
			), $templateMgr);
		} else {
			$msg = $templateMgr->smartyTranslate(array(
				'key' => 'plugins.generic.jatsParser.publication.jats.descriptionEmpty'
			), $templateMgr);
		}

		$form = new PublicationJATSUploadForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionFilesXML, $msg);
		$workflowData = $templateMgr->getTemplateVars('workflowData');
		$workflowData['components'][FORM_PUBLICATION_JATS_FULLTEXT] = $form->getConfig();

		$templateMgr->assign('workflowData', $workflowData);

		$templateMgr->display($this->getTemplateResource("workflowJatsFulltext.tpl"));
	}

	function previewFullTextCall(string $hookname, array $args) {
		/**
		 * @var $templateMgr TemplateManager
		 */
		$templateMgr = $args[0];
		$template = $args[1];
		$request = $this->getRequest();

		if ($template != 'workflow/workflow.tpl') {
			return false;
		}

		$templateMgr->addJavaScript('fulltextPreview', $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'preview.js', array('contexts' => 'backend'));

		return false;
	}

	/**
	 * @param $hookName string
	 * @param $args array
	 * @brief Controller for the preview page
	 */
	function loadPreviewHandler($hookName, $args) {
		$page = $args[0];
		$op = $args[1];
		$request = $this->getRequest();
		$userVars = $request->getUserVars();

		if ($page == 'workflow' && $op == 'fullTextPreview' && array_key_exists('_full-text-preview', $userVars)) {
			define('HANDLER_CLASS', 'FullTextPreviewHandler');
			define('JATSPARSER_PLUGIN_NAME', $this->getName());
			$args[2] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'FullTextPreviewHandler.inc.php';

		} else if ($page == 'article' && $op == 'downloadFullTextAssoc') {
			define('HANDLER_CLASS', 'FullTextArticleHandler');
			define('JATSPARSER_PLUGIN_NAME', $this->getName());
			$args[2] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'FullTextArticleHandler.inc.php';
		}
	}

	/**
	 * @param string $hookname
	 * @param array $args [
	 *   Publication -> new publication
	 *   Publication
	 *   array parameters/publication properties to be saved
	 *   Request
	 * ]
	 * @return bool
	 */
	function editPublication(string $hookname, array $args) {
		$newPublication = $args[0];
		$params = $args[2];
		if (!array_key_exists('jatsParser::fullTextFileId', $params)) return false;

		$localePare = $params['jatsParser::fullTextFileId'];
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		foreach ($localePare as $localeKey => $fileId) {
			if (empty($fileId)) {
				$newPublication->setData('jatsParser::fullText', null, $localeKey);
				continue;
			}
			$submissionFile = $submissionFileDao->getLatestRevision($fileId, SUBMISSION_FILE_PRODUCTION_READY);
			$htmlDocument = $this->getFullTextFromJats($submissionFile);
			$newPublication->setData('jatsParser::fullText', $htmlDocument->saveHTML(), $localeKey);
		}

		return false;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @return HTMLDocument
	 * @brief retrieves PHP DOM representation of the article's full-text
	 */
	public function getFullTextFromJats (SubmissionFile $submissionFile): HTMLDocument {
		$htmlDocument = new HTMLDocument(new Document($submissionFile->getFilePath()), false);
		return $htmlDocument;
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return bool
	 * @brief Displays full-text on article landing page and preview page
	 */
	function displayFullText(string $hookname, array $args) {
		$templateMgr =& $args[1];
		$output =& $args[2];
		$publication = $templateMgr->getTemplateVars('publication');
		$submission = $templateMgr->getTemplateVars('article');
		$submissionId = $submission->getId();
		$fullTexts = $publication->getData('jatsParser::fullText');

		$submissionFileId = 0;
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFile = null;

		$request = $this->getRequest();
		$requestedOp = $request->getRequestedOp();
		$html = null;

		if ($requestedOp === 'view') {
			if (empty($fullTexts)) return false;
			$currentLocale = AppLocale::getLocale();
			if (array_key_exists($currentLocale, $fullTexts)) {
				$html = $fullTexts[$currentLocale];

				$submissionFileId = $publication->getData('jatsParser::fullTextFileId', $currentLocale);
				$submissionFile = $submissionFileDao->getLatestRevision($submissionFileId, SUBMISSION_FILE_PRODUCTION_READY, $submissionId);
			} else {
				$locales = AppLocale::getAllLocales();
				$msg = __('plugins.generic.jatsParser.article.fulltext.availableLocale');
				if (count($fullTexts) > 1) {
					$msg = __('plugins.generic.jatsParser.article.fulltext.availableLocales');
				}

				$html = $msg;
				foreach ($fullTexts as $localeKey => $fullText) {
					$html .= ' <a href="' . $request->url(null, 'user', 'setLocale', $localeKey) . '">' . $locales[$localeKey] . '</a>';
					if ($fullText !== end($fullTexts)) {
						$html .= ', ';
					} else {
						$html .= '.';
					}
				}
			}

		} else if ($requestedOp === 'fullTextPreview') {
			$submissionFileId = $request->getUserVar('_full-text-preview');
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$submissionFile = $submissionFileDao->getLatestRevision($submissionFileId, SUBMISSION_FILE_PRODUCTION_READY, $submissionId);
			$html = $this->getFullTextFromJats($submissionFile)->saveHTML();
		}

		if (is_null($html)) return false;

		if ($submissionFileId && $submissionFile) {
			$html = $this->_setSupplImgPath($submissionFile, $html);
		}

		$templateMgr->assign('fullText', $html);
		$output .= $templateMgr->fetch($this->getTemplateResource('articleMainView.tpl'));

		return false;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @param string $htmlString
	 * @return string
	 * @brief Substitute path to attached images for full-text HTML
	 */
	function _setSupplImgPath(SubmissionFile $submissionFile, string $htmlString): string {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $submissionFile->getFileId(), $submissionFile->getSubmissionId());
		$request = $this->getRequest();
		$imageFiles = [];

		foreach ($dependentFiles as $dependentFile) {
			if (get_class($dependentFile !== 'SubmissionArtworkFile')) continue;
			if (!in_array($dependentFile->getFileType(), self::getSupportedSupplFileTypes())) continue;
			$filePath = $request->url(null, 'article', 'downloadFullTextAssoc', array($submissionFile->getSubmissionId(), $dependentFile->getAssocId(), $dependentFile->getFileId()));
			$imageFiles[$dependentFile->getOriginalFileName()] = $filePath;
		}

		if (empty($imageFiles)) return  $htmlString;

		// Solution from HtmlArticleGalleyPlugin::_getHTMLContents
		foreach ($imageFiles as $originalFileName => $filePath) {
			$pattern = preg_quote(rawurlencode($originalFileName));

			$htmlString = preg_replace(
				'/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
				'\1="' . $filePath . '"',
				$htmlString
			);
		}

		return $htmlString;
	}

	/**
	 * @return array
	 * @brief get the list of types of files that are dependent from an original JATS XML (from which full-text was generated) and are accessible to public
	 */
	public static function getSupportedSupplFileTypes() {
		return array(
			'image/png',
			'image/jpeg'
		);
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return bool
	 * @brief theme-specific styles for galley and article landing page
	 */
	function themeSpecificStyles(string $hookname, array $args) {
		$templateMgr = $args[0];
		$template = $args[1];

		if ($template !== "frontend/pages/article.tpl" && $template !== "plugins-plugins-generic-jatsParser-generic-jatsParser:articleGalleyView.tpl") return false;

		$request = $this->getRequest();
		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

		$themePlugins = PluginRegistry::getPlugins('themes');
		foreach ($themePlugins as $themePlugin) {
			if ($themePlugin->isActive()) {
				$parentTheme = $themePlugin->parent;
				// Chances are that child theme of a Default also need this styling
				if ($themePlugin->getName() == "defaultthemeplugin" || ($parentTheme && $parentTheme->getName() == "defaultthemeplugin")) {
					if ($template === "plugins-plugins-generic-jatsParser-generic-jatsParser:articleGalleyView.tpl") {
						$templateMgr->addStyleSheet('jatsParserThemeStyles', $baseUrl . '/resources/styles/default/galley.css');
						$templateMgr->assign("isFullWidth", true); // remove sidebar for the Default theme
					} else if ($template === "frontend/pages/article.tpl") {
						$templateMgr->addStyleSheet('jatsParserThemeStyles', $baseUrl . '/resources/styles/default/article.css');
					}
				}
			}
		}

		return false;
	}
}
