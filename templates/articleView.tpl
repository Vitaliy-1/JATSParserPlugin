{**
 * plugins/generic/jatsParser/templates/articleView.tpl
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Page for displaying JATS XML galley as HTML
 *}

{include file="frontend/components/headerHead"}
{include file="frontend/components/header.tpl" pageTitleTranslated=$article->getLocalizedTitle()|escape}

<div class="article-container container">
	<div class="galley-article-meta row">
		{if $article->getSectionTitle()}
			<div class="galley-article-section-title">
				{$article->getSectionTitle()}
			</div>
		{/if}
		{if $article->getLocalizedFullTitle()}
			<h1>{$article->getLocalizedFullTitle()|escape}</h1>
		{/if}
	</div>
	<div class="articleView-data row">
		<div class="col-xl-3">

		</div>
		<div class="col-xl-6 col-lg-8">
			<div class="article-fulltext">
				{if $article->getLocalizedAbstract()}
					<h2 class="article-section-title article-abstract">{translate key="article.abstract"}</h2>
					{$article->getLocalizedAbstract()|strip_unsafe_html}
				{/if}

				{$htmlDocument}

			</div>
		</div>
		<div class="details-wrapper col-xl-3 col-lg-4">
			{* Cover image *}
			{if $article->getLocalizedCoverImage() || $issue->getLocalizedCoverImage()}
				<div class="article_cover_wrapper">
					{if $article->getLocalizedCoverImage()}
						<img class="img-fluid img-thumbnail" src="{$article->getLocalizedCoverImageUrl()|escape}"{if $article->getLocalizedCoverImageAltText()} alt="{$article->getLocalizedCoverImageAltText()|escape}"{/if}>
					{else}
						<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
							<img class="img-fluid img-thumbnail" src="{$issue->getLocalizedCoverImageUrl()|escape}"{if $issue->getLocalizedCoverImageAltText()} alt="{$issue->getLocalizedCoverImageAltText()|escape}"{/if}>
						</a>
					{/if}
				</div>
			{/if}
			<div class="intraarticle-menu">
				<nav id="article-navbar" class="navbar navbar-light">
					<nav class="nav nav-pills flex-column" id="article-navigation-menu-items">
						{* adding menu by javascript here *}
					</nav>
				</nav>
			</div>
		</div>
	</div>
</div>

{include file="frontend/components/footer.tpl"}
