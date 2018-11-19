<?php

/**
 * @file plugins/generic/vgWort/controllers/grid/PixelTagGridCellProvider.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class PixelTagGridCellProvider
 * @ingroup plugins_generic_vgWort
 *
 * @brief Grid cell provider for the pixel tags listing
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class PixelTagGridCellProvider extends GridCellProvider {

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Get cell actions associated with this row/column combination
	 *
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$pixelTag = $row->getData();
		$columnId = $column->getId();
		assert(is_a($pixelTag, 'PixelTag') && !empty($columnId));

		import('lib.pkp.classes.linkAction.request.RedirectAction');
		switch ($columnId) {
			case 'message':
				if (!empty($pixelTag->getMessage())) {
					$router = $request->getRouter();
					return array(
						new LinkAction(
							'failureMessage',
							new AjaxModal(
								$router->url($request, null, null, 'statusMessage', null, array('pixelTagId' => $pixelTag->getId())),
								__('plugins.generic.vgWort.pixelTag.failed'),
								'failureMessage'
							),
							__('plugins.generic.vgWort.pixelTag.failed')
						)
					);
				}
				break;
			case 'title':
				$this->_titleColumn = $column;
				$submission = $pixelTag->getSubmission();
				if ($submission) {
					$title = $submission->getLocalizedTitle();
					if (empty($title)) $title = __('common.untitled');
					$authorsInTitle = $submission->getShortAuthorString();
					$title = $authorsInTitle . '; ' . $title;
					import('classes.core.ServicesContainer');
					return array(
						new LinkAction(
							'itemWorkflow',
							new RedirectAction(
								ServicesContainer::instance()->get('submission')->getWorkflowUrlByUserRoles($submission)
							),
							$title
						)
					);
				}
				break;
		}
		return parent::getCellActions($request, $row, $column, $position);
	}

	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$pixelTag = $row->getData();
		$columnId = $column->getId();
		assert(is_a($pixelTag, 'PixelTag') && !empty($columnId));
		switch ($columnId) {
			case 'pixelTagCodes':
				return array('label' => $pixelTag->getPrivateCode() .'<br />' . $pixelTag->getPublicCode());
			case 'domain':
				return array('label' => $pixelTag->getDomain());
			case 'dates':
				$dateOrdered = $pixelTag->getDateOrdered() ? strftime(Config::getVar('general', 'date_format_short'), strtotime($pixelTag->getDateOrdered())) : '&mdash;';
				$dateAssigned = $pixelTag->getDateAssigned() ? strftime(Config::getVar('general', 'date_format_short'), strtotime($pixelTag->getDateAssigned())) : '&mdash;';
				$dateRegistered = $pixelTag->getDateRegistered() ? strftime(Config::getVar('general', 'date_format_short'), strtotime($pixelTag->getDateRegistered())) : '&mdash;';
				$dateRemoved = $pixelTag->getDateRemoved() ? strftime(Config::getVar('general', 'date_format_short'), strtotime($pixelTag->getDateRemoved())) : '&mdash;';
				return array('label' => $dateOrdered .'<br />' . $dateAssigned .'<br />' . $dateRegistered .'<br />' . $dateRemoved);
			case 'status':
				return array('label' => $pixelTag->getStatusString());
			case 'message':
				if (!empty($pixelTag->getMessage())) {
					return array('label' => '');
				}
				return array('label' => '&mdash');
			case 'title':
				return array('label' => '');
			default: assert(false); break;
		}
	}
}

?>
