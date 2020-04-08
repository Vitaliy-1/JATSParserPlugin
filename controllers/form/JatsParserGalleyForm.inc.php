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

namespace Plugins\generic\jatsParser\form;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;

define('FORM_JATSPARSER_GALLEY', 'jatsParser');

class JatsParserGalleyForm extends FormComponent {

	public $id = FORM_JATSPARSER_GALLEY;
	public $method = 'PUT';

	public function __construct($action, $successMessage, $locales, $publication, $galley) {
		$this->action = $action;
		$this->successMessage = $successMessage;
		$this->locales = $locales;
		$isDefault = false;
		$defaultGalleyId = (int) $publication->getData("jatsparser::defaultGalleyId", $galley->getLocale());
		if ($defaultGalleyId == $galley->getId()) {
			$isDefault = true;
		}

		$fieldOptions = new FieldOptions('jatsparser::defaultGalleyId', array());
		$fieldOptions->label = __('plugins.generic.jatsParser.galley.settings.display');
		$fieldOptions->description = __('plugins.generic.jatsParser.galley.settings.display.description');
		$fieldOptions->options[] = array(
			'value' => $galley->getId(),
			'label' => __('common.enable')
		);
		$fieldOptions->value[] = array(
			'en_US' => $publication->getData("jatsparser::defaultGalleyId"),
			'uk_UA' => $publication->getData("jatsparser::defaultGalleyId")
		);

		$this->addField($fieldOptions);

	}
}
