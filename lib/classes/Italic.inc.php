<?php
/**
 * @file plugins.generic.jatsParser.lib.classes.Italic
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class Italic
 * @ingroup plugins_generic_jatsParser
 *
 * @brief POPO for italic text
 */
class Italic extends ParContent {
    private $content;

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }
}
