{**
 * plugins/generic/jatsParser/templates/controllers/jatsParserGalleySettings/jatsParserGalleySettings.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display settings of the JATS Parser plugin for XML galleys
 *}

<script type="text/javascript">
	$(function() {ldelim}
		$('#jatsParserGalleyForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				baseUrl: {$baseUrl|json_encode}
				{rdelim}
		);
		{rdelim});
</script>

<form class="pkp_form" id="jatsParserGalleyForm" action="{url op="updateGalleySettings" galleyId=$galleyId submissionId=$submissionId}" method="post">
	{csrf}
	{fbvFormArea id="jatsParserGalleyDisplayArea" title="plugins.generic.jatsParser.galley.settings.display"}
		{fbvFormSection list=true}
			<div class="instruct">{translate key="plugins.generic.jatsParser.galley.settings.display.description"}</div>
			<p class="pkp_help">{translate key="plugins.generic.jatsParser.galley.settings.displayDefault.description"}</p>
			{fbvElement type="checkbox" id="jatsParserDisplayDefaultXml" name="jatsParserDisplayDefaultXml" checked=$jatsParserDisplayDefaultXml label="plugins.generic.jatsParser.galley.settings.displayDefault" value="1"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
