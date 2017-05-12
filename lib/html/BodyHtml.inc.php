<?php

/**
 * @file plugins.generic.jatsParser.lib.html.BodyHtml
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class BodyHtml
 * @ingroup plugins_generic_jatsParser
 *
 * @brief For writing article sections into HTML
 */

class BodyHtml
{
    /**
     * @param $html -> our html as DOM object
     * @param $sections -> sections of our article as ArrayObject
     */
    function htmlBodyStructure(DOMDocument $html, ArrayObject $sections)
    {
        $path = new DOMXPath($html);
        $divArticletexts = $path->evaluate("/html/body/main/div/div/div/div/div/div[@class='article-text']");
        foreach ($divArticletexts as $divArticletext) {
            foreach ($sections as $sect) {
                $divPanwrap = $html->createElement("div");
                $divPanwrap->setAttribute("class", "panwrap");
                $divArticletext->appendChild($divPanwrap);

                $divSection = $html->createElement("div");
                $divSection->setAttribute("class", "section");
                $divPanwrap->appendChild($divSection);

                $hTitle = $html->createElement("h2", $sect->getTitle());
                $hTitle->setAttribute("class", "title");
                $divSection->appendChild($hTitle);

                $divForpan = $html->createElement("div");
                $divForpan->setAttribute("class", "forpan");
                $divPanwrap->appendChild($divForpan);

                $divPanelBody = $html->createElement("div");
                $divPanelBody->setAttribute("class", "panel-body");
                $divForpan->appendChild($divPanelBody);
                self::sectionWriting($html, $sect, $divPanelBody);
            }
        }
    }

    /**
     * @param $html
     * @param $sect -> single section of our article
     * @param $divPanelBody
     */
    function sectionWriting(DOMDocument $html, Section $sect, DOMElement $divPanelBody)
    {
        foreach ($sect->getContent() as $secCont) {
            if (get_class($secCont) == "ParContent" && $secCont->getType() == "paragraph") {
                $pForSections = $html->createElement("p");
                $pForSections->setAttribute("class", "for-sections");
                $divPanelBody->appendChild($pForSections);
                foreach ($secCont as $parCont) {
                    self::paragraphWriting($html, $parCont, $pForSections);
                }
            } elseif ((get_class($secCont) == "ParContent") && (($secCont->getType() == "list-ordered") || ($secCont->getType() == "list-unordered"))) {
                if ($secCont->getType() == "list-ordered") {
                    $pForSections = $html->createElement("ol");
                    $pForSections->setAttribute("class", "intext2");
                    $divPanelBody->appendChild($pForSections);
                } elseif ($secCont->getType() == "list-unordered") {
                    $pForSections = $html->createElement("ul");
                    $pForSections->setAttribute("class", "intext1");
                    $divPanelBody->appendChild($pForSections);
                }
                foreach ($secCont->getContent() as $parCont) {
                    $liInside = $html->createElement("li");
                    $pForSections->appendChild($liInside);
                    $pInsideLi = $html->createElement("p");
                    $pInsideLi->setAttribute("class", "inlist");
                    $liInside->appendChild($pInsideLi);
                    self::paragraphWriting($html, $parCont, $pInsideLi);
                }
            } elseif (get_class($secCont) == "Table") {
                $divFigure = $html->createElement("div");
                $divFigure->setAttribute("class", "figure-wrap table");
                $divPanelBody->appendChild($divFigure);

                $divFigureBox = $html->createElement("div");
                $divFigureBox->setAttribute("class", "fig-box");
                $divFigureBox->setAttribute("id", $secCont->getId());
                $divFigure->appendChild($divFigureBox);

                $tableNode = $html->createElement("table");
                $divFigureBox->appendChild($tableNode);

                /* writing table title */
                foreach ($secCont->getContent() as $tableTitles) {
                    if ($tableTitles->getType() == "table-title") {
                        $captionElement = $html->createElement("caption");
                        $captionElement->setAttribute("class", "table-title");
                        $tableNode->appendChild($captionElement);

                        $strongLabelElement = $html->createElement("strong", $secCont->getLabel());
                        $captionElement->appendChild($strongLabelElement);

                        foreach ($tableTitles->getContent() as $tableTitle) {
                            self::paragraphWriting($html, $tableTitle, $captionElement);
                        }
                    }
                }
                /* we need to create table head and body only once */
                $counter1 = 0;
                $counter2 = 0;

                /* iterating through table ArraObjects */
                foreach ($secCont->getContent() as $row) {
                    if ($row->getType() == "head") {
                        $counter1++;
                        if ($counter1 == 1) {
                            $theadNode = $html->createElement("thead");
                            $tableNode->appendChild($theadNode);
                        }
                        $tr = $html->createElement("tr");
                        $theadNode->appendChild($tr);
                        foreach ($row->getContent() as $cell) {
                            $thElement = $html->createElement("th");
                            $thElement->setAttribute("colspan", $cell->getColspan());
                            $thElement->setAttribute("rowspan", $cell->getRowspan());
                            $tr->appendChild($thElement);
                            foreach ($cell->getContent() as $parInCell) {
                                self::paragraphWriting($html, $parInCell, $thElement);
                            }
                        }
                    } elseif ($row->getType() == "body") {
                        $counter2++;
                        if ($counter2 == 1) {
                            $tbodyNode = $html->createElement("tbody");
                            $tableNode->appendChild($tbodyNode);
                        }
                        $tr = $html->createElement("tr");
                        $tbodyNode->appendChild($tr);
                        foreach ($row->getContent() as $cell) {
                            $tdElement = $html->createElement("td");
                            $tdElement->setAttribute("colspan", $cell->getColspan());
                            $tdElement->setAttribute("rowspan", $cell->getRowspan());
                            $tr->appendChild($tdElement);
                            foreach ($cell->getContent() as $parInCell) {
                                self::paragraphWriting($html, $parInCell, $tdElement);
                            }
                        }
                    } elseif ($row->getType() == "flat") {
                        $tr = $html->createElement("tr");
                        $tableNode->appendChild($tr);
                        foreach ($row->getContent() as $cell) {
                            $tdElement = $html->createElement("td");
                            $tdElement->setAttribute("colspan", $cell->getColspan());
                            $tdElement->setAttribute("rowspan", $cell->getRowspan());
                            $tr->appendChild($tdElement);
                            foreach ($cell->getContent() as $parInCell) {
                                self::paragraphWriting($html, $parInCell, $tdElement);
                            }
                        }
                    }
                }

                foreach ($secCont->getContent() as $tableCaption) {
                    if ($tableCaption->getType() == "table-caption") {
                        $tableCommentsElement = $html->createElement("p");
                        $tableCommentsElement->setAttribute("class", "comments");
                        $divFigureBox->appendChild($tableCommentsElement);

                        foreach ($tableTitles->getContent() as $tableTitle) {
                            self::paragraphWriting($html, $tableTitle, $tableCommentsElement);
                        }
                    }
                }

            } elseif (get_class($secCont) == "Figure") {
                $divFigureWrap = $html->createElement("div");
                $divFigureWrap->setAttribute("class", "figure-wrap fig");
                $divPanelBody->appendChild($divFigureWrap);

                $divFigureBoxFig = $html->createElement("div");
                $divFigureBoxFig->setAttribute("class", "fig-box");
                $divFigureBoxFig->setAttribute("id", $secCont->getId());
                $divFigureWrap->appendChild($divFigureBoxFig);

                $figStrong = $html->createElement("strong", $secCont->getLabel());
                $divFigureBoxFig->appendChild($figStrong);


                foreach ($secCont->getContent() as $figurePars) {
                    if ($figurePars->getType() == "figure-title") {
                        foreach ($figurePars as $figurePar) {
                            self::paragraphWriting($html, $figurePar, $divFigureBoxFig);
                        }
                    }
                }

                /* set div for image link and caption */
                $figureDivImagewrap = $html->createElement("div");
                $figureDivImagewrap->setAttribute("class", "imagewrap");
                $divFigureBoxFig->appendChild($figureDivImagewrap);

                $figureImgLink = $html->createElement("img");
                $figureImgLink->setAttribute("src", $secCont->getLink());
                $figureDivImagewrap->appendChild($figureImgLink);

                $figureDivInsideDiv = $html->createElement("div");
                $figureDivImagewrap->appendChild($figureDivInsideDiv);

                $figurePComments = $html->createElement("p");
                $figurePComments->setAttribute("class", "comments");
                $figureDivInsideDiv->appendChild($figurePComments);

                foreach ($secCont->getContent() as $figureCaptionPars) {
                    if ($figureCaptionPars->getType() == "figure-caption") {
                        foreach ($figureCaptionPars as $figureCaptionPar) {
                            self::paragraphWriting($html, $figureCaptionPar, $figurePComments);
                        }
                    }
                }

            } elseif (get_class($secCont) == "ArrayObject") {
                foreach ($secCont as $subsec) {

                    /* check section type */
                    if ($subsec->getType() == "sub") {
                        $divSubSection = $html->createElement("div");
                        $divSubSection->setAttribute("class", "subsection");
                        $divPanelBody->appendChild($divSubSection);

                        $hTitle = $html->createElement("h3", $subsec->getTitle());
                        $hTitle->setAttribute("class", "subtitle");
                        $divSubSection->appendChild($hTitle);

                        /* Recursion for parsing subSections */
                        self::sectionWriting($html, $subsec, $divSubSection);

                    } elseif ($subsec->getType() == "subsub") {
                        $divSubSubSection = $html->createElement("div");
                        $divSubSubSection->setAttribute("class", "subsubsection");
                        $divSubSection->appendChild($divSubSubSection);

                        $hTitle = $html->createElement("h4", $subsec->getTitle());
                        $hTitle->setAttribute("class", "subsubtitle");
                        $divSubSubSection->appendChild($hTitle);

                        /* Recursion for parsing subsubSections */
                        self::sectionWriting($html, $subsec, $divSubSubSection);
                    }
                }
            }
        }
    }

    /**
     * @param $html
     * @param $parCont
     * @param $pForSections
     */
    function paragraphWriting(DOMDocument $html, ParContent $parCont, DOMElement $pForSections)
    {
        if (get_class($parCont) == "ParText") {
            $parTextNode = $html->createTextNode($parCont->getContent());
            $pForSections->appendChild($parTextNode);
        } elseif (get_class($parCont) == "Xref") {
            $parXrefNode = $html->createElement("a", $parCont->getContent());
            $parXrefNode->setAttribute("class", "ref-tip btn btn-info");
            $parXrefNode->setAttribute("rid", $parCont->getRid());
            $pForSections->appendChild($parXrefNode);
        } elseif (get_class($parCont) == "XrefFig") {
            $parXrefNode = $html->createElement("a", $parCont->getContent());
            $parXrefNode->setAttribute("href", "#" . $parCont->getRid());
            $parXrefNode->setAttribute("class", "reffigure");
            $pForSections->appendChild($parXrefNode);
        } elseif (get_class($parCont) == "XrefTable") {
            $parXrefNode = $html->createElement("a", $parCont->getContent());
            $parXrefNode->setAttribute("href", "#" . $parCont->getRid());
            $parXrefNode->setAttribute("class", "reftable");
            $pForSections->appendChild($parXrefNode);
        } elseif (get_class($parCont) == "Italic") {
            $parXrefNode = $html->createElement("i", $parCont->getContent());
            $pForSections->appendChild($parXrefNode);
        } elseif (get_class($parCont) == "Bold") {
            $parXrefNode = $html->createElement("b", $parCont->getContent());
            $pForSections->appendChild($parXrefNode);
        }
    }
}
