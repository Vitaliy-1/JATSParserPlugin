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

	/* @var $_request Request */
	protected $_request;

	/* @var $_articleGalley ArticleGalley */
	protected $_articleGalley;

	/* @var $_submissionId int */
	protected $_submissionId;

	/* @var $_publication Publication */
	protected $_publication;

	public function __construct($request, $plugin) {
		parent::__construct($plugin->getTemplateResource('controllers/jatsParserGalleySettings/jatsParserGalleySettings.tpl'));

		$this->_request = $request;

		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleyId = $this->_request->getUserVar('galleyId');
		$this->_submissionId = $this->_request->getUserVar('submissionId');
		$this->_articleGalley = $articleGalleyDao->getById($galleyId);

		$jatsParserPublicationDao = DAORegistry::getDAO('JatsParserPublicationDAO'); /* @var $jatsParserPublicationDao JatsParserPublicationDAO */
		$this->_publication = $jatsParserPublicationDao->getByGalleyId($this->_articleGalley->getId());

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	function initData() {
		$galleyLocale = $this->_articleGalley->getLocale();

		$defaultGalleyId = $this->_publication->getLocalizedData("jatsparser::defaultGalley", $galleyLocale);

		if ($defaultGalleyId == $this->_articleGalley->getId()) {
			$this->setData('jatsParserDisplayDefaultXml', 1);
		} else {
			$this->setData('jatsParserDisplayDefaultXml', 0);
		}

		//$this->setData('jatsParserDisplayDefaultXml', $this->_articleGalley->getData('jatsParserDisplayDefaultXml', $galleyLocale));

		parent::initData();
	}

	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('jatsParserDisplayDefaultXml'));
	}

	function execute() {
		$jatsParserPublicationDao = DAORegistry::getDAO('JatsParserPublicationDAO'); /* @var $jatsParserPublicationDao JatsParserPublicationDAO */
		$currentGalley = $this->_articleGalley;
		$defaultGalleyId = (int) $this->_publication->getLocalizedData("jatsparser::defaultGalley", $currentGalley->getLocale());

		$displayDefaultXml = $this->getData('jatsParserDisplayDefaultXml');

		if ($displayDefaultXml) {
			$jatsParserPublicationDao->changeJatsParserSetting($this->_publication->getId(), "defaultGalley", (int) $this->_articleGalley->getId(), $currentGalley->getLocale());
		} else if ($defaultGalleyId == $this->_articleGalley->getId()) {
			$jatsParserPublicationDao->deleteJatsParserSetting($this->_publication->getId(), "defaultGalley", $currentGalley->getLocale());
		}

		return parent::execute();
	}
}
