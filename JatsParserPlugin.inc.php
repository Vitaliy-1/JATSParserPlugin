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
				$this->import('classes.JatsParserGalleyDAO');
				DAORegistry::registerDAO('JatsParserGalleyDAO', new JatsParserGalleyDAO());
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleViewCallback'));
				HookRegistry::register('ArticleHandler::view::galley', array($this, 'pdfViewCallback'));
				HookRegistry::register('Templates::Article::Main', array($this, 'embeddedXmlGalley'), HOOK_SEQUENCE_CORE);

				// Add an option to set default XML galley to display in the ArticleGalley form (only when editing galley)
				HookRegistry::register('TemplateManager::fetch', array($this, 'templateFetchCallback'));
				HookRegistry::register('articlegalleydao::getAdditionalFieldNames', array($this, 'addArticleGalleyDAOFieldNames'));
				HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));
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

		// Theme-specific styling for the galley page
		switch ($context->getSetting("themePluginPath")) {
			case "default":
				$templateMgr->addStyleSheet('jatsParserThemeStyles', $baseUrl . '/resources/styles/default/galley.css');
				$templateMgr->addJavaScript('jatsParserThemeJavascript', $baseUrl . '/resources/javascript/default/galley.js');
				break;
		}

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

		$templateMgr->display($this->getTemplateResource('articleView.tpl'));

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
	 * @param $hookName string
	 * @param $args array (PublishedArticle, SubmissionFile, submission file id)
	 * @return boolean
	 * @brief display parsed XML on article landing page
	 */
	function embeddedXmlGalley($hookName, $args) {
		$templateMgr =& $args[1];
		$output =& $args[2];
		$request = $this->getRequest();
		$context = $request->getContext();

		if (!$this->getSetting($context->getId(), "displayOnArticlePage")) return false;

		$article = $templateMgr->getTemplateVars('article');
		$galleys = $templateMgr->getTemplateVars('primaryGalleys');

		$locale = AppLocale::getLocale();
		$primaryLocale = AppLocale::getPrimaryLocale();

		// Determine what galley to display depending on galley's and request locale
		$xmlGalleys = array();
		$defaultGalley = null;
		$localizedDefaultGalley = null;

		foreach ($galleys as $galley) {
			$defaultGalleySetting = $galley->getData("jatsParserDisplayDefaultXml", $primaryLocale);
			$localizedDefaultGalleySetting = $galley->getData("jatsParserDisplayDefaultXml", $locale);
			if (($galley->getFileType() === "application/xml" || $galley->getFileType() === "text/xml")) {
				$xmlGalleys[$galley->getLocale()] = $galley;
			}

			if ($defaultGalleySetting) {
				$defaultGalley = $galley;
			}

			if ($localizedDefaultGalleySetting) {
				$localizedDefaultGalley = $galley;
			}
		}

		if (empty($xmlGalleys)) return false;

		if ($localizedDefaultGalley) {
			$xmlGalley = $localizedDefaultGalley;
		} else if ($defaultGalley) {
			/* @var $defaultGalley ArticleGalley */
			$xmlGalley = $defaultGalley;
		} else if ($xmlGalleys[$locale]) {
			// Last localized galley fetched
			$xmlGalley = $xmlGalleys[$locale];
		} else if ($xmlGalleys[$primaryLocale]) {
			// Last galley fetched in primary locale
			$xmlGalley = $xmlGalleys[$primaryLocale];
		} else {
			// Finally give up
			$xmlGalley = $xmlGalleys[0];
		}

		// Convert JATS and assign HTML
		$request = Application::getRequest();
		$contextId = $request->getContext()->getId();

		$jatsDocument = new Document($xmlGalley->getFile()->getFilePath());
		$htmlDocument = $this->htmlCreation($article, $templateMgr, $jatsDocument, $xmlGalley, $request);
		$templateMgr->assign('htmlDocument', $htmlDocument->saveHTML());
		if ($this->getSetting($contextId, 'convertToPdf')) {
			$convertedPdfUrl = $request->getCompleteUrl() . DIRECTORY_SEPARATOR . $xmlGalley->getId() . '?' . CREATE_PDF_QUERY;
			$templateMgr->assign('convertedPdfUrl', $convertedPdfUrl);
		}
		$output .= $templateMgr->fetch($this->getTemplateResource('articleMain.tpl'));

		return false;
	}

	/**
	 * @param $hookName string
	 * @param $args array (PKPTemplateManager, template, cache id, compile id, result)
	 * @return boolean
	 * @brief galley-level settings for the plugin
	 */
	function templateFetchCallback($hookName, $args) {

		$templateMgr = $args[0];
		$template = $args[1];
		$request = $this->getRequest();
		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();

		if ($template == 'controllers/grid/gridRow.tpl') {
			$row = $templateMgr->getTemplateVars('row');
			$data = $row->getData();
			if (!is_array($data) &&
				(get_class($data) == "ArticleGalley") &&
				in_array($data->getFileType(), array("application/xml", "text/xml"))) {

				/* @var $data ArticleGalley */
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				$row->addAction(new LinkAction(
					'jatsParser',
					new AjaxModal(
						$dispatcher->url($request, ROUTE_PAGE, null, 'jatsParser', 'settings', null,
							array(
								'galleyId' => $data->getId(),
								'submissionId' => $data->getSubmissionId()
							)
						),
						__("plugins.generic.jatsParser.workflow.settings")
					),
					__('plugins.generic.jatsParser.workflow.settings'),
					null
				));

			}
		}
	}

	/**
	 * @param $hookName string
	 * @param $args array
	 */
	function addArticleGalleyDAOFieldNames($hookName, $args) {
		$fields =& $args[1];
		$fields[] = 'jatsParserDisplayDefaultXml';
	}

	function callbackLoadHandler($hookName, $args) {
		$page = $args[0];
		$op = $args[1];

		switch ("$page/$op") {
			case 'jatsParser/settings':
			case 'jatsParser/updateGalleySettings':
				define('HANDLER_CLASS', 'JatsParserHandler');
				define('JATSPARSER_PLUGIN_NAME', $this->getName());
				$args[2] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'JatsParserHandler.inc.php';
				break;
		}

		return false;
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
