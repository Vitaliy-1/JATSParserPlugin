{**
 * plugins/generic/jatsParser/templates/controllers/jatsParserGalleySettings/jatsParserGalleySettings.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display settings of the JATS Parser plugin for XML galleys
 *}

<div id="settings">
	<pkp-form
		v-bind="components.{$smarty.const.FORM_JATSPARSER_GALLEY}"
		@set="set"
	/>
</div>
<script type="text/javascript">
	pkp.registry.init('settings', 'SettingsContainer', {$containerData|json_encode});
</script>

