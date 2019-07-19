<?php

/**
 * @file controllers/form/JatsParserGalleyForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsParserGalleyForm
 *
 * @brief JATS Parser plugin galley editing form.
 */

import('lib.pkp.classes.form.Form');

class JatsParserGalleyForm extends Form {

	protected $_request;
	protected $_articleGalley;
	protected $_submissionId;

	public function __construct($request, $plugin) {
		parent::__construct($plugin->getTemplateResource('controllers/jatsParserGalleySettings/jatsParserGalleySettings.tpl'));

		$this->_request = $request;

		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleyId = $this->_request->getUserVar('galleyId');
		$this->_submissionId = $this->_request->getUserVar('submissionId');
		$this->_articleGalley = $articleGalleyDao->getById($galleyId);

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	function initData() {
		$this->setData(array(
			'jatsParserDisplayDefaultXml' => $this->_articleGalley->getData('jatsParserDisplayDefaultXml')
		));

		parent::initData();
	}

	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('jatsParserDisplayDefaultXml'));
	}

	function execute() {
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$currentGalley = $this->_articleGalley;

		$displayDefaultXml = $this->getData('jatsParserDisplayDefaultXml');

		// Update settings for other galley from this submission that have jatsParserDisplayDefaultXml 'On'
		if ($displayDefaultXml) {
			$galleyDaoFactory = $articleGalleyDao->getGalleysBySetting('jatsParserDisplayDefaultXml', 'On', $this->_submissionId);
			while ($galley = $galleyDaoFactory->next()) {
				if (($galley->getId() != $currentGalley->getId()) && ($galley->getData('jatsParserDisplayDefaultXml'))) {
					$galley->setData('jatsParserDisplayDefaultXml', null);
					$articleGalleyDao->updateLocaleFields($galley);
				}
			}
		}

		$currentGalley->setData('jatsParserDisplayDefaultXml', $displayDefaultXml);

		$articleGalleyDao->updateLocaleFields($currentGalley);

		return parent::execute();
	}
}
