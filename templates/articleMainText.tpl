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
{foreach from=$sections item=sect}
    <div class="panwrap">
        <div class="section">
            <h2 class="title">{$sect->getTitle()}</h2>
        </div>
        <div class="forpan">
            <div class="panel-body">
                {foreach from=$sect->getContent() item=secCont}
                    {include file="`$path_template`/section.tpl"}
                    {if get_class($secCont) == "ArrayObject"}
                        {foreach from=$secCont item=subSec}
                            <div class="section">
                                <h3 class="title">{$subSec->getTitle()}</h3>
                            </div>
                            <div class="forpan">
                                <div class="panel-body">
                                    {foreach from=$subSec->getContent() item=secCont}
                                        {include file="`$path_template`/section.tpl"}
                                        {if get_class($secCont) == "ArrayObject"}
                                            {foreach from=$secCont item=subSubSec}
                                                <div class="section">
                                                    <h4 class="title">{$subSubCont->getTitle()}</h4>
                                                </div>
                                                <div class="forpan">
                                                    <div class="panel-body">
                                                        {foreach from=$subSubSec->getContent() item=secCont}
                                                            {include file="`$path_template`/section.tpl"}
                                                        {/foreach}
                                                    </div>
                                                </div>
                                            {/foreach}
                                        {/if}
                                    {/foreach}
                                </div>
                            </div>
                        {/foreach}
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
{/foreach}
    {** writing references *}
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
                            </span>
                        </li>
                    {/if}
                    {if get_class($reference) == "BibitemBook"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/book.tpl"}
                            </span>
                        </li>
                    {/if}
                    {if get_class($reference) == "BibitemChapter"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/chapter.tpl"}
                            </span>
                        </li>
                    {/if}
                    {if get_class($reference) == "BibitemConf"}
                        <li class="ref">
                            <span class="bib" id="{$reference->getId()}">
                                {include file="`$path_template`/vancouver/conference.tpl"}
                            </span>
                        </li>
                    {/if}
                {/foreach}
            </ol>
        </div>
    </div>
</div>
</div>