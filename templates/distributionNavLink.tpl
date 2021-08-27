{**
 * plugins/generic/vgWort/templates/distributionNavLink.tpl
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * Link (tab) on the Settins > Distribution page, for the pixel tags listing
 *
 *}

{**
 * <li><a name="pixelTags" href="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="pixelTagsTab" tab="pixelTags"}">{translate key="plugins.generic.vgWort.pixelTags"}</a></li>
 *}

<tab id="pixelTagsTab" label="{translate key="plugins.generic.vgWort.pixelTags"}">
	<!-- VG Wort -->
	
	{fbvFormSection}
	     <p class="pkp_help">{translate key="plugins.generic.vgWort.pixelTag.LocalizationNotice"}</p>
	{/fbvFormSection}
	{if $failedExists}
	     <div id="pixelTagsNotification" class="pkp_notification " style="display: block;">
	     	<div id="pkp_notification_pixel_tags">
	     		<span class="title">{translate key="notification.notification"}</span>
	     		<span class="pdescription">{translate key="plugins.generic.vgWort.notification.vgWortErrorExists"}</span>
	     	</div>
	     </div>
	{/if}
	
	{**{url|assign:pixelTagsGridUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="fetchGrid" escape=false}*}

	{capture assign="pixelTagsGridUrl"}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="fetchGrid" escape=false}{/capture}
	{load_url_in_div id="pixelTagsGridContainer" url=$pixelTagsGridUrl}
	
	<!-- /VG Wort -->

		{** {help file="settings/distribution-settings" section="pixeltags" class="pkp_help_tab"}
		* <pkp-form
		*	v-bind="components.{$smarty.const.ROUTE_COMPONENT}"
		*	@set="set"
		*/> 
		*}
</tab>
