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

	/**
	 * @copydoc PKPHandler::authorize()
	 */


	public function fullTextPreview($args, $request) {
		/** @var $request Request */
	}
}
