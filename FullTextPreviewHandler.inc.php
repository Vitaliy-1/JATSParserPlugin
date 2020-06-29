<?php

/**
 * @file plugins/generic/jatsParser/FullTextPreviewHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University Library
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2.
 *
 * @brief handler for the full-text preview page
 */

import('classes.handler.Handler');
import('pages.workflow.WorkflowHandler');

class FullTextPreviewHandler extends WorkflowHandler {

	var $_plugin;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->_plugin = PluginRegistry::getPlugin('generic', JATSPARSER_PLUGIN_NAME);
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('fullTextPreview', 'downloadPreviewAssoc')
		);
	}

	/**
	 * @param $args array
	 * @param $request Request;
	 * @brief handle request for full-text preview
	 */
	public function fullTextPreview($args, $request) {
		$submissionId = $args[0];
		$fileId = $request->getUserVar('_full-text-preview');
		$submission = Services::get('submission')->get($submissionId);
		$publication = $submission->getLatestPublication();
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFile = $submissionFileDao->getLatestRevision($fileId, SUBMISSION_FILE_PRODUCTION_READY, $submissionId);

		$dispatcher = $request->getDispatcher();
		if (!$submissionFile) $dispatcher->handle404();

		$templateMgr->assign(array(
			'article' => $submission,
			'publication' => $publication,
			'currentPublication' => $publication,
			'firstPublication' => reset($submission->getData('publications')),
		));
		$templateMgr->display('frontend/pages/article.tpl');
	}

	/**
	 * @param $args
	 * @param $request
	 * @brief download supplementary files for article's preview
	 */
	function downloadPreviewAssoc($args, $request) {
		$submissionId = $args[0];
		$dependentFileAssocId = $args[2];
		$dependentFileId = $args[3];
		$dispatcher = $request->getDispatcher();

		if (!$dependentFileId) $dispatcher->handle404();

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFile = $submissionFileDao->getLatestRevision($dependentFileAssocId, SUBMISSION_FILE_PRODUCTION_READY, $submissionId);
		$submissionFileDependent = $submissionFileDao->getLatestRevision($dependentFileId, SUBMISSION_FILE_DEPENDENT, $submissionId);

		if (!is_a($submissionFile, 'SubmissionFile') || !is_a($submissionFileDependent, 'SubmissionFile')) $dispatcher->handle404();

		// Verify that the file is dependant from the one specified
		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $dependentFileAssocId, $submissionId, SUBMISSION_FILE_DEPENDENT);

		if (empty($dependentFiles)) $dispatcher->handle404();
		$currentDependentFile = null;
		foreach ($dependentFiles as $dependentFile) {
			if ($dependentFileId == $dependentFile->getFileId()) {
				$currentDependentFile = $dependentFile;
				break;
			}
		}
		if (!$currentDependentFile) $dispatcher->handle404();

		if (!in_array($currentDependentFile->getFileType(), $this->_plugin::getSupportedSupplFileTypes())) $dispatcher->handler404();

		// Download file
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($request->getContext()->getId(), $submissionId);
		$submissionFileManager->downloadById($dependentFileId);

	}

	/**
	 * Set up the template. (Load required locale components.)
	 * @param $request PKPRequest
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_PKP_SUBMISSION);
	}
}
