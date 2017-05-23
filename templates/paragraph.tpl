{**
 * plugins/generic/jatsParser/templates/paragraph.tpl
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * A template to be included via Templates::Article::Main hook.
 * for writing article paragraphs
 *}
{strip}
{if get_class($parCont) == "ParText"}
    {$parCont->getContent()}
{elseif get_class($parCont) == "Xref"}
    <a class="ref-tip btn btn-info" rid="{$parCont->getRid()}">
        {$parCont->getContent()}
    </a>
{elseif get_class($parCont) == "XrefFig"}
    <a class="reffigure" href="#{$parCont->getRid()}">
        {$parCont->getContent()}
    </a>
{elseif get_class($parCont) == "XrefTable"}
    <a class="reftable" href="#{$parCont->getRid()}">
        {$parCont->getContent()}
    </a>
{elseif get_class($parCont) == "Italic"}
    <i>{$parCont->getContent()}</i>
{elseif get_class($parCont) == "Bold"}
    <b>{$parCont->getContent()}</b>
{/if}
{/strip}