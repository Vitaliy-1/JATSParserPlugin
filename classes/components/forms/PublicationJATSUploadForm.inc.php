<?php

use PKP\components\forms\FieldHTML;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldRadioInput;
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
	 * @param $jatsUploadUrl string URL to upload files to
	 */
	public function __construct($action, $locales, $publication, $submissionFiles) {
		/**
		 * @var $submissionFile SubmissionFile
		 */
		$this->action = $action;
		$this->successMessage = __('plugins.generic.jatsParser.publication.jats.fulltext.success');
		$this->locales = $locales;

		$options = array();
		foreach ($submissionFiles as $submissionFile) {
			$htmlDocument = new HTMLDocument(new JATSDocument($submissionFile->getFilePath()), false);
			$options[] = array(
				'value' => $htmlDocument,
				'label' => $submissionFile->getLocalizedName()
			);
		}

		$this->addField(new FieldRadioInput('jatsFulltext', [
			'label' => __('plugins.generic.jatsParser.publication.jats.label'),
			'isMultilingual' => true,
			'type' => 'radio',
			'options' => $options,
			'value' => $publication->getData('jatsParser::fulltext')
		]))
		->addField(new FieldHTML("preview", array(
			'description' => "<div>Just a test</div>",
			'isMultilingual' => true
		)));

	}

}
