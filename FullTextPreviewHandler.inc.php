<?php

/**
 * @file plugins/generic/docxConverter/DOCXConverterHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University Library
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2.
 *
 * @brief handler for the grid's conversion
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
			array('fullTextPreview')
		);
	}

	public function fullTextPreview($args, $request) {
		$submissionId = $args[0];
		$fileId = $request->getUserVar('_full-text-preview');
		$submission = Services::get('submission')->get($submissionId);
		$publication = $submission->getLatestPublication();
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		$templateMgr->assign(array(
			'article' => $submission,
			'publication' => $publication,
			'currentPublication' => $publication
		));
		$templateMgr->display('frontend/pages/article.tpl');
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
