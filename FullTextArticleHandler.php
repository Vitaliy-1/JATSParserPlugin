<?php
/**
 * @file plugins/generic/jatsParser/FullTextArticleHandler.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class FullTextArticleHandler
 * @ingroup plugins_generic_jatsParser
 */

namespace APP\plugins\generic\jatsParser;

use APP\facades\Repo;
use APP\pages\article\ArticleHandler;
use PKP\plugins\PluginRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FullTextArticleHandler extends ArticleHandler
{
    private string $pluginName;

    /** @var JatsParserPlugin */
    private $_plugin;

    public function __construct(string $pluginName)
    {
        $this->pluginName = $pluginName;
        $this->_plugin = PluginRegistry::getPlugin('generic', $pluginName);
        parent::__construct();
    }

    /**
     * @param array $args
     * @param \APP\core\Request $request
     * @brief download supplementary files for article's full-text
     */
    public function downloadFullTextAssoc(array $args, $request): void
    {
        $fileId = $args[2];
        if (empty($fileId) || !$this->article || !$this->publication) {
            throw new NotFoundHttpException();
        }

        if (!$this->userCanViewGalley($request, $this->article->getId())) {
            header('HTTP/1.0 403 Forbidden');
            echo '403 Forbidden<br>';
            exit;
        }

        $fullTextFileIds = $this->publication->getData('jatsParser::fullTextFileId');
        if (empty($fullTextFileIds)) {
            throw new NotFoundHttpException();
        }

        // Find if the file is an image dependent from the XML file, from which full-text was generated.
        $dependentFilesIterator = Repo::submissionFile()
            ->getCollector()
            ->filterByAssoc(\APP\core\Application::ASSOC_TYPE_SUBMISSION_FILE, array_values($fullTextFileIds))
            ->filterBySubmissionIds([$this->article->getId()])
            ->filterByFileStages([\PKP\submissionFile\SubmissionFile::SUBMISSION_FILE_DEPENDENT])
            ->includeDependentFiles()
            ->getMany();

        if ($dependentFilesIterator->isEmpty()) {
            throw new NotFoundHttpException();
        }

        $submissionFile = null;
        foreach ($dependentFilesIterator as $dependentFile) {
            if ($fileId == $dependentFile->getData('fileId')) {
                $submissionFile = $dependentFile;
                break;
            }
        }

        if (!$submissionFile) {
            throw new NotFoundHttpException();
        }

        if (!in_array($submissionFile->getData('mimetype'), JatsParserPlugin::getSupportedSupplFileTypes())) {
            throw new NotFoundHttpException();
        }

        // Download file if exists
        if (!app()->get('file')->fs->has($submissionFile->getData('path'))) {
            throw new NotFoundHttpException();
        }

        $filename = app()->get('file')->formatFilename($submissionFile->getData('path'), $submissionFile->getLocalizedData('name'));
        app()->get('file')->download($submissionFile->getData('fileId'), $filename);
    }
}
