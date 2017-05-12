<?php

/**
 * @file plugins.generic.jatsParser.lib.html.BackHtml
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class BackHtml
 * @ingroup plugins_generic_jatsParser
 *
 * @brief For writing references into HTML
 */

class BackHtml
{
    function htmlBackStructure(DOMDocument $html, References $references)
    {
        $path = new DOMXPath($html);
        $divArticletexts = $path->evaluate("/html/body/main/div/div/div/div/div/div[@class='article-text'][1]");
        foreach ($divArticletexts as $divArticletext) {
            $divPanwrap = $html->createElement("div");
            $divPanwrap->setAttribute("class", "panwrap");
            $divArticletext->appendChild($divPanwrap);

            $divSection = $html->createElement("div");
            $divSection->setAttribute("class", "section");
            $divPanwrap->appendChild($divSection);

            $hTitle = $html->createElement("h2", $references->getTitle());
            $hTitle->setAttribute("class", "title");
            $divSection->appendChild($hTitle);

            $divForpan = $html->createElement("div");
            $divForpan->setAttribute("class", "forpan");
            $divPanwrap->appendChild($divForpan);

            $divPanelBody = $html->createElement("div");
            $divPanelBody->setAttribute("class", "panel-body");
            $divForpan->appendChild($divPanelBody);

            $olReferences = $html->createElement("ol");
            $olReferences->setAttribute("class", "references");
            $divPanelBody->appendChild($olReferences);

            foreach ($references->getReferences() as $reference) {
                $liReference = $html->createElement("li");
                $liReference->setAttribute("class", "ref");
                $olReferences->appendChild($liReference);

                $spanClass = $html->createElement("span");
                $spanClass->setAttribute("class", "bib");
                $spanClass->setAttribute("id", $reference->getId());
                $liReference->appendChild($spanClass);

                /* checking reference item type
                 * journal article
                 * book
                 * chapter
                 * conference paper
                 */
                if (get_class($reference) == "BibitemJournal") {

                    /* writing article title */
                    $spanRefTitle = $html->createElement("span", $reference->getTitle() . ".");
                    $spanRefTitle->setAttribute("class", "ref-title");
                    $spanClass->appendChild($spanRefTitle);

                    /* writing author names or collab */
                    $spanRefAuth = $html->createElement("span");
                    $spanRefAuth->setAttribute("class", "ref-auth");
                    $spanClass->appendChild($spanRefAuth);

                    /* writing author Names or Collab */
                    $surnameString = self::writingNames($reference);

                    if ($surnameString != null) {
                        $spanRefAuthText = $html->createTextNode($surnameString);
                        $spanRefAuth->appendChild($spanRefAuthText);
                    } elseif ($reference->getCollab() != null) {
                        $spanRefAuthText = $html->createTextNode($reference->getCollab() . ".");
                        $spanRefAuth->appendChild($spanRefAuthText);
                    }


                    /* writing article source, year, volume etc */
                    $spanRefSource = $html->createElement("span");
                    $spanRefSource->setAttribute("class", "ref-source");
                    $spanClass->appendChild($spanRefSource);

                    $sourceString = null;
                    $journalArticleSource = $reference->getSource();
                    $sourceString = $sourceString . $journalArticleSource . ". ";
                    $journalArticleYear = $reference->getYear();
                    if ($reference->getVolume() == null && $reference->getIssue() == null && $reference->getFpage() == null && $reference->getLpage() == null) {
                        $sourceString = $sourceString . $journalArticleYear;
                    } elseif ($journalArticleYear != null) {
                        $sourceString = $sourceString . $journalArticleYear . ";";
                    }
                    $sourceString = $sourceString . $reference->getVolume();
                    if ($reference->getIssue() != null) {
                        $sourceString = $sourceString . "(" . $reference->getIssue() . ")";
                    }
                    if ($reference->getFpage() != null && $reference->getLpage() != null) {
                        $sourceString = $sourceString . ":" . $reference->getFpage() . "-" . $reference->getLpage();
                    } elseif ($reference->getFpage() != null && $reference->getLpage() == null) {
                        $sourceString = $sourceString . ":" . $reference->getFpage();
                    } elseif ($reference->getLpage() != null && $reference->getFpage() == null) {
                        $sourceString = $sourceString . ":" . $reference->getLpage();
                    }
                    $sourceString = $sourceString . ".";
                    $spanRefSourceText = $html->createTextNode($sourceString);
                    $spanRefSource->appendChild($spanRefSourceText);

                    /* writing article links */
                    self::writingUrlDoiPmid($html, $reference, $spanClass);

                } elseif (get_class($reference) == "BibitemBook") {
                    $spanRefTitle = $html->createElement("span", $reference->getSource() . ".");
                    $spanRefTitle->setAttribute("class", "ref-title");
                    $spanClass->appendChild($spanRefTitle);

                    /* writing author names or collab */
                    $spanRefAuth = $html->createElement("span");
                    $spanRefAuth->setAttribute("class", "ref-auth");
                    $spanClass->appendChild($spanRefAuth);

                    $surnameString = self::writingNames($reference);

                    if ($surnameString != null) {
                        $spanRefAuthText = $html->createTextNode($surnameString);
                        $spanRefAuth->appendChild($spanRefAuthText);
                    } elseif ($reference->getCollab() != null) {
                        $spanRefAuthText = $html->createTextNode($reference->getCollab() . ".");
                        $spanRefAuth->appendChild($spanRefAuthText);
                    }

                    /* writing book publisher name, year  etc */
                    $spanRefSource = $html->createElement("span");
                    $spanRefSource->setAttribute("class", "ref-source");
                    $spanClass->appendChild($spanRefSource);

                    $sourceString = null;
                    if ($reference->getPublisherLoc() != null && $reference->getPublisherName() != null) {
                        $sourceString = $sourceString . $reference->getPublisherLoc() . ": ";
                    } elseif ($reference->getPublisherLoc() != null && $reference->getPublisherName() == null) {
                        $sourceString = $sourceString . $reference->getPublisherLoc();
                    }
                    if ($reference->getPublisherName() != null && $reference->getYear() != null) {
                        $sourceString = $sourceString . $reference->getPublisherName() . "; ";
                    } elseif ($reference->getPublisherName() != null && $reference->getYear() == null) {
                        $sourceString = $sourceString . $reference->getPublisherName();
                    }
                    if ($reference->getYear() != null) {
                        $sourceString = $sourceString . $reference->getYear();
                    }
                    $sourceString = $sourceString . ".";
                    $spanRefSourceText = $html->createTextNode($sourceString);
                    $spanRefSource->appendChild($spanRefSourceText);

                    self::writingUrlDoiPmid($html, $reference, $spanClass);
                } elseif (get_class($reference) == "BibitemChapter") {

                    $spanRefTitle = $html->createElement("span", $reference->getChapterTitle() . ".");
                    $spanRefTitle->setAttribute("class", "ref-title");
                    $spanClass->appendChild($spanRefTitle);

                    $spanRefAuth = $html->createElement("span");
                    $spanRefAuth->setAttribute("class", "ref-auth");
                    $spanClass->appendChild($spanRefAuth);

                    $surnameString = self::writingNames($reference);

                    if ($surnameString != null) {
                        $spanRefAuthText = $html->createTextNode($surnameString);
                        $spanRefAuth->appendChild($spanRefAuthText);
                    } elseif ($reference->getCollab() != null) {
                        $spanRefAuthText = $html->createTextNode($reference->getCollab() . ".");
                        $spanRefAuth->appendChild($spanRefAuthText);
                    }

                    /* writing book editors, title, publisher name, year  etc */
                    $spanRefSource = $html->createElement("span");
                    $spanRefSource->setAttribute("class", "ref-source");
                    $spanClass->appendChild($spanRefSource);

                    $sourceString = null;
                    if ($reference->getEditor() != null) {
                        $sourceString = "In: ";
                        foreach ($reference->getEditor() as $i => $editor) {
                            $initials = null;
                            $initialArray = (array)$editor->getInitials();
                            foreach ($initialArray as $initial) {
                                $initials = (string)$initial . $initials;
                            }
                            if ($initials != null) {
                                $sourceString = (string)$sourceString . $editor->getSurname() . " " . $initials . ", ";
                            } elseif ($editor->getGivenname() != null) {
                                $sourceString = (string)$sourceString . $editor->getSurname() . " " . $editor->getGivenname() . ", ";
                            }
                        }
                        $sourceString = $sourceString . "editors. ";
                    }
                    if ($reference->getPublisherLoc() != null && $reference->getPublisherName() != null) {
                        $sourceString = $sourceString . $reference->getPublisherLoc() . ": ";
                    } elseif ($reference->getPublisherLoc() != null && $reference->getPublisherName() == null) {
                        $sourceString = $sourceString . $reference->getPublisherLoc();
                    }
                    if ($reference->getPublisherName() != null && $reference->getYear() != null) {
                        $sourceString = $sourceString . $reference->getPublisherName() . "; ";
                    } elseif ($reference->getPublisherName() != null && $reference->getYear() == null) {
                        $sourceString = $sourceString . $reference->getPublisherName();
                    }
                    if ($reference->getYear() != null) {
                        $sourceString = $sourceString . $reference->getYear();
                    }
                    $sourceString = $sourceString . ".";
                    $spanRefSourceText = $html->createTextNode($sourceString);
                    $spanRefSource->appendChild($spanRefSourceText);

                    self::writingUrlDoiPmid($html, $reference, $spanClass);
                } elseif (get_class($reference) == "BibitemConf") {

                    $spanRefTitle = $html->createElement("span", $reference->getSource() . ".");
                    $spanRefTitle->setAttribute("class", "ref-title");
                    $spanClass->appendChild($spanRefTitle);

                    $spanRefAuth = $html->createElement("span");
                    $spanRefAuth->setAttribute("class", "ref-auth");
                    $spanClass->appendChild($spanRefAuth);

                    $surnameString = self::writingNames($reference);

                    if ($surnameString != null) {
                        $spanRefAuthText = $html->createTextNode($surnameString);
                        $spanRefAuth->appendChild($spanRefAuthText);
                    } elseif ($reference->getCollab() != null) {
                        $spanRefAuthText = $html->createTextNode($reference->getCollab() . ".");
                        $spanRefAuth->appendChild($spanRefAuthText);
                    }

                    /* writing book publisher name, year  etc */
                    $spanRefSource = $html->createElement("span");
                    $spanRefSource->setAttribute("class", "ref-source");
                    $spanClass->appendChild($spanRefSource);

                    $sourceString = null;
                    $sourceString = $sourceString . "Paper presented at: ";
                    if ($reference->getConfName() != null && ($reference->getYear() != null || $reference->getConfDate() != null || $reference->getConfLoc() != null)) {
                        $sourceString = $sourceString . $reference->getConfName() . ";";
                    } else {
                        $sourceString = $sourceString . $reference->getConfName();
                    }
                    if ($reference->getConfDate() != null && $reference->getYear() != null) {
                        $sourceString = $sourceString . $reference->getConfDate() . ", ";
                    } elseif ($reference->getConfDate() != null && $reference->getYear() == null) {
                        $sourceString = $sourceString . $reference->getConfDate() . ". ";
                    }
                    if ($reference->getYear() != null) {
                        $sourceString = $sourceString . $reference->getYear() . ". ";
                    }
                    if ($reference->getConfLoc() != null) {
                        $sourceString = $sourceString . $reference->getConfLoc();
                    }
                    $sourceString = $sourceString . ".";
                    $spanRefSourceText = $html->createTextNode($sourceString);
                    $spanRefSource->appendChild($spanRefSourceText);

                    self::writingUrlDoiPmid($html, $reference, $spanClass);
                }
            }
        }

    }

    /**
     * @param DOMDocument $html
     * @param BibitemChapter|BibitemBook|BibitemConf|BibitemJournal $reference
     * @param DOMElement $spanClass
     */
    function writingUrlDoiPmid(DOMDocument $html, $reference, $spanClass)
    {
        if ($reference->getDoi() != null || $reference->getUrl() != null || $reference->getPmid() != null) {
            $spanRefFull = $html->createElement("span");
            $spanRefFull->setAttribute("class", "ref-full");
            $spanClass->appendChild($spanRefFull);

            if ($reference->getDoi() != null) {
                $aLinkDoi = $html->createElement("a", "View Article");
                $aLinkDoi->setAttribute("href", $reference->getDoi());
                $spanRefFull->appendChild($aLinkDoi);
            }
            if ($reference->getPmid() != null) {
                $alinkPmid = $html->createElement("a", "PubMed");
                $alinkPmid->setAttribute("href", $reference->getPmid());
                $spanRefFull->appendChild($alinkPmid);
            }
            if ($reference->getUrl() != null) {
                $alinkUrl = $html->createElement("a", "Publisher Full Text");
                $alinkUrl->setAttribute("href", $reference->getUrl());
                $spanRefFull->appendChild($alinkUrl);
            }
        }
    }

    /**
     * @param BibitemJournal|BibitemConf|BibitemBook|BibitemChapter $reference
     * @return null|string
     */
    function writingNames($reference)
    {
        $surnameString = null;
        foreach ($reference->getName() as $i => $name) {
            $initials = null;
            $initialArray = (array)$name->getInitials();
            foreach ($initialArray as $initial) {
                $initials = (string)$initial . $initials;
            }
            if ($i + 1 == $reference->getName()->count() && $initials != null) {
                $surnameString = (string)$surnameString . $name->getSurname() . " " . $initials . ".";
            } elseif ($initials != null) {
                $surnameString = (string)$surnameString . $name->getSurname() . " " . $initials . ", ";
            } elseif ($name->getGivenname() != null && $i + 1 == $reference->getName()->count()) {
                $surnameString = (string)$surnameString . $name->getSurname() . " " . $name->getGivenname() . ".";
            } elseif ($name->getGivenname() != null) {
                $surnameString = (string)$surnameString . $name->getSurname() . " " . $name->getGivenname() . ", ";
            }
        }
        return $surnameString;
    }
}
