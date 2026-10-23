<?php
/**
 * @file plugins/generic/jatsParser/JatsParserSettingsForm.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko, MD
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserSettingsForm
 * @ingroup plugins_generic_jatsParser
 *
 * @brief Form for journal managers to modify jatsParser plugin settings
 */

namespace APP\plugins\generic\jatsParser;

use PKP\form\Form;

class JatsParserSettingsForm extends Form
{
    /** @var int */
    private int $_journalId;

    /** @var JatsParserPlugin */
    private $_plugin;

    public function __construct($plugin, int $journalId)
    {
        $this->_journalId = $journalId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    public function initData(): void
    {
        $contextId = $this->_journalId;
        $plugin = $this->_plugin;

        $this->setData('convertToPdf', $plugin->getSetting($contextId, 'convertToPdf'));
    }

    public function readInputData(): void
    {
        $this->readUserVars(['convertToPdf', 'galleysImport']);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = \APP\template\TemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->_plugin->getName(),
        ]);
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $plugin = $this->_plugin;
        $contextId = $this->_journalId;

        $convertToPdf = (bool) $this->getData('convertToPdf');
        $plugin->updateSetting($contextId, 'convertToPdf', $convertToPdf);

        if ($this->getData('galleysImport')) {
            $plugin->importGalleys();
        }

        parent::execute(...$functionArgs);
    }
}
