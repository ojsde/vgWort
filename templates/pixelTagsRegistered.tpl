{**
 * @file templates/pixelTagsRegistered.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: May 10, 2016
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of registered pixel tags.
 *
 *}
{assign var="pageTitle" value="plugins.generic.vgWort.pixelTagsRegistered"}

{include file="../plugins/generic/vgWort/templates/pixelTagsHeader.tpl"}
<br />
{include file="../plugins/generic/vgWort/templates/pixelTagSearch.tpl"}
<br />
<br />

{assign var=colspan value="6"}
{assign var=colspanPage value="3"}
<div id="pixelTags">
<table width="100%" class="listing">
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="25%">{translate key="plugins.generic.vgWort.pixelTag.privateCode"}</td>
		<td width="10%">{translate key="article.authors"}</td>
		<td width="10%">{translate key="plugins.generic.vgWort.pixelTag.translators"}</td>
		<td width="30%">{translate key="article.title"}</td>
		<td width="10%">{translate key="plugins.generic.vgWort.pixelTag.date_registered"}</td>
		<td width="15%" align="right">{translate key="common.status"} / {translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="headseparator">&nbsp;</td>
	</tr>

{iterate from=pixelTags item=pixelTag}
{assign var=article value=$pixelTag->getArticle()}
	{assign var=translators value=""}
	{foreach name=vgWortTranslators from=$article->getData('vgWortTranslators') key=vgWortTranslatorIndex item=vgWortTranslator}
		{if $vgWortTranslatorIndex != 0}
			{assign var=translators value="`$translators`, "}
		{/if}
		{assign var=translators value="`$translators``$vgWortTranslator.lastName`"}
	{/foreach}
	<tr valign="top">
		<td>{$pixelTag->getPrivateCode()|escape}</td>
		<td>{$article->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td>{$translators|truncate:40:"..."|escape|default:"&mdash;"}</td>
		<td><a href="{url op="submission" path=$pixelTag->getArticleId()}" class="action">{$article->getLocalizedTitle()|strip_unsafe_html|truncate:40:"..."}</a></td>
		<td>{$pixelTag->getDateRegistered()|date_format:$dateFormatShort}</td>
		<td align="right">
			{if $pixelTag->getDateRemoved()}{translate key="plugins.generic.vgWort.pixelTag.removed"}{else}{translate key="plugins.generic.vgWort.pixelTag.active"}{/if}
			<a href="{url op="pixelTags" path="registered" action=remove pixelTagId=$pixelTag->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.vgWort.confirmRemove"}')" class="action">{translate key="plugins.generic.vgWort.editor.remove"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="{if $pixelTags->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $pixelTags->wasEmpty()}
	<tr>
		<td colspan="{$colspan}" class="nodata">{translate key="plugins.generic.vgWort.none"}</td>
	</tr>
	<tr>
		<td colspan="{$colspan}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="{$colspanPage}" align="left">{page_info iterator=$pixelTags}</td>
		<td colspan="{$colspanPage+1}" align="right">{page_links anchor="pixelTags" name="pixelTags" iterator=$pixelTags}</td>
	</tr>
{/if}
</table>
</div>
<br />

<p><a class="action" href="{url op="createPixelTag"}">{translate key="plugins.generic.vgWort.editor.manuallyInsert"}</a></p>

{include file="common/footer.tpl"}
