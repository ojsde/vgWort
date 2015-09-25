{**
 * templates/cardNoView.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: September 25, 2015
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * VG Wort cardNo view
 *
 *}
<!-- VG Wort -->
<tr valign="top">
	<td class="label">{translate key="plugins.generic.vgWort.cardNo"}</td>
	<td class="value">{$author->getData('cardNo')|escape|default:"&mdash;"}</td>
</tr>
<!-- /VG Wort -->

