{**
 * plugins/generic/vgWort/templates/distributionNavLink.tpl
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * Link (tab) on the Settins > Distribution page, for the pixel tags listing
 *
 *}
<li><a name="pixelTags" href="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.vgWort.controllers.grid.PixelTagGridHandler" op="pixelTagsTab" tab="pixelTags"}">{translate key="plugins.generic.vgWort.pixelTags"}</a></li>