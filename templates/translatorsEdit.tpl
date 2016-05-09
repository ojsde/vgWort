{**
 * templates/translatorsEdit.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: May 10, 2016
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit VG Wort tranlators
 *
 *}
<!-- VG Wort -->
<div id="vgWortTranslators">
<h3>{translate key="plugins.generic.vgWort.translators"}</h3>
<p>{translate key="plugins.generic.vgWort.translatorsDescription"}</p>
{foreach name=vgWortTranslators from=$vgWortTranslators key=vgWortTranslatorIndex item=vgWortTranslator}
<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="vgWortTranslators-$vgWortTranslatorIndex-firstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="vgWortTranslators[{$vgWortTranslatorIndex|escape}][firstName]" id="vgWortTranslators-{$vgWortTranslatorIndex|escape}-firstName" value="{$vgWortTranslator.firstName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="vgWortTranslators-$vgWortTranslatorIndex-middleName" key="user.middleName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="vgWortTranslators[{$vgWortTranslatorIndex|escape}][middleName]" id="vgWortTranslators-{$vgWortTranslatorIndex|escape}-lastName" value="{$vgWortTranslator.middleName|escape}" size="20" maxlength="40" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="vgWortTranslators-$vgWortTranslatorIndex-lastName" required="true" key="user.lastName"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="vgWortTranslators[{$vgWortTranslatorIndex|escape}][lastName]" id="vgWortTranslators-{$vgWortTranslatorIndex|escape}-lastName" value="{$vgWortTranslator.lastName|escape}" size="20" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="vgWortTranslators-$vgWortTranslatorIndex-email" required="true" key="user.email"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="vgWortTranslators[{$vgWortTranslatorIndex|escape}][email]" id="vgWortTranslators-{$vgWortTranslatorIndex|escape}-email" value="{$vgWortTranslator.email|escape}" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="vgWortTranslators-$vgWortTranslatorIndex-cardNo" key="plugins.generic.vgWort.cardNo"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="vgWortTranslators[{$vgWortTranslatorIndex|escape}][cardNo]" id="vgWortTranslators-{$vgWortTranslatorIndex|escape}-cardNo" value="{$vgWortTranslator.cardNo|escape}" size="30" maxlength="90" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label"> </td>
	<td width="80%" class="value"><input type="submit" name="delVGWortTranslator[{$vgWortTranslatorIndex|escape}]" value="{translate key="plugins.generic.vgWort.adelTranslator"}" class="button" /></td>
</tr>
<tr>
	<td colspan="2"><br/></td>
</tr>
</table>
{/foreach}

<p><input type="submit" class="button" name="addVGWortTranslator" value="{translate key="plugins.generic.vgWort.addTranslator"}" /></p>
</div>
<div class="separator"></div>
<!-- /VG Wort -->

