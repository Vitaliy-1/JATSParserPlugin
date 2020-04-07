<?php

/**
 * @file plugins/generic/jatsParser/JatsParserHandler.inc.php
 *
 * Copyright (c) 2016-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsParserHandler
 * @ingroup controllers_jatsParserHandler
 *
 * @brief Handle article JATS XML galley requests.
 */

// import grid base classes
import('classes.handler.Handler');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class JatsParserHandler extends Handler {

	protected $_plugin;

	function __construct() {
		parent::__construct();
		$this->_plugin = PluginRegistry::getPlugin('generic', JATSPARSER_PLUGIN_NAME);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('settings', 'updateGalleySettings'));

	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$this->_request = $request;

		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', WORKFLOW_STAGE_ID_PRODUCTION));

		if ($request->getUserVar('representationId')) {
			import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
			$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}


	/**
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage
	 */
	function settings($args, $request) {
		$this->setupTemplate($request);
		$galleyId = $request->getUserVar('galleyId');
		$submissionId = $request->getUserVar('submissionId');
		$publicationId = $request->getUserVar('publicationId');
		$context = $request->getContext();

		$dispatcher = $request->getDispatcher();
		$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'submissions/'. $submissionId . "/publications/" . $publicationId);
		$successMessage = __('admin.contexts.form.edit.success');
		$supportedLocales = $context->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedLocales);

		$galley = Services::get('galley')->get($galleyId);
		$publication = Services::get('publication')->get($publicationId);

		import('plugins.generic.jatsParser.controllers.form.JatsParserGalleyForm');
		$jatsParserGalleyForm = new \Plugins\generic\jatsParser\form\JatsParserGalleyForm($apiUrl, $successMessage, $locales, $publication, $galley);
		$jatsParserGalleyFormConfig = $jatsParserGalleyForm->getConfig();

		$containerData = [
			'components' => [
				FORM_JATSPARSER_GALLEY => $jatsParserGalleyFormConfig,
			],
		];

		$templateMgr = TemplateManager::getManager($request);

		$templateMgr->assign(array(
			'containerData' => $containerData
		));

		$plugin = PluginRegistry::getPlugin('generic', 'jatsparserplugin');

		return new JSONMessage(true, $templateMgr->fetch($plugin->getTemplateResource('controllers/jatsParserGalleySettings/jatsParserGalleySettings.tpl')));
	}

}
