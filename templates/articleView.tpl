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
		{* Cover image *}
		{if $article->getLocalizedCoverImage() || $issue->getLocalizedCoverImage()}
			<div class="article_cover_wrapper">
				{if $article->getLocalizedCoverImage()}
					<img class="galley-cover-image img-fluid img-thumbnail" src="{$article->getLocalizedCoverImageUrl()|escape}"{if $article->getLocalizedCoverImageAltText()} alt="{$article->getLocalizedCoverImageAltText()|escape}"{/if}>
				{else}
					<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
						<img class="galley-cover-image img-fluid img-thumbnail" src="{$issue->getLocalizedCoverImageUrl()|escape}"{if $issue->getLocalizedCoverImageAltText()} alt="{$issue->getLocalizedCoverImageAltText()|escape}"{/if}>
					</a>
				{/if}
			</div>
		{/if}
		<div class="galley-meta-row">

			{* Section title *}
			{if $article->getSectionTitle()}
				<div class="galley-article-section-title">
					{$article->getSectionTitle()|escape}
				</div>
			{/if}

			{* DOI (requires plugin) *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $pubIdPlugin->getPubIdType() != 'doi'}
					{php}continue;{/php}
				{/if}
				{assign var=pubId value=$article->getStoredPubId($pubIdPlugin->getPubIdType())}
				{if $pubId}
					{assign var="doiUrl" value=$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
					<div class="galley-article-doi">
						<span class="galley-doi-label">
							{capture assign=translatedDOI}{translate key="plugins.pubIds.doi.readerDisplayName"}{/capture}
							{translate key="semicolon" label=$translatedDOI}
						</span>
						<span class="galley-doi-value">
							<a href="{$doiUrl}">
								{* maching DOI's (with new and old format) *}
								{$doiUrl|regex_replace:"/http.*org\//":" "}
							</a>
						</span>
					</div>
				{/if}
			{/foreach}

			{* Submitted date *}
			{if $article->getDateSubmitted()}
				<div class="galley-article-date-submitted">
					<span>{translate key="submissions.submitted"}:<span> <span>{$article->getDateSubmitted()|date_format:$dateFormatShort}</span>
				</div>
			{/if}

			{* Published date *}
			{if $article->getDatePublished()}
				<div class="galley-article-date-published">
					<span>{translate key="submissions.published"}:<span> <span>{$article->getDatePublished()|date_format:$dateFormatShort}</span>
				</div>
			{/if}
		</div>

		{* Article title *}
		{if $article->getLocalizedFullTitle()}
			<h1>{$article->getLocalizedFullTitle()|escape}</h1>
		{/if}

		{* Authors' list *}
		{if $article->getAuthors()}
			<ul class="galley-authors-list col-xl-6 offset-xl-3">
				{foreach from=$article->getAuthors() item=author key=authorNumber}
					<li class="galley-author-item">
						<span>
							{$author->getFullName()|escape}{if $authorNumber+1 !== $article->getAuthors()|@count},{/if}
						</span>
					</li>
				{/foreach}
			</ul>
		{/if}

		{* Keywords *}
		{if !empty($keywords[$currentLocale])}
			<div class="galley-keywords-wrapper">
				<div class="galley-keywords-row">
					{foreach from=$keywords item=keywordArray}
						{foreach from=$keywordArray item=keyword key=k}
								<span class="galley-span-keyword">{$keyword|escape}</span>
						{/foreach}
					{/foreach}
				</div>
			</div>
		{/if}
	</div>
	<div class="articleView-data row">
		<div class="left-article-block col-xl-3">
			{if $generatePdfUrl}
				<div class="galley-pdf-link-wrapper">
					<a class="galley-link-pdf" href="{$generatePdfUrl}">
						<i class="fas fa-file-pdf fa-2x"></i>
					</a>
				</div>
			{/if}
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
