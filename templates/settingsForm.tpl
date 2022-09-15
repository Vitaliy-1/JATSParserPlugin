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

		{assign var='otherStyleValue' value=true}
		{fbvFormSection list=true description="plugins.generic.jatsParser.style.description"}
			{foreach from=$citationStyles item="citationStyleItem"}
				{if $citationStyleItem["id"]|compare:$citationStyle}
					{assign var='otherStyleValue' value=false}
				{/if}
				{fbvElement type="radio" id=$citationStyleItem["id"] name="citationStyle" value=$citationStyleItem["id"] checked=$citationStyleItem["id"]|compare:$citationStyle label=$citationStyleItem["title"]}
			{/foreach}
			{fbvElement type="radio" id="customStyle" name="citationStyle" value="customStyle" checked=$otherStyleValue label="plugins.generic.jatsParser.style.custom"}
        {/fbvFormSection}
		{fbvFormSection}
			{if $otherStyleValue}
                {fbvElement type="text" id="customStyleInput" name="customStyleInput" value=$citationStyle label="plugins.generic.jatsParser.style.label"}
			{else}
				{fbvElement type="text" id="customStyleInput" name="customStyleInput" disabled=true label="plugins.generic.jatsParser.style.label"}
			{/if}
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
<script type="text/javascript">
    {literal}
		$(document).ready(function() {
			var customStyleInput = $('input[name="customStyleInput"]');
			var inputs = $('input[name="citationStyle"]');
			inputs.change(function () {
				if ($(this).is(':checked')) {
					var checkedItemId = $(this).attr('id');
					if (checkedItemId === 'customStyle') {
						customStyleInput.removeAttr('disabled');
					} else {
						customStyleInput.attr('disabled', 'disabled');
					}
				}
			});
		});
	{/literal}
</script>
