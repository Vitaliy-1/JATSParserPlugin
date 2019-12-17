{**
 * plugins/generic/jatsParser/templates/articleMain.tpl
 *
 * @uses $htmlDocument HTML string parsed from JATS XML document
 * @brief Page for displaying JATS XML on article landing page via Templates::Article::Main hook.
 *}

<article id="jatsParserFullText">
	{if $convertedPdfUrl}
		<p>
			<a href="{$convertedPdfUrl}">{translate key="jatsParser.pdf.read.label"}</a>
		</p>
	{/if}
	{$htmlDocument}
</article>
