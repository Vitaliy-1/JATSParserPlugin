<?php

import('pages.article.ArticleHandler');

class FullTextArticleHandler extends ArticleHandler {

	var $_plugin;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->_plugin = PluginRegistry::getPlugin('generic', JATSPARSER_PLUGIN_NAME);
	}

	/**
	 * @param $args
	 * @param $request
	 * @brief download supplementary files for article's full-text
	 */
	function downloadFullTextAssoc($args, $request) {
		$fileId = $args[2];
		$dispatcher = $request->getDispatcher();
		if (empty($fileId) || !$this->article || !$this->publication) $dispatcher->handle404();

		$fullTextFileIds = $this->publication->getData('jatsParser::fullTextFileId');
		if (empty($fullTextFileIds)) $dispatcher->handle404();

		// Find if the file is an image dependent from the XML file, from which full-text was generated.
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$dependentFiles = [];
		foreach ($fullTextFileIds as $fullTextFileId) {
			$dependentFilesArray = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $fullTextFileId, $this->article->getId(), SUBMISSION_FILE_DEPENDENT);
			$dependentFiles = array_merge($dependentFiles, $dependentFilesArray);
		}

		if (empty($dependentFiles)) $dispatcher->handler404();

		$submissionFile = null;
		foreach ($dependentFiles as $dependentFile) {
			 if ($fileId == $dependentFile->getFileId()) {
				 $submissionFile = $dependentFile;
			 	break;
			 }
		}

		if (!$submissionFile) $dispatcher->handle404();

		if (!in_array($submissionFile->getFileType(), $this->_plugin::getSupportedSupplFileTypes())) $dispatcher->handler404();

		// Download file
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($this->article->getContextId(), $this->article->getId());
		$submissionFileManager->downloadById($fileId);
	}
}
