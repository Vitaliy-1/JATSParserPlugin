 {**
 * plugins/generic/JATSParserPlugin/settingsForm.tpl
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * JATSParserPlugin plugin settings
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#jatsParserSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
</script>

<form class="pkp_form" id="jatsParserSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="jatsParserSettingsFormNotification"}

	{fbvFormArea id="jatsParserSettingsFormArea" title="plugins.generic.jatsParser.settings.description"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="convertToPdf" name="convertToPdf" checked=$convertToPdf label="plugins.generic.jatsParser.settings.convert.pdf"}
		{/fbvFormSection}
	{/fbvFormArea}

    {fbvFormArea id="jatsParserGalleyImport" title="plugins.generic.jatsParser.galley.import"}
		{fbvFormSection for="galleysImport" list=true description="plugins.generic.jatsParser.galley.import.description"}
			{fbvElement type="checkbox" id="galleysImport" name="galleysImport" label="plugins.generic.jatsParser.galley.import.title"}
		{/fbvFormSection}
    {/fbvFormArea}

	{fbvFormButtons}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

