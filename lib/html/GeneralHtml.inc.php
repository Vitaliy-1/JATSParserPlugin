<?php

/**
 * @file plugins.generic.jatsParser.lib.html.GeneralHtml
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class GeneralHtml
 * @ingroup plugins_generic_jatsParser
 *
 * @brief Making general HTML structure for article
 */

class GeneralHtml
{
    /**
     * @return DOMDocument
     */
    function htmlGeneralStructure()
    {
        /* creating html */
        $html = new DOMDocument("1.0", "utf-8");
        $html->formatOutput = true;

        $htmlNode = $html->createElement("html");
        $html->appendChild($htmlNode);

        $htmlHead = $html->createElement("head");
        $htmlNode->appendChild($htmlHead);

        /* add links to css
        * in the head */
        $linkrel1 = $html->createElement("link");
        $linkrel1->setAttribute("rel", "stylesheet");
        $linkrel1->setAttribute("type", "text/css");
        $linkrel1->setAttribute("href", "custom/css/bootstrap.min.css");
        $htmlHead->appendChild($linkrel1);

        $linkrel2 = $html->createElement("link");
        $linkrel2->setAttribute("rel", "stylesheet");
        $linkrel2->setAttribute("type", "text/css");
        $linkrel2->setAttribute("href", "custom/css/bootstrap-theme.min.css");
        $htmlHead->appendChild($linkrel2);

        $linkrel3 = $html->createElement("link");
        $linkrel3->setAttribute("rel", "stylesheet");
        $linkrel3->setAttribute("type", "text/css");
        $linkrel3->setAttribute("href", "custom/css/psychosomatics.css");
        $htmlHead->appendChild($linkrel3);

        /* add links to javascript
        * in the head */
        $script1 = $html->createElement("script", " ");
        $script1->setAttribute("type", "text/javascript");
        $script1->setAttribute("src", "custom/js/jquery.min.js");
        $htmlHead->appendChild($script1);

        $script2 = $html->createElement("script", " ");
        $script2->setAttribute("type", "text/javascript");
        $script2->setAttribute("src", "custom/js/bootstrap.min.js");
        $htmlHead->appendChild($script2);

        $script3 = $html->createElement("script", " ");
        $script3->setAttribute("type", "text/javascript");
        $script3->setAttribute("src", "custom/js/psychosomatics.js");
        $script3->setAttribute("defer", "defer");
        $htmlHead->appendChild($script3);

        /* make body structure */
        $bodyNode = $html->createElement("body");
        $bodyNode->setAttribute("data-spy", "scroll");
        $bodyNode->setAttribute("data-target", "#myAffix");
        $htmlNode->appendChild($bodyNode);

        $mainNode = $html->createElement("main");
        $bodyNode->appendChild($mainNode);

        $gridcellNode = $html->createElement("div");
        $gridcellNode->setAttribute("class", "grid-cell");
        $mainNode->appendChild($gridcellNode);

        $containerFluid = $html->createElement("div");
        $containerFluid->setAttribute("class", "container-fluid");
        $gridcellNode->appendChild($containerFluid);

        $rowTabContent = $html->createElement("div");
        $rowTabContent->setAttribute("class", "row tab-content");
        $containerFluid->appendChild($rowTabContent);

        $forcontentCol = $html->createElement("div");
        $forcontentCol->setAttribute("class", "forcontent col-lg-7 col-md-7 col-sm-12 col-xs-12 tab-pane fade in active");
        $forcontentCol->setAttribute("role", "main");
        $forcontentCol->setAttribute("id", "article");
        $rowTabContent->appendChild($forcontentCol);

        $tabsForNav1 = $html->createElement("a", "Article");
        $tabsForNav1->setAttribute("href", "#article");
        $forcontentCol->appendChild($tabsForNav1);
        $tabsForNav2 = $html->createElement("a", "Data");
        $tabsForNav2->setAttribute("href", "#figuresdata");
        $forcontentCol->appendChild($tabsForNav2);
        $tabsForNav3 = $html->createElement("a", "Info");
        $tabsForNav3->setAttribute("href", "#infodata");
        $forcontentCol->appendChild($tabsForNav3);

        $articleContent = $html->createElement("div");
        $articleContent->setAttribute("class", "article-content");
        $forcontentCol->appendChild($articleContent);

        $titleBlock = $html->createElement("div");
        $titleBlock->setAttribute("class", "title-block");
        $articleContent->appendChild($titleBlock);

        $articleText = $html->createElement("div");
        $articleText->setAttribute("class", "article-text");
        $articleContent->appendChild($articleText);

        $divFront = $html->createElement("div", " ");
        $divFront->setAttribute("class", "front");
        $articleText->appendChild($divFront);
        return $html;
    }
}
