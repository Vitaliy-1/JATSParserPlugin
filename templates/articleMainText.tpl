{**
 * plugins/generic/jatsParser/templates/articleMainText.tpl
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * A template to be included via Templates::Article::Main hook.
 *}
<div id="ful">
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