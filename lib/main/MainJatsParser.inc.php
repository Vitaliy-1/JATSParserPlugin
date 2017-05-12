<?php

/**
 * @file plugins.generic.jatsParser.lib.main.MainJatsParser
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class MainJatsParser
 * @ingroup plugins_generic_jatsParser
 *
 * @brief For making article objects and saving as HTML
 */
import("plugins.generic.jatsParser.lib.main.Body");
import("plugins.generic.jatsParser.lib.main.Back");
import("plugins.generic.jatsParser.lib.html.GeneralHtml");
import("plugins.generic.jatsParser.lib.html.BodyHtml");
import("plugins.generic.jatsParser.lib.html.BackHtml");

class MainJatsParser
{

    function parsingJatsContent(DOMDocument $xml)
    {
        /* iterating through JATS XML nodes and write data to Objects */
        //$xml = new DOMDocument();
        //$xml->load("../test.xml");
        $xpath = new DOMXPath($xml);


        /* parsing sections inside the body */
        $body = new Body();
        $sections = $body->bodyParsing($xpath);

        /* parsing references */
        $back = new Back();
        $references = $back->parsingBack($xpath);

        /* generating
         * html
         * */

        /* generating the structure of html */
        $generalHtml = new GeneralHtml();
        $html = $generalHtml->htmlGeneralStructure();

        /* add article body to html */
        $bodyHtml = new BodyHtml();
        $backHtml = new BackHtml();
        $bodyHtml->htmlBodyStructure($html, $sections); // sections -> ArrayObject
        $backHtml->htmlBackStructure($html, $references);  // $references -> References class


        /* saving html to a file */

        $html->saveHTML();
        //$output = $html->saveHTML();
        $the_file = "../test.html";
        $html->save($the_file);
        file_put_contents($the_file, preg_replace('/<\?xml[^>]+>\s+/', '<!DOCTYPE html>' . "\n", file_get_contents($the_file)));

        return $html;
    }
}
