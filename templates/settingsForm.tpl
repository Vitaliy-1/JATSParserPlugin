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

	<div id="description">{translate key="plugins.generic.jatsParser.settings.description"}</div>

	{fbvFormArea id="jatsParserSettingsFormArea"}
		{fbvFormSection list=true}
			{fbvElement type="radio" id="jatsReferences" name="references" value="jatsReferences" checked=$references|compare:"jatsReferences" label="plugins.generic.jatsParser.settings.jatsReferences"}
			{fbvElement type="radio" id="ojsReferences" name="references" value="ojsReferences" checked=$references|compare:"ojsReferences" label="plugins.generic.jatsParser.settings.ojsReferences"}
			{fbvElement type="radio" id="defaultReferences" name="references" value="defaultReferences" checked=$references|compare:"defaultReferences" label="plugins.generic.jatsParser.settings.defaultReferences"}
		{/fbvFormSection}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="convertToPdf" name="convertToPdf" checked=$convertToPdf label="plugins.generic.jatsParser.settings.display.pdf"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>
