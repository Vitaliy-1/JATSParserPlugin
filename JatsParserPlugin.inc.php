<?php
/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.inc.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserPlugin
 * @ingroup plugins_generic_jatsParser
 *
 */

require_once __DIR__ . '/JATSParser/vendor/autoload.php';

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.jatsParser.classes.JATSParserDocument');
import('plugins.generic.jatsParser.classes.components.forms.PublicationJATSUploadForm');
import('lib.pkp.classes.file.SubmissionFileManager');
import('lib.pkp.classes.citation.Citation');

use JATSParser\Body\Document;
use JATSParser\PDF\TCPDFDocument;
use JATSParser\HTML\Document as HTMLDocument;
use \PKP\components\forms\FormComponent;

define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin {

	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {

			if ($this->getEnabled()) {
				// Add data to the publication
				HookRegistry::register('Template::Workflow::Publication', array($this, 'publicationTemplateData'));
				HookRegistry::register('Schema::get::publication', array($this, 'addToSchema'));
				HookRegistry::register('TemplateManager::display', array($this, 'previewFullTextCall'));
				HookRegistry::register('LoadHandler', array($this, 'loadPreviewHandler'));
				HookRegistry::register('Publication::edit', array($this, 'editPublicationFullText'));
				HookRegistry::register('Templates::Article::Main', array($this, 'displayFullText'));
				HookRegistry::register('TemplateManager::display', array($this, 'themeSpecificStyles'));
				HookRegistry::register('Form::config::before', array($this, 'addCitationsFormFields'));
				HookRegistry::register('Publication::edit', array($this, 'editPublicationReferences'));
				HookRegistry::register('Publication::edit', array($this, 'createPdfGalley'), HOOK_SEQUENCE_LAST);
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
	 * @param $article Submission
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issue Issue
	 * @param
	 */
	private function pdfCreation(string $htmlString, Publication $publication, Request $request, string $localeKey): string
	{
		// HTML preparation
		$context = $request->getContext(); /* @var $context Journal */
		$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getBySubmissionId($submission->getId(), $context->getId());

		//$this->imageUrlReplacement($xmlGalley, $xpath);
		//$this->ojsCitationsExtraction($article, $templateMgr, $htmlDocument, $request);

		// extends TCPDF object
		$pdfDocument = new TCPDFDocument();

		$pdfDocument->setTitle($publication->getLocalizedFullTitle());

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
		$pdfDocument->SetSubject($publication->getLocalizedData('subject'));

		$articleDataString = $issue->getIssueIdentification();
		if ($pages = $publication->getLocalizedData('subject')) {
			$articleDataString .= ", ". $pages;
		}

		if ($doi = $publication->getData('pub-id::doi')) {
			$articleDataString .= "\n" . __('plugins.pubIds.doi.readerDisplayName', null, $localeKey) . ': ' . $doi;
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

		// Article title

		$pdfDocument->SetFillColor(255, 255, 255);
		$pdfDocument->SetFont('dejavuserif', 'B', 20);
		$pdfDocument->MultiCell('', '', $publication->getLocalizedFullTitle(), 0, 'L', 1, 1, '' ,'', true);
		$pdfDocument->Ln(6);

		// Article's authors
		$authors = $publication->getData('authors');
		if (count($authors) > 0) {
			/* @var $author Author */
			foreach ($authors as $author) {
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
		if ($abstract = $publication->getLocalizedData('abstract', $localeKey)) {
			$pdfDocument->setCellPaddings(5, 5, 5, 5);
			$pdfDocument->SetFillColor(248, 248, 255);
			$pdfDocument->SetFont('dejavuserif', '', 10);
			$pdfDocument->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 140, 0)));
			$pdfDocument->writeHTMLCell('', '', '', '', $abstract, 'B', 1, 1, true, 'J', true);
			$pdfDocument->Ln(4);
		}

		// Text (goes from JATSParser
		$pdfDocument->setCellPaddings(0, 0, 0, 0);
		$pdfDocument->SetFont('dejavuserif', '', 10);

		$htmlString .= "\n" . '<style>' . "\n" . file_get_contents($this->getPluginPath() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
		$htmlString = $this->_prepareForPdfGalley($htmlString);
		$pdfDocument->writeHTML($htmlString, true, false, true, false, '');

		return $pdfDocument->Output('article.pdf', 'S');
	}

	/**
	 * @param string $htmlString
	 * @return string Preprocessed HTML string for TCPDF
	 */
	private function _prepareForPdfGalley(string $htmlString): string {

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

		if ($page == 'workflow' && (($op == 'fullTextPreview' && array_key_exists('_full-text-preview', $userVars)) || $op = 'downloadPreviewAssoc')) {
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
	function editPublicationFullText(string $hookname, array $args) {
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
			$newPublication->setData('jatsParser::fullText', $htmlDocument->saveAsHTML(), $localeKey);
		}

		return false;
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return bool
	 * @brief modify citationsRaw property based on parsed citations from JATS XML
	 */
	function editPublicationReferences(string $hookname, array $args) {
		$newPublication = $args[0];
		$params = $args[2];
		if (!array_key_exists('jatsParser::references', $params)) return false;

		$fileId = $params['jatsParser::references'];
		if (!$fileId) return false;

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFile = $submissionFileDao->getLatestRevision($fileId, SUBMISSION_FILE_PRODUCTION_READY);
		$htmlDocument = $this->getFullTextFromJats($submissionFile);

		$request = $this->getRequest();
		$context = $request->getContext();

		// Get citations style, define default if not set
		$citationStyle = $this->getCitationStyle($context);

		$lang = str_replace('_', '-', $submissionFile->getSubmissionLocale());
		$htmlDocument->setReferences($citationStyle, $lang, false);

		$this->_importCitations($htmlDocument, $newPublication);

		return false;
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return false
	 * @brief creates a PDF file and saves as a galley
	 */
	function createPdfGalley(string $hookname, array $args) {
		$newPublication = $args[0]; /* @var $newPublication Publication */
		$params = $args[2];
		$request = $args[3];

		if (!array_key_exists('jatsParser::pdfGalley', $params)) return false;
		$submission = Services::get('submission')->get($newPublication->getData('submissionId')); /* @var $submission Submission */
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */

		$localePare = $params['jatsParser::pdfGalley'];
		foreach ($localePare as $localeKey => $createPdf) {
			$fullText = $newPublication->getData('jatsParser::fullText', $localeKey);
			if (empty($fullText)) continue;
			if (!$createPdf) continue;

			// Set real path to images, attached to the original JATS XML file
			$jatsFileId = $newPublication->getData('jatsParser::fullTextFileId', $localeKey);
			$jatsSubmissionFile = $submissionFileDao->getLatestRevision($jatsFileId, null, $submission->getId());
			if ($jatsSubmissionFile) {
				$fullText = $this->_setSupplImgPath($jatsSubmissionFile, $fullText);
			}

			// Set references
			$fullText = $this->_setReferences($newPublication, $localeKey, $fullText);

			// Finally, convert and receive TCPDF output as a binary string
			$pdf = $this->pdfCreation($fullText, $newPublication, $request, $localeKey);

			// Create a PDF Galley
			$galleyId = $this->createGalley($localeKey, $newPublication);
			$galley = $articleGalleyDao->getByBestGalleyId($galleyId, $newPublication->getId());

			// Create associated submission file and update the galley
			$submissionFile = $this->_setPdfSubmissionFile($pdf, $newPublication, $galley);
			if ($submissionFile) {
				$galley->setFileId($submissionFile->getFileId());
				$articleGalleyDao->updateObject($galley);
			}
			// remove galley if submission file is missing
			else {
				$articleGalleyDao->deleteObject($galley);
			}
		}

		return false;
	}

	/**
	 * @param string $galleyLocale
	 * @param Publication $publication
	 * @return int
	 * @brief create an empty galley
	 */
	function createGalley(string $galleyLocale, Publication $publication): int {
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
		$articleGalley = $articleGalleyDao->newDataObject();
		$articleGalley->setLocale($galleyLocale);
		$articleGalley->setData('publicationId', $publication->getId());
		$articleGalley->setLabel(__('plugins.generic.jatsParser.publication.galley.pdf.label'));
		return $articleGalleyDao->insertObject($articleGalley);
	}

	/**
	 * @param string $pdfBinaryString output of the TCPDF, binary string
	 * @param Publication $publication publication associated with a submission file
	 * @brief creates a new PDF submission file
	 */
	private function _setPdfSubmissionFile(string $pdfBinaryString, Publication $publication, ArticleGalley $galley) {
		$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */

		// Create a temporary file
		$tmpFile = tempnam(sys_get_temp_dir(), 'jatsParser');
		file_put_contents($tmpFile, $pdfBinaryString);

		// Set main Submission File data
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFile = $submissionFileDao->newDataObjectByGenreId(GENRE_CATEGORY_DOCUMENT); /* @var $submissionFile SubmissionFile */
		$submissionFile->setGenreId(GENRE_CATEGORY_DOCUMENT);
		$submissionFile->setAssocType(ASSOC_TYPE_GALLEY);
		$submissionFile->setAssocId($galley->getId());
		$submissionFile->setFileStage(SUBMISSION_FILE_PROOF);
		$submissionFile->setSubmissionId($submission->getId());
		$submissionFile->setFileType('application/pdf');
		$submissionFile->setFileSize(filesize($tmpFile));
		$submissionFile->setRevision(1);
		$submissionFile->setDateUploaded(Core::getCurrentDate());
		$submissionFile->setDateModified(Core::getCurrentDate());
		$submissionFile->setUploaderUserId($this->getRequest()->getUser()->getId());
		$submissionFile->setSubmissionLocale($galley->getLocale());

		// Set original filename, get it from the JATS XML file
		$jatsFileId = $publication->getData('jatsParser::fullTextFileId', $galley->getLocale());
		$jatsFile = $submissionFileDao->getLatestRevision($jatsFileId, null, $submission->getId());
		$filename = $jatsFile->getOriginalFileName() ?
			pathinfo($jatsFile->getOriginalFileName())['filename'] . '.pdf' :
			'document.pdf';
		$submissionFile->setOriginalFileName($filename);
		$submissionFile = $submissionFileDao->insertObject($submissionFile, $tmpFile);
		unlink($tmpFile);
		return $submissionFile;
	}

	/**
	 * @param Publication $publication
	 * @param string $locale
	 * @param string $htmlString
	 * @return string
	 * @brief set references for PDF galley
	 */
	private function _setReferences(Publication $publication, string $locale, string $htmlString): string {
		$rawCitations = $publication->getData('citationsRaw');
		if (empty($rawCitations)) return $htmlString;

		// Use OJS raw citations tokenizer
		import('lib.pkp.classes.citation.CitationListTokenizerFilter');
		$citationTokenizer = new CitationListTokenizerFilter();
		$citationStrings = $citationTokenizer->execute($rawCitations);

		if (!is_array($citationStrings) || empty($citationStrings)) return $htmlString;
		$htmlString .= '<h2 class="article-section-title" id="reference-title">' . __('submission.citations', null, $locale) . '</h2>';
		$htmlString .= "\n";
		$htmlString .= '<ol id="references">';
		$htmlString .= "\n";
		foreach ($citationStrings as $citationString) {
			$htmlString .= "\t";
			$htmlString .= '<li>' . $citationString . '</li>';
			$htmlString .= "\n";
		}
		$htmlString .= '</ol>';

		return $htmlString;
	}

	/**
	 * @param Journal $context Journal
	 * @return string
	 * @brief Retrieve citation style format that should be supported by citeproc-php
	 * use own format defined in settings if set
	 * use CitationStyleLanguagePlugin if set
	 * use vancouver style otherwise
	 */
	function getCitationStyle(Journal $context): string {

		$contextId = $context->getId();

		$citationStyle = $this->getSetting($contextId, 'citationStyle');

		if ($citationStyle) return $citationStyle;

		$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
		$cslPluginSettings = $pluginSettingsDAO->getPluginSettings($contextId, 'CitationStyleLanguagePlugin');

		if ($cslPluginSettings &&
			array_key_exists('enabled', $cslPluginSettings) &&
			$cslPluginSettings['enabled'] &&
			array_key_exists('primaryCitationStyle', $cslPluginSettings) &&
			$cslPrimaryCitStyle = $cslPluginSettings['primaryCitationStyle']
		) $citationStyle = $cslPrimaryCitStyle;

		if ($citationStyle) return $citationStyle;

		$lastCslKey = array_key_last(self::getSupportedCitationStyles());
		return self::getSupportedCitationStyles()[$lastCslKey]['id']; // vancouver
	}

	/**
	 * @param HTMLDocument $htmlDocument
	 * @param Publication $newPublication
	 * @return void
	 * @brief saves parsed citeproc references as raw citations
	 */
	private function _importCitations(HTMLDocument $htmlDocument, Publication $newPublication): void {
		$refs = $htmlDocument->getRawReferences();
		$publicationId = $newPublication->getId();
		$citationDao = DAORegistry::getDAO('CitationDAO'); /** @var $citationDao CitationDAO */

		$citationDao->deleteByPublicationId($publicationId);
		$rawCitations = '';

		foreach ($refs as $key => $ref) {
			$rawCitations .= $ref . "\n";
		}

		$newPublication->setData('citationsRaw', $rawCitations);
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @return HTMLDocument
	 * @brief retrieves PHP DOM representation of the article's full-text
	 */
	public function getFullTextFromJats (SubmissionFile $submissionFile): HTMLDocument {
		$htmlDocument = new HTMLDocument(new Document($submissionFile->getFilePath()));
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

				$html = '<p>' . $msg;
				foreach ($fullTexts as $localeKey => $fullText) {
					$html .= ' <a href="' . $request->url(null, 'user', 'setLocale', $localeKey) . '">' . $locales[$localeKey] . '</a>';
					if ($fullText !== end($fullTexts)) {
						$html .= ', ';
					} else {
						$html .= '.';
					}
				}
				$html .= '</p>';
			}

		} else if ($requestedOp === 'fullTextPreview') {
			$submissionFileId = $request->getUserVar('_full-text-preview');
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$submissionFile = $submissionFileDao->getLatestRevision($submissionFileId, SUBMISSION_FILE_PRODUCTION_READY, $submissionId);
			$html = $this->getFullTextFromJats($submissionFile)->saveAsHTML();
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
			if (get_class($dependentFile) !== 'SubmissionArtworkFile') continue;
			if (!in_array($dependentFile->getFileType(), self::getSupportedSupplFileTypes())) continue;
			$submissionId = $submissionFile->getSubmissionId();
			switch ($request->getRequestedOp()) {
				case 'view':
					$filePath = $request->url(null, 'article', 'downloadFullTextAssoc', array($submissionId, $dependentFile->getAssocId(), $dependentFile->getFileId()));
					break;
				case 'fullTextPreview':
					$submission = Services::get('submission')->get($submissionId);
					$submissionProps = Services::get('submission')->getProperties($submission, array('stageId'), array('request' => $request));
					$filePath = $request->url(null, 'workflow', 'downloadPreviewAssoc', array_merge(
						[$submissionId],
						$submissionProps,
						[$dependentFile->getAssocId(), $dependentFile->getFileId()]
					));
					break;
				case 'editPublication':
					// API Handler cannot process $op, $path or $anchor in url()
					$image = file_get_contents($dependentFile->getFilePath());
					$imageBase64 = base64_encode($image);
					$filePath = '@' . $imageBase64; // Format, supported by TCPDF
					break;
			}

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
		return [
			'image/png',
			'image/jpeg'
		];
	}

	public static function getSupportedCitationStyles() {
		return [
			[
				'id' => 'acm-sig-proceedings',
				'title' => 'plugins.generic.jatsParser.style.acm-sig-proceedings',
			],
			[
				'id' => 'acs-nano',
				'title' => 'plugins.generic.jatsParser.style.acs-nano',
			],
			[
				'id' => 'apa',
				'title' => 'plugins.generic.jatsParser.style.apa',
			],
			[
				'id' => 'associacao-brasileira-de-normas-tecnicas',
				'title' => 'plugins.generic.jatsParser.style.associacao-brasileira-de-normas-tecnicas',
			],
			[
				'id' => 'chicago-author-date',
				'title' => 'plugins.generic.jatsParser.style.chicago-author-date',
			],
			[
				'id' => 'harvard-cite-them-right',
				'title' => 'plugins.generic.jatsParser.style.harvard-cite-them-right',
			],
			[
				'id' => 'ieee',
				'title' => 'plugins.generic.jatsParser.style.ieee',
			],
			[
				'id' => 'modern-language-association',
				'title' => 'plugins.generic.jatsParser.style.modern-language-association',
			],
			[
				'id' => 'turabian-fullnote-bibliography',
				'title' => 'plugins.generic.jatsParser.style.turabian-fullnote-bibliography',
			],
			[
				'id' => 'vancouver',
				'title' => 'plugins.generic.jatsParser.style.vancouver',
			],
		];
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

	/**
	 * @return void
	 * @brief iterate through all submissions and add full-text from  galleys
	 */
	public function importGalleys() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$request = $this->getRequest();
		$context = $request->getContext();
		$user = $request->getUser();
		$publicationDao = DAORegistry::getDAO('PublicationDAO');

		$submissions = Services::get('submission')->getMany([
			'contextId' => $context->getId(),
			'stageIds' => [
				WORKFLOW_STAGE_ID_PRODUCTION
			]
		]);

		foreach ($submissions as $submission) {
			$publication = $submission->getCurrentPublication();
			$galleys = $publication->getData('galleys');

			if (empty($galleys)) continue;

			foreach ($galleys as $galley) {
				if (!in_array($galley->getFileType(), array("application/xml", "text/xml"))) continue;

				$galleyLocale = $galley->getLocale();
				$localizedFullTextFileSetting = $publication->getData('jatsParser::fullTextFileId', $galleyLocale);
				if ($localizedFullTextFileSetting) continue;

				$submissionFile = $galley->getFile();
				/** @var $submissionFile SubmissionFile */
				$document = new Document($submissionFile->getFilePath());
				if (empty($document->getArticleSections())) continue;

				$submissionFileManager = new SubmissionFileManager($context->getId(), $submissionFile->getSubmissionId());
				$newSubmissionFile = $submissionFileManager->copySubmissionFile($submissionFile->getFilePath(), SUBMISSION_FILE_PRODUCTION_READY, $user->getId(), null, $submissionFile->getGenreId());

				// copy and attach dependent files, only images are supported
				$assocFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $submissionFile->getId());
				foreach ($assocFiles as $assocFile) {
					/** @var $assocFile SubmissionFile */
					if (in_array($assocFile->getFileType(), $this->getSupportedSupplFileTypes())) {
						$newAssocFile = $submissionFileManager->copySubmissionFile($assocFile->getFilePath(), SUBMISSION_FILE_DEPENDENT, $user->getId(), null, $assocFile->getGenreId(), $assocFile->getAssocType(), $newSubmissionFile->getId());
						$newAssocFile->setOriginalFileName($assocFile->getOriginalFileName()); // should have preserved original file name to be retrieved for front-end correctly
						$submissionFileDao->updateObject($newAssocFile);
					}
				}

				// Convert and save as a Publication property
				$htmlDocument = new HTMLDocument($document);
				$htmlString = $htmlDocument->saveAsHTML();
				$publication->setData('jatsParser::fullTextFileId', $newSubmissionFile->getId(), $galleyLocale);
				$publication->setData('jatsParser::fullText', $htmlString, $galleyLocale);
				$publicationDao->updateObject($publication);
			}
		}
	}

	/**
	 * @param $hookName string Form::config::before
	 * @param $form FormComponent The form object
	 */
	public function addCitationsFormFields(string $hookName, FormComponent $form): void {
		if ($form->id !== 'citations' || !empty($form->errors)) return;

		$path = parse_url($form->action)['path'];
		if (!$path) return;

		$args = explode('/', $path);
		$publicationId = 0;
		if ($key = array_search('publications', $args)) {
			if (array_key_exists($key+1, $args)) {
				$publicationId = intval($args[$key+1]);
			}
		}

		if (!$publicationId) return;

		$publication = Services::get('publication')->get($publicationId);
		if (!$publication) return;

		$submissionFileIds = array_unique($publication->getData('jatsParser::fullTextFileId'));
		if (empty($submissionFileIds)) return;

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		$submissionFiles = [];
		foreach ($submissionFileIds as $submissionFileId) {
			// Check if file ID is valid and object can be returned
			if ($submissionFile = $submissionFileDao->getLatestRevision($submissionFileId)) {
				$submissionFiles[] = $submissionFile;
			}
		}

		if (empty($submissionFiles)) return;

		$options = [];
		foreach ($submissionFiles as $submissionFile) {
			$options[] = [
				'value' => $submissionFile->getId(),
				'label' => $submissionFile->getLocalizedName(),
			];
		}

		$options[] = [
			'value' => null,
			'label' => __('common.default'),
		];

		$form->addField(new \PKP\components\forms\FieldOptions('jatsParser::references', [
			'label' => __('plugins.generic.jatsParser.publication.jats.references.label'),
			'description' => __('plugins.generic.jatsParser.publication.jats.references.description'),
			'type' => 'radio',
			'options' => $options,
			'value' => null
		]));

	}
}
