{**
 * plugins/generic/jatsParser/templates/articleMainText.tpl
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * A template to be included via Templates::Article::Main hook.
 * Gives main structure of the article document
 *}


<div class="article-text">
    {** get abstract *}
    {if $article->getLocalizedAbstract()}
        {include file="`$path_template`/abstract.tpl"}
    {/if}
    {** get sections *}
{foreach from=$sections item=sect}
    <div class="panwrap">
        <div class="section">
            <h2 class="title">{$sect->getTitle()}</h2>
        </div>
        <div class="forpan">
            <div class="panel-body">
                {foreach from=$sect->getContent() item=secCont}
                    {include file="`$path_template`/section.tpl"}
                    {if get_class($secCont) == "ArticleSection"}
                        <div class="subsection">
                            <h3 class="title">{$secCont->getTitle()}</h3>
                        </div>
                        <div class="subforpan">
                            <div class="subpanel-body">
                                {foreach from=$secCont->getContent() item=secCont}
                                    {include file="`$path_template`/section.tpl"}
                                    {if get_class($secCont) == "ArticleSection"}
                                        <div class="subsubsection">
                                            <h4 class="title">{$secCont->getTitle()}</h4>
                                        </div>
                                        <div class="subsubforpan">
                                            <div class="subsubpanel-body">
                                                {foreach from=$secCont->getContent() item=secCont}
                                                    {include file="`$path_template`/section.tpl"}
                                                {/foreach}
                                            </div>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
{/foreach}
    {** writing references *}
{if $references->getTitle() != NULL}
<div class="panwrap">
    <div class = "section">
        <h2 class="title references">{$references->getTitle()}</h2>
    </div>
    <div class="forpan">
        <div class="panel-body">
            <ol class="references">
                {foreach from=$references->getReferences() item=reference}
                    {if get_class($reference) == "BibitemJournal"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/journal_article.tpl"}
                                <button type="button" class="tocite btn btn-default btn-xs" id="to-{$reference->getId()}">
                                    <span class="glyphicon glyphicon-circle-arrow-up" aria-hidden="true"></span> {translate key="jatsParser.references.link"}
                                </button>
                            </span>
                        </li>
                    {/if}
                    {if get_class($reference) == "BibitemBook"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/book.tpl"}
                                <button type="button" class="tocite btn btn-default btn-xs" id="to-{$reference->getId()}">
                                    <span class="glyphicon glyphicon-circle-arrow-up" aria-hidden="true"></span> {translate key="jatsParser.references.link"}
                                </button>
                            </span>
                        </li>
                    {/if}
                    {if get_class($reference) == "BibitemChapter"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/chapter.tpl"}
                                <button type="button" class="tocite btn btn-default btn-xs" id="to-{$reference->getId()}">
                                    <span class="glyphicon glyphicon-circle-arrow-up" aria-hidden="true"></span> {translate key="jatsParser.references.link"}
                                </button>
                            </span>
                        </li>
                    {/if}
                    {if get_class($reference) == "BibitemConf"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/conference.tpl"}
                                <button type="button" class="tocite btn btn-default btn-xs" id="to-{$reference->getId()}">
                                    <span class="glyphicon glyphicon-circle-arrow-up" aria-hidden="true"></span> {translate key="jatsParser.references.link"}
                                </button>
                            </span>
                        </li>
                    {/if}
                {/foreach}
            </ol>
        </div>
    </div>
</div>
{/if}
</div>