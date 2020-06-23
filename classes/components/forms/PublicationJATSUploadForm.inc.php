<?php

use PKP\components\forms\FieldHTML;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

define("FORM_PUBLICATION_JATS_FULLTEXT", "jatsUpload");

class PublicationJATSUploadForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_PUBLICATION_JATS_FULLTEXT;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $publication \Publication publication to change settings for
	 * @param $submissionFiles array of SubmissionFile with xml type
	 */
	public function __construct($action, $locales, $publication, $submissionFiles, $msg) {
		/**
		 * @var $submissionFile SubmissionFile
		 */
		$this->action = $action;
		$this->successMessage = __('plugins.generic.jatsParser.publication.jats.fulltext.success');
		$this->locales = $locales;

		$options = array();
		foreach ($locales as $value) {
			$locale = $value['key'];
			$lang = array();
			if (empty($submissionFiles)) break;
			foreach ($submissionFiles as $submissionFile) {
				$subName = $submissionFile->getName($locale);
				if (empty($subName)) {
					$subName = $submissionFile->getLocalizedName();
				}
				$lang[] = array(
					'value' => $submissionFile->getId(),
					'label' => $subName
				);

			}

			$lang[] = array(
				'value' => null,
				'label' => __('common.default')
			);

			$options[$locale] = $lang;
		}

		if (!empty($options)) {
			$this->addField(new FieldOptions('jatsParser::fullTextFileId', [
				'label' => __('plugins.generic.jatsParser.publication.jats.label'),
				'description' => $msg,
				'isMultilingual' => true,
				'type' => 'radio',
				'options' => $options,
				'value' => $publication->getData('jatsParser::fullTextFileId'),
			]));
		} else {
			$this->addField(new FieldHTML("preview", array(
				'description' => $msg
			)));
		}
	}
}
