<?php
/**
 * @file plugins/generic/jatsParser/JatsParserWorkflowHandler.php
 *
 * Copyright (c) 2017-2024 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserWorkflowHandler
 * @ingroup plugins_generic_jatsParser
 *
 * @brief Handles AJAX requests for the JATSParser workflow tab.
 *
 * XML files are taken from the Production stage (PRODUCTION_READY),
 * not from the native OJS JATS tab.
 */

namespace APP\plugins\generic\jatsParser;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\Role;
use PKP\submission\Genre;
use PKP\submissionFile\SubmissionFile;

class JatsParserWorkflowHandler extends Handler
{
    /** @var JatsParserPlugin */
    private JatsParserPlugin $_plugin;

    public function __construct(string $pluginName)
    {
        $this->_plugin = PluginRegistry::getPlugin('generic', $pluginName);
        parent::__construct();

        $this->addRoleAssignment(
            [
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ],
            ['processSubmission', 'clearFullText', 'getWorkflowFiles']
        );
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextRequiredPolicy($request));

        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);
        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Process a Production-Ready XML file for a given publication.
     *
     * The file to process is taken (in order of priority):
     *  1. `fileId` POST param — explicit selection from the UI
     *  2. Previously stored `jatsParser::fullTextFileId` for the locale (re-process)
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function processSubmission(array $args, $request): JSONMessage
    {
        if (!$request->isPost() || !$request->checkCSRF()) {
            return new JSONMessage(false, __('api.400.badRequest'));
        }

        $submissionId  = (int) $request->getUserVar('submissionId');
        $publicationId = (int) $request->getUserVar('publicationId');
        $requestedFileId = (int) $request->getUserVar('fileId'); // explicit selection

        if (!$submissionId || !$publicationId) {
            return new JSONMessage(false, __('api.400.badRequest'));
        }

        $submission = Repo::submission()->get($submissionId);
        if (!$submission) {
            return new JSONMessage(false, __('api.404.resourceNotFound'));
        }

        $publication = Repo::publication()->get($publicationId);
        if (!$publication || $publication->getData('submissionId') !== $submission->getId()) {
            return new JSONMessage(false, __('api.404.resourceNotFound'));
        }

        $locale = $submission->getData('locale');
        $storedFileIds = (array) ($publication->getData('jatsParser::fullTextFileId') ?? []);

        // 1. Explicit file selected from the UI
        $submissionFile = null;
        if ($requestedFileId) {
            $submissionFile = Repo::submissionFile()->get($requestedFileId);
        }

        // 2. Previously stored file (re-process without changing the source)
        if (!$submissionFile && !empty($storedFileIds[$locale])) {
            $submissionFile = Repo::submissionFile()->get((int) $storedFileIds[$locale]);
        }

        if (!$submissionFile) {
            return new JSONMessage(false, '', '0', ['errorCode' => 'noJatsFile']);
        }

        try {
            $htmlDocument = $this->_plugin->getFullTextFromJats($submissionFile);
        } catch (\Throwable $e) {
            error_log('JATSParser processSubmission error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return new JSONMessage(false, '', '0', ['errorCode' => 'processError', 'errorMessage' => $e->getMessage()]);
        }

        Repo::publication()->edit($publication, [
            'jatsParser::fullTextFileId' => array_merge(
                $storedFileIds,
                [$locale => $submissionFile->getId()]
            ),
            'jatsParser::fullText' => array_merge(
                (array) ($publication->getData('jatsParser::fullText') ?? []),
                [$locale => $htmlDocument->saveAsHTML()]
            ),
        ]);

        return new JSONMessage(true, [
            'fileId' => $submissionFile->getId(),
            'locale' => $locale,
        ]);
    }

    /**
     * Clear the JATS full-text data for a given publication.
     * Removes the generated HTML and the source file reference from
     * publication_settings; the original XML file is not deleted.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function clearFullText(array $args, $request): JSONMessage
    {
        if (!$request->isPost() || !$request->checkCSRF()) {
            return new JSONMessage(false, __('api.400.badRequest'));
        }

        $submissionId  = (int) $request->getUserVar('submissionId');
        $publicationId = (int) $request->getUserVar('publicationId');

        if (!$submissionId || !$publicationId) {
            return new JSONMessage(false, __('api.400.badRequest'));
        }

        $submission = Repo::submission()->get($submissionId);
        if (!$submission) {
            return new JSONMessage(false, __('api.404.resourceNotFound'));
        }

        $publication = Repo::publication()->get($publicationId);
        if (!$publication || $publication->getData('submissionId') !== $submission->getId()) {
            return new JSONMessage(false, __('api.404.resourceNotFound'));
        }

        $locale = $submission->getData('locale');

        $fullTextFileIds = (array) ($publication->getData('jatsParser::fullTextFileId') ?? []);
        unset($fullTextFileIds[$locale]);

        $fullTexts = (array) ($publication->getData('jatsParser::fullText') ?? []);
        unset($fullTexts[$locale]);

        Repo::publication()->edit($publication, [
            'jatsParser::fullTextFileId' => empty($fullTextFileIds) ? null : $fullTextFileIds,
            'jatsParser::fullText'       => empty($fullTexts)       ? null : $fullTexts,
        ]);

        return new JSONMessage(true, ['locale' => $locale]);
    }

    /**
     * Return the Production-Ready XML files available for this submission,
     * plus the image assets attached to the currently processed file.
     *
     * Response content:
     *  - productionFiles : [{id, name, isSelected}]
     *  - assets          : [{id, name, mimetype}]
     *  - selectedFileId  : int|null
     *  - selectedFileName: string|null
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function getWorkflowFiles(array $args, $request): JSONMessage
    {
        $submissionId  = (int) $request->getUserVar('submissionId');
        $publicationId = (int) $request->getUserVar('publicationId');

        if (!$submissionId || !$publicationId) {
            return new JSONMessage(false, __('api.400.badRequest'));
        }

        $submission = Repo::submission()->get($submissionId);
        if (!$submission) {
            return new JSONMessage(false, __('api.404.resourceNotFound'));
        }

        $publication = Repo::publication()->get($publicationId);
        if (!$publication || $publication->getData('submissionId') !== $submission->getId()) {
            return new JSONMessage(false, __('api.404.resourceNotFound'));
        }

        $locale        = $submission->getData('locale');
        $storedFileIds = (array) ($publication->getData('jatsParser::fullTextFileId') ?? []);
        $selectedFileId = $storedFileIds[$locale] ?? null;

        // ── Production-Ready XML files ────────────────────────────────────────
        $productionFiles = [];
        $allProductionFiles = Repo::submissionFile()
            ->getCollector()
            ->filterBySubmissionIds([$submissionId])
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY])
            ->getMany();

        foreach ($allProductionFiles as $file) {
            $mime = $file->getData('mimetype');
            if (!in_array($mime, ['application/xml', 'text/xml', 'application/jats+xml'])) {
                continue;
            }
            $productionFiles[] = [
                'id'         => $file->getId(),
                'name'       => $file->getLocalizedData('name'),
                'isSelected' => ($file->getId() == $selectedFileId),
            ];
        }

        // ── Assets (dependent image files) of the currently processed file ────
        $assets            = [];
        $selectedFileName  = null;

        if ($selectedFileId) {
            $selectedFile = Repo::submissionFile()->get((int) $selectedFileId);
            if ($selectedFile) {
                $selectedFileName = $selectedFile->getLocalizedData('name');
                $genreDao = DAORegistry::getDAO('GenreDAO');

                $dependentFiles = Repo::submissionFile()
                    ->getCollector()
                    ->filterByAssoc(Application::ASSOC_TYPE_SUBMISSION_FILE, [(int) $selectedFileId])
                    ->filterBySubmissionIds([$submissionId])
                    ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
                    ->includeDependentFiles()
                    ->getMany();

                foreach ($dependentFiles as $depFile) {
                    $genre = $genreDao->getById($depFile->getData('genreId'));
                    if (!$genre || $genre->getCategory() !== Genre::GENRE_CATEGORY_ARTWORK) {
                        continue;
                    }
                    $assets[] = [
                        'id'       => $depFile->getId(),
                        'name'     => $depFile->getLocalizedData('name'),
                        'mimetype' => $depFile->getData('mimetype'),
                    ];
                }
            }
        }

        return new JSONMessage(true, [
            'productionFiles'  => $productionFiles,
            'assets'           => $assets,
            'selectedFileId'   => $selectedFileId,
            'selectedFileName' => $selectedFileName,
        ]);
    }
}
