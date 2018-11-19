<?php

/**
 * @file plugins/generic/vgWort/controllers/grid/PixelTagGridRow.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class PixelTagGridRow
 * @ingroup plugins_generic_vgWort
 *
 * @brief Handle pixel tag grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class PixelTagGridRow extends GridRow {

	/*
	 * @copydoc GridRow::initialize
	 */
	function initialize($request, $template = null) {
		parent::initialize($request);

		$router = $request->getRouter();
		$pixelTagId = $this->getId();
		if (!empty($pixelTagId) && is_numeric($pixelTagId)) {
			$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
			$pixelTag = $pixelTagDao->getById($pixelTagId);
			$pixelTagStatus = $pixelTag->getStatus();
			switch ($pixelTagStatus) {
				// only consider active pixel tags
				case PT_STATUS_UNREGISTERED_ACTIVE:
					if ($pixelTag->isPublished()) {
						$this->addAction(
							new LinkAction(
								'register',
								new RemoteActionConfirmationModal(
									$request->getSession(),
									__('plugins.generic.vgWort.pixelTags.register.confirm'),
									__('plugins.generic.vgWort.pixelTags.register'),
									$router->url($request, null, null, 'registerPixelTag', null, array('pixelTagId' => $pixelTagId)),
									'modal_confirm'
								),
								__('plugins.generic.vgWort.pixelTags.register'),
								'advance'
							)
						);
					}
					break;
				default:
					break;
			}
		}
	}
}

?>
