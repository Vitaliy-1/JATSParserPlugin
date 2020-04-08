<?php


/**
* @file plugins/generic/jatsParser/JatsParserSettingsForm.inc.php
*
 * Copyright (c) 2017-2018 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
* Distributed under the GNU GPL v3.
 *
 * @class JatsParserSettingsForm
 * @ingroup plugins_generic_jatsParser
*
 * @brief Form for journal managers to modify jatsParser plugin settings
*/

import('lib.pkp.classes.form.Form');

class JatsParserSettingsForm extends Form {

	/** @var int */
	var $_journalId;

	/** @var object */
	var $_plugin;

	/**
	 * Constructor
	 * @param $plugin JATSParserPlugin
	 * @param $journalId int
	 */
	function __construct($plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$contextId = $this->_journalId ;
		$plugin = $this->_plugin;

		$this->setData('references', $plugin->getSetting($contextId, 'references'));
		$this->setData('convertToPdf', $plugin->getSetting($contextId, 'convertToPdf'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('references', 'convertToPdf'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin = $this->_plugin;
		$contextId = $this->_journalId ;

		$plugin->updateSetting($contextId, 'references', $this->getData('references'));

		$convertToPdf = $this->getData('convertToPdf');
		if (!$convertToPdf) {
			$convertToPdf = false;
		} else {
			$convertToPdf = true;
		}
		$plugin->updateSetting($contextId, 'convertToPdf', $convertToPdf);
	}
}
