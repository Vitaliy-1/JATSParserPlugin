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

		$this->setData('convertToPdf', $plugin->getSetting($contextId, 'convertToPdf'));
		$this->setData('citationStyle', $plugin->getSetting($contextId, 'citationStyle'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('convertToPdf', 'citationStyle', 'customStyleInput', 'galleysImport'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'pluginName' => $this->_plugin->getName(),
			'citationStyles' => $this->_plugin::getSupportedCitationStyles()
		]);
		return parent::fetch($request, $template, $display);
	}

	/**
	 * Save settings.
	 */
	function execute(...$functionArgs) {
		$plugin = $this->_plugin;
		$contextId = $this->_journalId ;

		$convertToPdf = $this->getData('convertToPdf');
		if (!$convertToPdf) {
			$convertToPdf = false;
		} else {
			$convertToPdf = true;
		}
		$plugin->updateSetting($contextId, 'convertToPdf', $convertToPdf);

		// Citation Style Format
		$citationStyle = $this->getData('citationStyle');
		if ($citationStyle == 'customStyle') {
			$plugin->updateSetting($contextId, 'citationStyle', $this->getData('customStyleInput'));
		} else {
			$plugin->updateSetting($contextId, 'citationStyle', $this->getData('citationStyle'));
		}

		// Import galleys
		if ($importGalleys = $this->getData('galleysImport')) {
			$plugin->importGalleys();
		}

		parent::execute(...$functionArgs);
	}
}
