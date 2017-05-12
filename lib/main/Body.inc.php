<?php

/**
 * @file plugins.generic.jatsParser.lib.main.Back
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class Body
 * @ingroup plugins_generic_jatsParser
 *
 * @brief For parsing a main sections (in the body node) of JATS XML. Supported up to 3 level deep (sections, subsections and subsubsections)
 */

import("plugins.generic.jatsParser.lib.classes.ArticleSection");
import("plugins.generic.jatsParser.lib.classes.ParContent");
import("plugins.generic.jatsParser.lib.classes.ParText");
import("plugins.generic.jatsParser.lib.classes.Xref");
import("plugins.generic.jatsParser.lib.classes.Italic");
import("plugins.generic.jatsParser.lib.classes.XrefFig");
import("plugins.generic.jatsParser.lib.classes.XrefTable");
import("plugins.generic.jatsParser.lib.classes.Bold");
import("plugins.generic.jatsParser.lib.classes.Table");
import("plugins.generic.jatsParser.lib.classes.Row");
import("plugins.generic.jatsParser.lib.classes.Cell");
import("plugins.generic.jatsParser.lib.classes.Figure");

class Body
{

    function bodyParsing (DOMXPath $xpath)
    {
        $sections = new ArrayObject();
        $subsections = new ArrayObject();
        $subsubsections = new ArrayObject();
        foreach ($xpath->evaluate("/article/body/sec") as $sec) {
            self::sectionParsing($xpath, $sec, $sections, $subsections, $subsubsections);
        }
        return $sections;
    }

    /**
     * @param $xpath
     * @param $sec -> our section DOM Node
     * @param $sections -> our section as ArrayObject
     * @param $subsections - our subsection as ArrayObject
     * @param $subsubsections - our subsubsection as ArrayObject
     */
    function sectionParsing(DOMXPath $xpath, DOMElement $sec, ArrayObject $sections, ArrayObject $subsections, ArrayObject $subsubsections)
    {
        $section = new ArticleSection();
        $ifSubSecs = $xpath->evaluate("parent::sec", $sec);
        $ifSubSubSecs = $xpath->evaluate("parent::sec/parent::sec", $sec);
        foreach ($ifSubSecs as $ifSubSec) {
        }
        foreach ($ifSubSubSecs as $ifSubSubSec) {

        }
        if ($ifSubSec == null) {
            $section->setType("sec");
            $sections->append($section);
        } elseif ($ifSubSec != null && $ifSubSubSec == null) {
            $section->setType("sub");
            $subsections->append($section);
        } elseif ($ifSubSec != null && $ifSubSubSec != null) {
            $section->setType("subsub");
            $subsections->append($section);
        }

        foreach ($xpath->evaluate("title|p|fig|sec|table-wrap|list", $sec) as $secContent) {

            if ($secContent->tagName == "title") {
                $section->setTitle(trim($secContent->nodeValue));
            } elseif ($secContent->tagName == "list") { // start of parsing lists, ordered and unordered are supported
                $listContent = new ParContent();

                if ($secContent->getAttribute("list-type") == "ordered") {
                    $listContent->setType("list-ordered");
                } elseif ($secContent->getAttribute("list-type") == "unordered") {
                    $listContent->setType("list-unordered");
                }
                foreach ($xpath->evaluate("list-item/p", $secContent) as $listItem) {
                    self::paragraphParsing($listItem, $listContent);
                }
                $section->getContent()->offsetSet(null, $listContent);
            } elseif ($secContent->tagName == "p") { // start of parsing paragraphs
                $paragraphContent = new ParContent();
                $paragraphContent->setType("paragraph");
                $section->getContent()->offsetSet(null, $paragraphContent);
                self::paragraphParsing($secContent, $paragraphContent);

            } elseif ($secContent->tagName == "table-wrap") { // start of parsing tables
                $table = new Table();
                $section->getContent()->offsetSet(null, $table);
                $tableIdAttr = $secContent->getAttribute("id");
                if ($tableIdAttr != null) {
                    $table->setId($tableIdAttr);
                }

                /* set table label, e.g. 'Table 1' */
                foreach ($xpath->evaluate("label", $secContent) as $labelNode) {
                    $table->setLabel($labelNode->nodeValue);
                }

                /* parsing table title */
                foreach ($xpath->evaluate("caption/title", $secContent) as $tableTitle) {
                    $titleParagraph = new ParContent();
                    $titleParagraph->setType("table-title");
                    $table->getContent()->offsetSet(null, $titleParagraph);
                    self::paragraphParsing($tableTitle, $titleParagraph);
                }

                /* parsing table with head and body */
                foreach ($xpath->evaluate("table/thead/tr|table/tbody/tr", $secContent) as $rowNode) {
                    if ($rowNode != null) {
                        $row = new Row();
                        $parentNode = $rowNode->parentNode;
                        if ($parentNode->tagName == "thead") {
                            $row->setType("head");
                            $table->getContent()->offsetSet(null, $row);
                            foreach ($xpath->evaluate("th|td", $rowNode) as $cellNode) {
                                $cell = new Cell();
                                $row->getContent()->offsetSet(null, $cell);
                                $cell->setColspan($cellNode->getAttribute("colspan"));
                                $cell->setRowspan($cellNode->getAttribute("rowspan"));
                                self::paragraphParsing($cellNode, $cell);
                            }
                        } elseif ($parentNode->tagName == "tbody") {
                            $row->setType("body");
                            $table->getContent()->offsetSet(null, $row);
                            foreach ($xpath->evaluate("th|td", $rowNode) as $cellNode) {
                                $cell = new Cell();
                                $row->getContent()->offsetSet(null, $cell);
                                $cell->setColspan($cellNode->getAttribute("colspan"));
                                $cell->setRowspan($cellNode->getAttribute("rowspan"));
                                self::paragraphParsing($cellNode, $cell);
                            }
                        }
                    }
                }

                /* parsing table without head */
                foreach ($xpath->evaluate("table/tr", $secContent) as $rowNodeWithoutHead) {
                    if ($rowNodeWithoutHead != null) {
                        $row = new Row();
                        $row->setType("flat");
                        $table->getContent()->offsetSet(null, $row);
                        foreach ($xpath->evaluate("th|td", $rowNodeWithoutHead) as $cellNodeWithoutHead) {
                            $cell = new Cell();
                            $row->getContent()->offsetSet(null, $cell);
                            $cell->setColspan($cellNodeWithoutHead->getAttribute("colspan"));
                            $cell->setRowspan($cellNodeWithoutHead->getAttribute("rowspan"));
                            self::paragraphParsing($cellNodeWithoutHead, $cell);
                        }

                    }
                }

                /* parsing table caption */
                foreach ($xpath->evaluate("caption/p", $secContent) as $tableCaption) {
                    $captionParagraph = new ParContent();
                    $captionParagraph->setType("table-caption");
                    $table->getContent()->offsetSet(null, $captionParagraph);
                    self::paragraphParsing($tableCaption, $captionParagraph);
                }


            } elseif ($secContent->tagName == "fig") {
                $figure = new Figure();
                $section->getContent()->offsetSet(null, $figure);
                $figure->setId($secContent->getAttribute("id"));
                foreach ($xpath->evaluate("label", $secContent) as $labelNode) {
                    $figure->setLabel($labelNode->nodeValue);
                }
                foreach ($xpath->evaluate("caption/title", $secContent) as $figureTitleNode) {
                    $figureTitle = new ParContent();
                    $figureTitle->setType("figure-title");
                    $figure->getContent()->offsetSet(null, $figureTitle);
                    self::paragraphParsing($figureTitleNode, $figureTitle);
                }
                foreach ($xpath->evaluate("caption/p", $secContent) as $figureCaptionNode) {
                    $figureCaption = new ParContent();
                    $figureCaption->setType("figure-caption");
                    $figure->getContent()->offsetSet(null, $figureCaption);
                    self::paragraphParsing($figureCaptionNode, $figureCaption);
                }
                foreach ($xpath->evaluate("graphic", $secContent) as $graphicLinksNode) {
                    $figure->setLink($graphicLinksNode->getAttribute("xlink:href"));
                }

            } elseif ($secContent->tagName == "sec") {
                if ($section->getType() == "sec") {
                    $section->getContent()->offsetSet(0, $subsections);
                }
                if ($section->getType() == "sub") {
                    $section->getContent()->offsetSet(0, $subsubsections);
                }

                /* Recursion for parsing subsections and subsubsection from XML */

                self::sectionParsing($xpath, $secContent, $sections, $subsections, $subsubsections);
            }
        }
    }

    /**
     * @param $secContent -> XML section Node content
     * @param $paragraphContent -> Cell or ParContent object
     */
    function paragraphParsing(DOMElement $secContent, $paragraphContent)
    {
        foreach ($secContent->childNodes as $parContent) {
            if ($parContent->nodeType == XML_TEXT_NODE) {
                $parText = new ParText();
                $parText->setContent($parContent->nodeValue);
                $paragraphContent->getContent()->offsetSet(null, $parText);
            } else if ($parContent->tagName == "xref") {
                if ($parContent->getAttribute("ref-type") == "bibr") {
                    $ref = new Xref();
                    $ref->setRid($parContent->getAttribute("rid"));
                    $ref->setContent($parContent->nodeValue);
                    $paragraphContent->getContent()->offsetSet(null, $ref);
                } else if ($parContent->getAttribute("ref-type") == "table") {
                    $ref = new XrefTable();
                    $ref->setRid($parContent->getAttribute("rid"));
                    $ref->setContent($parContent->nodeValue);
                    $paragraphContent->getContent()->offsetSet(null, $ref);
                } else if ($parContent->getAttribute("ref-type") == "fig") {
                    $ref = new XrefFig();
                    $ref->setRid($parContent->getAttribute("rid"));
                    $ref->setContent($parContent->nodeValue);
                    $paragraphContent->getContent()->offsetSet(null, $ref);
                }
            } else if ($parContent->tagName == "italic") {
                $italic = new Italic();
                $italic->setContent(trim($parContent->nodeValue));
                $paragraphContent->getContent()->offsetSet(null, $italic);
            } else if ($parContent->tagName == "bold") {
                $bold = new Bold();
                $bold->setContent($parContent->nodeValue);
                $paragraphContent->getContent()->offsetSet(null, $bold);
            }
        }
    }
}
