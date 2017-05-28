{**
 * plugins/generic/jatsParser/templates/abstract.tpl
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * A template to be included via Templates::Article::Main hook.
 * article abstract
 *}

<div class="panwrap abstract">
    <div class="section">
        <h2 class="title">
            {translate key="article.abstract"}
        </h2>
    </div>
    <div class="forpan">
        <div class="panel-body">
            {$article->getLocalizedAbstract()|strip_unsafe_html|nl2br}
        </div>
    </div>
</div>