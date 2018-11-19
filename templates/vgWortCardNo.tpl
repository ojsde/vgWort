{**
 * plugins/generic/vgWort/templates/vgWortCardNo.tpl
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * Edit VG Wort card number for an user or author (in the user, profile and author form)
 *
 *}
<!-- VG Wort -->
{fbvFormSection title=$vgWortFieldTitle}
	{fbvElement type="text" label="plugins.generic.vgWort.cardNo.description" id="vgWortCardNo" name="vgWortCardNo" value=$vgWortCardNo maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
{/fbvFormSection}
<!-- /VG Wort -->

