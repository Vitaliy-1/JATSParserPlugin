{**
 * plugins/generic/jatsParser/templates/articleMainText.tpl
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * A template to be included via Templates::Article::Main hook.
 *}
<div id="ful">
    <ol class="refrences">
        {foreach from=$references->getReferences() item=reference}
            <li class="ref">
                <span class="bib" id="{$reference->getId()}">
                {if get_class($reference) == "BibitemJournal"}
                    <span class="ref-title">{$reference->getTitle()}</span>
                    <span class="ref-ref-auth">
                        {foreach from=$reference->getName() key=i item=name}
                            <span class="authorSurname">{$name->getSurname()|trim}</span>
                            <span class="authorInit">
                                {foreach from=$name->getInitials() key=j item=initial}
                                    <span initcount="{$name->getInitials()|@count}" j="{$j}" name="{$reference->getName()|count}">
                                    {if $j+1 < $name->getInitials()|count}
                                        {$initial}
                                    {else}
                                        {$initial},
                                    {/if}
                                    </span>
                                {/foreach}
                            </span>
                        {/foreach}
                    </span>
                {/if}
                </span>
            </li>
        {/foreach}
    </ol>
</div>