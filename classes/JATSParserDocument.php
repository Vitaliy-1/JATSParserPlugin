<?php
/**
 * @file plugins/generic/jatsParser/classes/JATSParserDocument.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JATSParserDocument
 * @ingroup plugins_generic_jatsParser
 */

namespace APP\plugins\generic\jatsParser\classes;

require_once __DIR__ . '/../JATSParser/vendor/autoload.php';

use JATSParser\HTML\Document as Document;
use JATSParser\Body\Document as JATSDocument;

class JATSParserDocument extends Document
{
    public JATSDocument $jatsDocument;
    private bool $parseReferences;

    public function __construct(JATSDocument $jatsDocument, bool $parseReferences = true)
    {
        $this->parseReferences = $parseReferences;

        parent::__construct($jatsDocument, $parseReferences);

        $this->jatsDocument = $jatsDocument;
    }

    public function useOjsReferences(): bool
    {
        if (!$this->jatsDocument->getReferences()) {
            return true;
        }
        return false;
    }
}
