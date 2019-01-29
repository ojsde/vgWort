{**
 * plugins/generic/vgWort/templates/settingsForm.tpl
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * VG Wort plugin settings
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#vgWortSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
	
	{literal}
	<!--
	// function to clear the dateInYear date filed
	function clearDate() {
		$('[id^="dateInYear"]').val('');
	}
	// -->
	{/literal}
</script>
<form class="pkp_form" id="vgWortSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="save"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="usageStatsSettingsFormNotification"}
	{fbvFormArea id="vgWortUserIdPassword" title="plugins.generic.vgWort.settings.vgWortUserIdPassword" class="border"}
		<p>{translate key="plugins.generic.vgWort.settings.vgWortUserIdPassword.description"}</p>
		{fbvFormSection}
			{fbvElement type="text" name="vgWortUserId" id="vgWortUserId" value=$vgWortUserId label="plugins.generic.vgWort.settings.vgWortUserId" required=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" name="vgWortUserPassword" id="vgWortUserPassword" value=$vgWortUserPassword label="plugins.generic.vgWort.settings.vgWortUserPassword" required=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="vgWortRegistration" title="plugins.generic.vgWort.settings.vgWortRegistration" class="border"}
		<p>{translate key="plugins.generic.vgWort.settings.vgWortRegistration.description"}</p>
		{fbvFormSection}
			{fbvElement type="text" id="dateInYear" name="dateInYear" label="plugins.generic.vgWort.settings.vgWortRegistration.dateInYear" value=$dateInYear|date_format:$dateFormatShort size=$fbvStyles.size.MEDIUM class="datepicker" inline=true}
			<a href="#" onClick="javascript:clearDate()">{translate key="plugins.generic.vgWort.settings.clearDate"}</a>
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" id="daysAfterPublication" name="daysAfterPublication" label="plugins.generic.vgWort.settings.vgWortRegistration.daysAfterPublication" value=$daysAfterPublication" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{*
	{fbvFormArea id="vgWortPrivacyArea" title="plugins.generic.vgWort.settings.vgWortPrivacy" class="border"}
		<p>{translate key="plugins.generic.vgWort.settings.vgWortPrivacy.description"}</p>
		{fbvFormSection}
			{fbvElement type="textarea" multilingual=true name="vgWortPrivacy" id="vgWortPrivacy" value=$vgWortPrivacy rich="extended"}
		{/fbvFormSection}
	{/fbvFormArea}
	*}
	{fbvFormArea id="vgWortTestModeArea" title="plugins.generic.vgWort.settings.vgWortTestMode" class="border"}
		{fbvFormSection list="true"}
			{fbvElement type="checkbox" id="vgWortTestAPI" name="vgWortTestAPI" label="plugins.generic.vgWort.settings.vgWortTestMode.description" checked=$vgWortTestAPI|compare:true}
		{/fbvFormSection}
	{/fbvFormArea}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	{fbvFormButtons submitText="common.save"}
</form>
