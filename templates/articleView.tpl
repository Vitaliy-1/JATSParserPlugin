{**
 * plugins/generic/jatsParser/templates/articleView.tpl
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Page for displaying JATS XML galley as HTML
 *}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

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
					{continue}
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
			<ul class="authors-string">
				{foreach from=$article->getAuthors() item=authorString key=authorStringKey}
					{strip}
						<li>
							<a class="jatsparser-author-string-href" href="#author-{$authorStringKey+1}">
								<span>{$authorString->getFullName()|escape}</span>
								<sup class="author-symbol author-plus">+</sup>
								<sup class="author-symbol author-minus hide">&minus;</sup>
							</a>
							{if $authorString->getOrcid()}
								<a class="orcidImage" href="{$authorString->getOrcid()|escape}"><img src="{$baseUrl}/{$jatsParserOrcidImage}"></a>
							{/if}
						</li>
					{/strip}
				{/foreach}
			</ul>

			{* Authors *}
			{assign var="authorCount" value=$article->getAuthors()|@count}
			{assign var="authorBioIndex" value=0}
			<div class="article-details-authors">
				{foreach from=$article->getAuthors() item=author key=authorKey}
					<div class="article-details-author hideAuthor" id="author-{$authorKey+1}">
						{if $author->getLocalizedAffiliation()}
							<div class="article-details-author-affiliation">{$author->getLocalizedAffiliation()|escape}</div>
						{/if}
						{if $author->getOrcid()}
							<div class="article-details-author-orcid">
								<a href="{$author->getOrcid()|escape}" target="_blank">
									{$orcidIcon}
									{$author->getOrcid()|escape}
								</a>
							</div>
						{/if}
						{if $author->getLocalizedBiography()}
							<a class="article-details-bio-toggle" data-toggle="modal" data-target="#authorBiographyModal{$authorKey+1}">
								{translate key="plugins.themes.healthSciences.article.authorBio"}
							</a>
							{* Store author biographies to print as modals in the footer *}
							<div
									class="modal fade"
									id="authorBiographyModal{$authorKey+1}"
									tabindex="-1"
									role="dialog"
									aria-labelledby="authorBiographyModalTitle{$authorKey+1}"
									aria-hidden="true"
							>
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<div class="modal-title" id="authorBiographyModalTitle{$authorKey+1}">
												{$author->getFullName()|escape}
											</div>
											<button type="button" class="close" data-dismiss="modal" aria-label="{translate|escape key="common.close"}">
												<span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body">
											{$author->getLocalizedBiography()}
										</div>
									</div>
								</div>
							</div>
						{/if}
					</div>
				{/foreach}
			</div>

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
