<?php
/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserPlugin
 * @ingroup plugins_generic_jatsParser
 */

namespace APP\plugins\generic\jatsParser;

require_once __DIR__ . '/JATSParser/vendor/autoload.php';

use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\citation\CitationListTokenizerFilter;
use PKP\config\Config;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\file\PrivateFileManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\submission\Genre;
use PKP\submissionFile\SubmissionFile;
use PKP\userGroup\UserGroup;
use JATSParser\Body\Document;
use JATSParser\PDF\TCPDFDocument;
use JATSParser\HTML\Document as HTMLDocument;
use PKP\components\forms\FormComponent;

define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled()) {
                Hook::add('Schema::get::publication', [$this, 'addToSchema']);
                Hook::add('LoadHandler', [$this, 'loadFullTextAssocHandler']);
                Hook::add('Templates::Article::Main', [$this, 'displayFullText']);
                Hook::add('TemplateManager::display', [$this, 'themeSpecificStyles']);
                Hook::add('TemplateManager::display', [$this, 'addWorkflowComponent']);
                Hook::add('Form::config::before', [$this, 'addCitationsFormFields']);
                Hook::add('Publication::edit', [$this, 'editPublicationReferences']);
                Hook::add('Publication::edit', [$this, 'createPdfGalley'], Hook::SEQUENCE_LAST);
            }
            return true;
        }
        return false;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.jatsParser.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.jatsParser.description');
    }

    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new \PKP\linkAction\LinkAction(
                    'settings',
                    new \PKP\linkAction\request\AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            parent::getActions($request, $verb)
        );
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $form = new JatsParserSettingsForm($this, $context->getId());
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new \PKP\core\JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new \PKP\core\JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    /**
     * Add a property to the publication schema
     *
     * @param string $hookName `Schema::get::publication`
     * @param array $args
     */
    public function addToSchema(string $hookName, array $args): bool
    {
        $schema = $args[0];
        $propId = '{
            "type": "integer",
            "multilingual": true,
            "apiSummary": true,
            "validation": [
                "nullable"
            ]
        }';
        $propText = '{
            "type": "string",
            "multilingual": true,
            "apiSummary": true,
            "validation": [
                "nullable"
            ]
        }';
        $schema->properties->{'jatsParser::fullTextFileId'} = json_decode($propId);
        $schema->properties->{'jatsParser::fullText'} = json_decode($propText);
        return Hook::CONTINUE;
    }

    /**
     * Handle associated files of the full-text, only images are supported
     *
     * @param string $hookName
     * @param array $args [string $page, string $op, string $sourceFile, mixed $handler]
     */
    public function loadFullTextAssocHandler(string $hookName, array $args): bool
    {
        $page = $args[0];
        $op = $args[1];

        if ($page === 'article' && $op === 'downloadFullTextAssoc') {
            $args[3] = new FullTextArticleHandler($this->getName());
            return Hook::ABORT;
        }

        if ($page === 'jatsParser' && in_array($op, ['processSubmission', 'clearFullText', 'getWorkflowFiles'])) {
            $args[3] = new JatsParserWorkflowHandler($this->getName());
            return Hook::ABORT;
        }

        return Hook::CONTINUE;
    }

    /**
     * Auto-process a JATS file when it is uploaded via the native JATS tab
     *
     * @param string $hookName
     * @param array $args [$submissionFile]
     */
    public function onJatsFileAdded(string $hookName, array $args): bool
    {
        /** @var SubmissionFile $submissionFile */
        $submissionFile = $args[0];

        if ($submissionFile->getData('fileStage') !== SubmissionFile::SUBMISSION_FILE_JATS) {
            return Hook::CONTINUE;
        }

        $publicationId = $submissionFile->getData('assocId');
        $publication = Repo::publication()->get($publicationId);
        if (!$publication) {
            return Hook::CONTINUE;
        }

        try {
            $htmlDocument = $this->getFullTextFromJats($submissionFile);
        } catch (\Throwable $e) {
            error_log('JATSParser onJatsFileAdded error for file id=' . $submissionFile->getId() . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return Hook::CONTINUE;
        }

        // Determine locale from submission
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $locale = $submission->getData('locale');

        Repo::publication()->edit($publication, [
            'jatsParser::fullTextFileId' => array_merge(
                (array) ($publication->getData('jatsParser::fullTextFileId') ?? []),
                [$locale => $submissionFile->getId()]
            ),
            'jatsParser::fullText' => array_merge(
                (array) ($publication->getData('jatsParser::fullText') ?? []),
                [$locale => $htmlDocument->saveAsHTML()]
            ),
        ]);

        return Hook::CONTINUE;
    }

    /**
     * @param string $hookname
     * @param array $args
     * @return bool
     * @brief modify citationsRaw property based on parsed citations from JATS XML
     */
    public function editPublicationReferences(string $hookname, array $args): bool
    {
        $newPublication = $args[0];
        $params = $args[2];
        if (!array_key_exists('jatsParser::references', $params)) {
            return Hook::CONTINUE;
        }

        $fileId = $params['jatsParser::references'];
        if (!$fileId) {
            return Hook::CONTINUE;
        }

        $submissionFile = Repo::submissionFile()->get((int) $fileId);
        if (!$submissionFile) {
            return Hook::CONTINUE;
        }

        // Extract references directly from JATS XML, bypassing CiteProc
        // (CiteProc may throw when lang is null, causing the hook to fail silently)
        try {
            $htmlDocument = $this->getFullTextFromJats($submissionFile);
        } catch (\Throwable $e) {
            error_log('JATSParser editPublicationReferences error for file id=' . $submissionFile->getId() . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return Hook::CONTINUE;
        }

        $jatsRefs = $htmlDocument->jatsDocument->getReferences();

        if (empty($jatsRefs)) {
            return Hook::CONTINUE;
        }

        $rawCitations = '';
        foreach ($jatsRefs as $ref) {
            $rawText = trim($ref->getRawReference());
            if (!empty($rawText)) {
                $rawCitations .= $rawText . "\n";
            }
        }

        if (!empty(trim($rawCitations))) {
            // Setting citationsRaw triggers OJS to re-import structured citations via DAO
            $newPublication->setData('citationsRaw', trim($rawCitations));
        }

        return Hook::CONTINUE;
    }

    /**
     * @param string $hookname
     * @param array $args
     * @return bool
     * @brief creates a PDF file and saves as a galley
     */
    public function createPdfGalley(string $hookname, array $args): bool
    {
        $newPublication = $args[0]; /* @var $newPublication \APP\publication\Publication */
        $params = $args[2];
        $request = $args[3];

        if (!array_key_exists('jatsParser::fullText', $params)) {
            return Hook::CONTINUE;
        }
        if (!$this->getSetting($request->getContext()->getId(), 'convertToPdf')) {
            return Hook::CONTINUE;
        }

        $fullTextByLocale = $params['jatsParser::fullText'];
        if (empty($fullTextByLocale) || !is_array($fullTextByLocale)) {
            return Hook::CONTINUE;
        }

        foreach ($fullTextByLocale as $localeKey => $fullText) {
            if (empty($fullText)) {
                continue;
            }

            // Evita duplicati: se esiste già una galley PDF per questo locale, aggiorna quella
            $pdfLabel = __('plugins.generic.jatsParser.publication.galley.pdf.label');
            $existingGalley = null;
            foreach ($newPublication->getData('galleys') ?? [] as $g) {
                if ($g->getLocale() === $localeKey && $g->getLabel() === $pdfLabel) {
                    $existingGalley = $g;
                    break;
                }
            }

            // Set real path to images, attached to the original JATS XML file
            $jatsFileId = $newPublication->getData('jatsParser::fullTextFileId', $localeKey);
            $jatsSubmissionFile = $jatsFileId ? Repo::submissionFile()->get($jatsFileId) : null;
            if ($jatsSubmissionFile) {
                $fullText = $this->_setSupplImgPath($jatsSubmissionFile, $fullText, true);
            }

            // Set references
            $fullText = $this->_setReferences($newPublication, $localeKey, $fullText);

            // Finally, convert and receive TCPDF output as a binary string
            $pdf = $this->pdfCreation($fullText, $newPublication, $request, $localeKey);

            // Riusa la galley esistente o creane una nuova
            if ($existingGalley) {
                $galley = $existingGalley;
            } else {
                $galleyId = $this->createGalley($localeKey, $newPublication);
                $galley = Repo::galley()->get($galleyId);
            }

            // Create associated submission file and update the galley
            $submissionFile = $this->_setPdfSubmissionFile($pdf, $newPublication, $galley, $localeKey);
            if ($submissionFile) {
                Repo::galley()->edit($galley, ['submissionFileId' => $submissionFile->getId()]);
            } else {
                Repo::galley()->delete($galley);
            }
        }

        return Hook::CONTINUE;
    }

    /**
     * @param string $galleyLocale
     * @param \APP\publication\Publication $publication
     * @return int
     * @brief create an empty galley
     */
    public function createGalley(string $galleyLocale, $publication): int
    {
        $galley = Repo::galley()->newDataObject();
        $galley->setLocale($galleyLocale);
        $galley->setData('publicationId', $publication->getId());
        $galley->setLabel(__('plugins.generic.jatsParser.publication.galley.pdf.label'));
        return Repo::galley()->add($galley);
    }

    /**
     * @param string $pdfBinaryString output of the TCPDF, binary string
     * @param \APP\publication\Publication $publication publication associated with a submission file
     * @param \PKP\galley\Galley $galley
     * @brief creates a new PDF submission file
     */
    private function _setPdfSubmissionFile(string $pdfBinaryString, $publication, $galley, string $localeKey)
    {
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $request = $this->getRequest();

        // Create a temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'jatsParser');
        file_put_contents($tmpFile, $pdfBinaryString);

        // Store file
        $submissionDir = Repo::submissionFile()->getSubmissionDir($submission->getData('contextId'), $submission->getId());
        $fileId = app()->get('file')->add(
            $tmpFile,
            $submissionDir . DIRECTORY_SEPARATOR . uniqid() . '.pdf'
        );

        // Set original filename from the JATS XML file
        // Usiamo $localeKey esplicitamente invece di $galley->getLocale() che può essere null
        $jatsFileId = $publication->getData('jatsParser::fullTextFileId', $localeKey);
        $jatsFile = $jatsFileId ? Repo::submissionFile()->get((int) $jatsFileId) : null;

        $name = [];
        if ($jatsFile) {
            foreach ($jatsFile->getData('name') as $locale => $sourceName) {
                $name[$locale] = pathinfo($sourceName)['filename'] . '.pdf';
            }
        }
        if (empty($name)) {
            $name = [$localeKey => 'article.pdf'];
        }

        $genreDAO = DAORegistry::getDAO('GenreDAO');
        $genre = $genreDAO->getByKey('SUBMISSION', $submission->getData('contextId'));

        $submissionFile = Repo::submissionFile()->newDataObject([
            'fileId' => $fileId,
            'assocType' => Application::ASSOC_TYPE_GALLEY,
            'assocId' => $galley->getId(),
            'fileStage' => SubmissionFile::SUBMISSION_FILE_PROOF,
            'mimetype' => 'application/pdf',
            'locale' => $galley->getData('locale'),
            'genreId' => $genre->getId(),
            'name' => $name,
            'submissionId' => $submission->getId(),
        ]);

        $submissionFileId = Repo::submissionFile()->add($submissionFile);

        unlink($tmpFile);
        return Repo::submissionFile()->get($submissionFileId);
    }

    /**
     * @param \APP\publication\Publication $publication
     * @param string $locale
     * @param string $htmlString
     * @return string
     * @brief set references for PDF galley
     */
    private function _setReferences($publication, string $locale, string $htmlString): string
    {
        // In OJS 3.5 citationsRaw è un Stringable@anonymous (lazy proxy) - va castato a string
        $rawCitations = (string) ($publication->getData('citationsRaw') ?? '');
        if (empty($rawCitations)) {
            return $htmlString;
        }

        $citationTokenizer = new CitationListTokenizerFilter();
        $citationStrings = $citationTokenizer->execute($rawCitations);

        if (!is_array($citationStrings) || empty($citationStrings)) {
            return $htmlString;
        }
        $htmlString .= '<h2 class="article-section-title" id="reference-title">' . __('submission.citations', [], $locale) . '</h2>';
        $htmlString .= "\n";
        $htmlString .= '<ol id="references">';
        $htmlString .= "\n";
        foreach ($citationStrings as $citationString) {
            $htmlString .= "\t";
            $htmlString .= '<li>' . $citationString . '</li>';
            $htmlString .= "\n";
        }
        $htmlString .= '</ol>';

        return $htmlString;
    }

    /**
     * @param SubmissionFile $submissionFile
     * @return HTMLDocument
     * @brief retrieves PHP DOM representation of the article's full-text
     */
    public function getFullTextFromJats(SubmissionFile $submissionFile): HTMLDocument
    {
        $fileMgr = new PrivateFileManager();
        $filePath = $fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path');
        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                'JATSParser: XML file not found on disk (submission file id=' . $submissionFile->getId() . ', path=' . $filePath . ')'
            );
        }
        $htmlDocument = new HTMLDocument(new Document($filePath));
        return $htmlDocument;
    }

    /**
     * @param string $hookname
     * @param array $args
     * @return bool
     * @brief Displays full-text on article landing page
     */
    public function displayFullText(string $hookname, array $args): bool
    {
        $templateMgr =& $args[1];
        $output =& $args[2];
        $publication = $templateMgr->getTemplateVars('publication');
        $submission = $templateMgr->getTemplateVars('article');
        $fullTexts = $publication->getData('jatsParser::fullText');

        $submissionFileId = 0;
        $submissionFile = null;

        $request = $this->getRequest();
        $html = null;

        if (empty($fullTexts)) {
            return Hook::CONTINUE;
        }
        $currentLocale = Locale::getLocale();
        if (array_key_exists($currentLocale, $fullTexts)) {
            $html = $fullTexts[$currentLocale];

            $submissionFileId = $publication->getData('jatsParser::fullTextFileId', $currentLocale);
            $submissionFile = Repo::submissionFile()->get($submissionFileId);
        } else {
            $availableLocales = Locale::getLocales();
            $msg = __('plugins.generic.jatsParser.article.fulltext.availableLocale');
            if (count($fullTexts) > 1) {
                $msg = __('plugins.generic.jatsParser.article.fulltext.availableLocales');
            }

            $html = '<p>' . $msg;
            foreach ($fullTexts as $localeKey => $fullText) {
                $localeName = isset($availableLocales[$localeKey]) ? $availableLocales[$localeKey]->getDisplayName() : $localeKey;
                $html .= ' <a href="' . $request->url(null, 'user', 'setLocale', $localeKey) . '">' . $localeName . '</a>';
                if ($fullText !== end($fullTexts)) {
                    $html .= ', ';
                } else {
                    $html .= '.';
                }
            }
            $html .= '</p>';
        }

        if (is_null($html)) {
            return Hook::CONTINUE;
        }

        if ($submissionFileId && $submissionFile) {
            $html = $this->_setSupplImgPath($submissionFile, $html);
        }

        $templateMgr->assign('fullText', $html);
        $output .= $templateMgr->fetch($this->getTemplateResource('articleMainView.tpl'));

        return Hook::CONTINUE;
    }

    /**
     * @param SubmissionFile $submissionFile
     * @param string $htmlString
     * @return string
     * @brief Substitute path to attached images for full-text HTML
     */
    public function _setSupplImgPath(SubmissionFile $submissionFile, string $htmlString, bool $forPdf = false): string
    {
        $dependentFilesIterator = Repo::submissionFile()
            ->getCollector()
            ->filterByAssoc(Application::ASSOC_TYPE_SUBMISSION_FILE, [$submissionFile->getId()])
            ->filterBySubmissionIds([$submissionFile->getData('submissionId')])
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
            ->includeDependentFiles()
            ->getMany();

        $request = $this->getRequest();
        $imageFiles = [];

        $privateFileManager = new PrivateFileManager();
        $genreDao = DAORegistry::getDAO('GenreDAO');
        foreach ($dependentFilesIterator as $dependentFile) {
            $genre = $genreDao->getById($dependentFile->getData('genreId'));
            if (!$genre || $genre->getCategory() !== Genre::GENRE_CATEGORY_ARTWORK) {
                continue;
            }
            if (!in_array($dependentFile->getData('mimetype'), self::getSupportedSupplFileTypes())) {
                continue;
            }
            $submissionId = $submissionFile->getData('submissionId');
            if ($forPdf) {
                $image = file_get_contents($privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile->getData('path'));
                $imageBase64 = base64_encode($image);
                $mime = $dependentFile->getData('mimetype');
                $filePath = 'data:' . $mime . ';base64,' . $imageBase64;
            } else {
                switch ($request->getRequestedOp()) {
                    case 'view':
                        $filePath = $request->url(null, 'article', 'downloadFullTextAssoc', [$submissionId, $dependentFile->getData('assocId'), $dependentFile->getData('fileId')]);
                        break;
                    case 'editPublication':
                        $image = file_get_contents($privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile->getData('path'));
                        $imageBase64 = base64_encode($image);
                        $filePath = '@' . $imageBase64;
                        break;
                    default:
                        continue 2;
                }
            }

            $imageFileNames = array_values($dependentFile->getData('name'));
            foreach ($imageFileNames as $imageFileName) {
                if (empty($imageFileName)) {
                    continue;
                }
                if (array_key_exists($imageFileName, $imageFiles)) {
                    continue;
                }
                $imageFiles[$imageFileName] = $filePath;
            }
        }

        if (empty($imageFiles)) {
            return $htmlString;
        }

        foreach ($imageFiles as $originalFileName => $filePath) {
            $pattern = preg_quote(rawurlencode($originalFileName));

            $htmlString = preg_replace(
                '/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
                '\1="' . $filePath . '"',
                $htmlString
            );
        }

        return $htmlString;
    }

    /**
     * @return array
     */
    public static function getSupportedSupplFileTypes(): array
    {
        return [
            'image/png',
            'image/jpeg',
        ];
    }

    /**
     * Load the workflow Vue component for the editorial JATS tab
     *
     * @param string $hookName `TemplateManager::display`
     * @param array $args [$templateMgr, $template]
     */
    public function addWorkflowComponent(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'dashboard/editors.tpl') {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();

        // Inject the processing URL and locale strings as global JS variables.
        // We inject locale strings directly to avoid relying on the pkp.localeKeys cache.
        $processUrl = $request->url($context->getPath(), 'jatsParser', 'processSubmission');
        $clearUrl   = $request->url($context->getPath(), 'jatsParser', 'clearFullText');
        $filesUrl   = $request->url($context->getPath(), 'jatsParser', 'getWorkflowFiles');
        $localeStrings = [
            'selectFile'         => __('plugins.generic.jatsParser.workflow.selectFile'),
            'noProductionFiles'  => __('plugins.generic.jatsParser.workflow.noProductionFiles'),
            'currentlyProcessed' => __('plugins.generic.jatsParser.workflow.currentlyProcessed'),
            'process'            => __('plugins.generic.jatsParser.workflow.process'),
            'reprocess'          => __('plugins.generic.jatsParser.workflow.reprocess'),
            'processSuccess'     => __('plugins.generic.jatsParser.workflow.processSuccess'),
            'processError'       => __('plugins.generic.jatsParser.workflow.processError'),
            'noJatsFile'         => __('plugins.generic.jatsParser.workflow.noJatsFile'),
            'clearFullText'      => __('plugins.generic.jatsParser.workflow.clearFullText'),
            'clearSuccess'       => __('plugins.generic.jatsParser.workflow.clearSuccess'),
            'clearError'         => __('plugins.generic.jatsParser.workflow.clearError'),
            'confirmClear'       => __('plugins.generic.jatsParser.workflow.confirmClear'),
            'tabLabel'           => __('plugins.generic.jatsParser.workflow.tab.label'),
            'assetsTitle'        => __('plugins.generic.jatsParser.workflow.assets.title'),
            'uploadInfo'         => __('plugins.generic.jatsParser.workflow.assets.uploadInfo'),
            'loading'            => __('common.loading'),
        ];
        $templateMgr->addJavaScript(
            'jatsparser-workflow-config',
            'window.jatsParserConfig = { processUrl: ' . json_encode($processUrl) . ', clearUrl: ' . json_encode($clearUrl) . ', filesUrl: ' . json_encode($filesUrl) . ', locale: ' . json_encode($localeStrings) . ' };',
            ['inline' => true, 'contexts' => 'backend']
        );

        // Load the workflow component JS
        $templateMgr->addJavaScript(
            'jatsparser-workflow-component',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/jatsParserWorkflow.js',
            ['contexts' => 'backend', 'priority' => TemplateManager::STYLE_SEQUENCE_LAST]
        );

        return Hook::CONTINUE;
    }

    /**
     * @param string $hookname
     * @param array $args
     * @return bool
     * @brief theme-specific styles for galley and article landing page
     */
    public function themeSpecificStyles(string $hookname, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'frontend/pages/article.tpl') {
            return Hook::CONTINUE;
        }

        $request = $this->getRequest();
        $baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

        $themePlugins = PluginRegistry::getPlugins('themes');
        foreach ($themePlugins as $themePlugin) {
            if ($themePlugin->isActive()) {
                $parentTheme = $themePlugin->parent;
                if ($themePlugin->getName() === 'defaultthemeplugin' || ($parentTheme && $parentTheme->getName() === 'defaultthemeplugin')) {
                    $templateMgr->addStyleSheet('jatsParserThemeStyles', $baseUrl . '/resources/styles/default/article.css');
                }
            }
        }

        return Hook::CONTINUE;
    }

    /**
     * @return void
     * @brief iterate through all submissions and add full-text from galleys
     */
    public function importGalleys(): void
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $user = $request->getUser();
        $fileManager = new PrivateFileManager();

        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStageIds([WORKFLOW_STAGE_ID_PRODUCTION])
            ->getMany();

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $galleys = $publication->getData('galleys');

            if (empty($galleys)) {
                continue;
            }

            foreach ($galleys as $galley) {
                $galleyFileId = $galley->getData('fileId');
                if (!$galleyFileId) {
                    continue;
                }
                $submissionFile = Repo::submissionFile()->get((int) $galleyFileId);
                if (!$submissionFile) {
                    continue;
                }
                if (!in_array($submissionFile->getData('mimetype'), ['application/xml', 'text/xml', 'application/jats+xml'])) {
                    continue;
                }

                $galleyLocale = $galley->getLocale();
                $localizedFullTextFileSetting = $publication->getData('jatsParser::fullTextFileId', $galleyLocale);
                if ($localizedFullTextFileSetting) {
                    continue;
                }

                $document = new Document($fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'));
                if (empty($document->getArticleSections())) {
                    continue;
                }

                // Copy galley as a production ready submission file
                $submissionDir = Repo::submissionFile()->getSubmissionDir($request->getContext()->getId(), $submission->getId());
                $fileId = app()->get('file')->add(
                    $fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'),
                    $submissionDir . '/' . uniqid() . '.xml'
                );

                $newSubmissionFile = Repo::submissionFile()->newDataObject([
                    'fileId' => $fileId,
                    'uploaderUserId' => $user->getId(),
                    'fileStage' => SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY,
                    'submissionId' => $submission->getId(),
                    'genreId' => $submissionFile->getData('genreId'),
                    'name' => $submissionFile->getData('name'),
                ]);
                $newSubmissionFileId = Repo::submissionFile()->add($newSubmissionFile);
                $newSubmissionFile = Repo::submissionFile()->get($newSubmissionFileId);

                // copy and attach dependent files, only images are supported
                $assocFiles = Repo::submissionFile()
                    ->getCollector()
                    ->filterByAssoc(Application::ASSOC_TYPE_SUBMISSION_FILE, [$submissionFile->getId()])
                    ->filterBySubmissionIds([$submission->getId()])
                    ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
                    ->includeDependentFiles()
                    ->getMany();

                foreach ($assocFiles as $assocFile) {
                    /** @var SubmissionFile $assocFile */
                    if (in_array($assocFile->getData('mimetype'), $this->getSupportedSupplFileTypes())) {
                        $newAssocFileId = app()->get('file')->add(
                            $fileManager->getBasePath() . DIRECTORY_SEPARATOR . $assocFile->getData('path'),
                            $submissionDir . '/' . uniqid() . '.' . $fileManager->parseFileExtension($assocFile->getData('path'))
                        );

                        $assocSubmissionFile = Repo::submissionFile()->newDataObject([
                            'fileId' => $newAssocFileId,
                            'assocId' => $newSubmissionFile->getId(),
                            'assocType' => Application::ASSOC_TYPE_SUBMISSION_FILE,
                            'uploaderUserId' => $user->getId(),
                            'fileStage' => SubmissionFile::SUBMISSION_FILE_DEPENDENT,
                            'submissionId' => $submission->getId(),
                            'genreId' => $assocFile->getData('genreId'),
                            'name' => $assocFile->getData('name'),
                            'caption' => $assocFile->getData('caption'),
                            'copyrightOwner' => $assocFile->getData('copyrightOwner'),
                            'credit' => $assocFile->getData('credit'),
                            'terms' => $assocFile->getData('terms'),
                        ]);
                        Repo::submissionFile()->add($assocSubmissionFile);
                    }
                }

                $htmlDocument = new HTMLDocument($document);
                $htmlString = $htmlDocument->saveAsHTML();
                $publication->setData('jatsParser::fullTextFileId', $newSubmissionFile->getId(), $galleyLocale);
                $publication->setData('jatsParser::fullText', $htmlString, $galleyLocale);
                Repo::publication()->dao->update($publication);
            }
        }
    }

    /**
     * @param string $hookName Form::config::before
     * @param FormComponent $form The form object
     */
    public function addCitationsFormFields(string $hookName, FormComponent $form): void
    {
        if ($form->id !== 'citations' || !empty($form->errors)) {
            return;
        }

        $path = parse_url($form->action)['path'];
        if (!$path) {
            return;
        }

        $args = explode('/', $path);
        $publicationId = 0;
        if ($key = array_search('publications', $args)) {
            if (array_key_exists($key + 1, $args)) {
                $publicationId = intval($args[$key + 1]);
            }
        }

        if (!$publicationId) {
            return;
        }

        $publication = Repo::publication()->get($publicationId);
        if (!$publication) {
            return;
        }

        $submissionFileIds = array_unique($publication->getData('jatsParser::fullTextFileId') ?? []);
        if (empty($submissionFileIds)) {
            return;
        }

        $submissionFiles = [];
        foreach ($submissionFileIds as $submissionFileId) {
            if ($submissionFile = Repo::submissionFile()->get($submissionFileId)) {
                $submissionFiles[] = $submissionFile;
            }
        }

        if (empty($submissionFiles)) {
            return;
        }

        $options = [];
        foreach ($submissionFiles as $submissionFile) {
            $options[] = [
                'value' => $submissionFile->getId(),
                'label' => $submissionFile->getLocalizedData('name'),
            ];
        }

        $options[] = [
            'value' => null,
            'label' => __('common.default'),
        ];

        $form->addField(new \PKP\components\forms\FieldOptions('jatsParser::references', [
            'label' => __('plugins.generic.jatsParser.publication.jats.references.label'),
            'description' => __('plugins.generic.jatsParser.publication.jats.references.description'),
            'type' => 'radio',
            'options' => $options,
            'value' => null,
        ]));
    }

    /**
     * @param string $htmlString
     * @param \APP\publication\Publication $publication
     * @param \APP\core\Request $request
     * @param string $localeKey
     * @return string
     */
    private function pdfCreation(string $htmlString, $publication, $request, string $localeKey): string
    {
        $context = $request->getContext();
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $issue = Repo::issue()->getBySubmissionId($submission->getId());

        $pdfDocument = new TCPDFDocument();

        $pdfDocument->setTitle($publication->getLocalizedFullTitle($localeKey));

        $journal = $request->getContext();
        $thumb = $journal->getLocalizedData('journalThumbnail');
        if (!empty($thumb)) {
            $journalFilesPath = __DIR__ . '/../../../' . Config::getVar('files', 'public_files_dir') . '/journals/' . $journal->getId() . '/';
            $pdfHeaderLogo = $journalFilesPath . $thumb['uploadName'];
        } else {
            $pdfHeaderLogo = __DIR__ . '/JATSParser/logo/logo.jpg';
        }

        $pdfDocument->SetCreator(PDF_CREATOR);
        $userGroups = UserGroup::withContextIds([$context->getId()])->get();
        $pdfDocument->SetAuthor($publication->getAuthorString($userGroups));
        $pdfDocument->SetSubject($publication->getLocalizedData('subject', $localeKey));

        $articleDataString = '';

        if ($issue && $issueIdentification = $issue->getIssueIdentification()) {
            $articleDataString .= $issueIdentification;
        }

        if ($pages = $publication->getLocalizedData('subject', $localeKey)) {
            $articleDataString .= ', ' . $pages;
        }

        if ($doi = $publication->getData('pub-id::doi')) {
            $articleDataString .= "\n" . __('plugins.pubIds.doi.readerDisplayName', [], $localeKey) . ': ' . $doi;
        }

        $pdfDocument->SetHeaderData($pdfHeaderLogo, PDF_HEADER_LOGO_WIDTH, $journal->getName($localeKey), $articleDataString);

        $pdfDocument->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdfDocument->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdfDocument->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdfDocument->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdfDocument->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdfDocument->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdfDocument->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdfDocument->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdfDocument->AddPage();

        $pdfDocument->SetFillColor(255, 255, 255);
        $pdfDocument->SetFont('dejavuserif', 'B', 20);
        $pdfDocument->MultiCell('', '', $publication->getLocalizedFullTitle($localeKey), 0, 'L', 1, 1, '', '', true);
        $pdfDocument->Ln(6);

        $authors = $publication->getData('authors');
        if (count($authors) > 0) {
            foreach ($authors as $author) {
                $pdfDocument->SetFont('dejavuserif', 'I', 10);

                $authorName = htmlspecialchars($author->getGivenName($localeKey)) . ' ' . htmlspecialchars($author->getFamilyName($localeKey));
                $affiliation = htmlspecialchars($author->getLocalizedAffiliationNamesAsString($localeKey));

                $authorLineWidth = 60;
                $authorNameStringHeight = $pdfDocument->getStringHeight($authorLineWidth, $authorName);

                $affiliationLineWidth = 110;
                $afilliationStringHeight = $pdfDocument->getStringHeight(110, $affiliation);

                $authorNameStringHeight > $afilliationStringHeight ? $cellHeight = $authorNameStringHeight : $cellHeight = $afilliationStringHeight;

                $pdfDocument->MultiCell($authorLineWidth, 0, $authorName, 0, 'L', 1, 0, 19, '', true, 0, false, true, 0, 'T', true);
                $pdfDocument->SetFont('dejavuserif', '', 10);
                $pdfDocument->MultiCell($affiliationLineWidth, $cellHeight, $affiliation, 0, 'L', 1, 1, '', '', true, 0, false, true, 0, 'T', true);
            }
            $pdfDocument->Ln(6);
        }

        if ($abstract = $publication->getLocalizedData('abstract', $localeKey)) {
            $pdfDocument->setCellPaddings(5, 5, 5, 5);
            $pdfDocument->SetFillColor(248, 248, 255);
            $pdfDocument->SetFont('dejavuserif', '', 10);
            $pdfDocument->SetLineStyle(['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => [255, 140, 0]]);
            $pdfDocument->writeHTMLCell('', '', '', '', $abstract, 'B', 1, 1, true, 'J', true);
            $pdfDocument->Ln(4);
        }

        $pdfDocument->setCellPaddings(0, 0, 0, 0);
        $pdfDocument->SetFont('dejavuserif', '', 10);

        $htmlString .= "\n" . '<style>' . "\n" . file_get_contents($this->getPluginPath() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'pdfGalley.css') . '</style>';
        $htmlString = $this->_prepareForPdfGalley($htmlString);

        $previousErrorReporting = error_reporting();
        error_reporting(E_ERROR);

        $pdfDocument->writeHTML($htmlString, true, false, true, false, '');
        $pdfOutput = $pdfDocument->Output('article.pdf', 'S');

        error_reporting($previousErrorReporting);

        return $pdfOutput;
    }

    /**
     * @param string $htmlString
     * @return string Preprocessed HTML string for TCPDF
     */
    private function _prepareForPdfGalley(string $htmlString): string
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $htmlHead = "\n";
        $htmlHead .= '<head>';
        $htmlHead .= "\t" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $htmlHead .= "\n";
        $htmlHead .= '</head>';
        $dom->loadHTML($htmlHead . $htmlString);

        $xpath = new \DOMXPath($dom);

        $tableNodes = $xpath->evaluate('//table');
        foreach ($tableNodes as $tableNode) {
            $tableNode->setAttribute('border', '1');
            $tableNode->setAttribute('cellpadding', '2');
        }

        $captionNodes = $xpath->evaluate('//figure/p[@class="caption"]|//table/caption');
        foreach ($captionNodes as $captionNode) {
            $captionParts = $xpath->evaluate('span[@class="label"]|span[@class="title"]', $captionNode);
            foreach ($captionParts as $captionPart) {
                $emptyTextNode = $dom->createTextNode(' ');
                $captionPart->appendChild($emptyTextNode);
            }
        }

        $tableCaptions = $xpath->evaluate('//table/caption');
        foreach ($tableCaptions as $tableCaption) {
            $tableNode = $tableCaption->parentNode;
            $divNode = $dom->createElement('div');
            $divNode->setAttribute('class', 'caption');
            $nextToTableNode = $tableNode->nextSibling;
            if ($nextToTableNode) {
                $tableNode->parentNode->insertBefore($divNode, $nextToTableNode);
            }
            $divNode->appendChild($tableCaption);
        }

        $modifiedHtmlString = $dom->saveHTML();
        $modifiedHtmlString = preg_replace('/<caption>\s*/', '<br>' . '<caption>', $modifiedHtmlString);
        $modifiedHtmlString = preg_replace('/<p class="caption">\s*/', '<p class="caption">', $modifiedHtmlString);

        return $modifiedHtmlString;
    }
}
