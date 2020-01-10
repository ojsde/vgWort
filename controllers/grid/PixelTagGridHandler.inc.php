<?php
/**
 * @file plugins/generic/vgWort/controllers/grid/PixelTagGridHandler.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class PixelTagGridHandler
 * @ingroup plugins_generic_vgWort
 *
 * @brief The pixel tags listing.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.vgWort.controllers.grid.PixelTagGridRow');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class PixelTagGridHandler extends GridHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR),
			array(
				'fetchGrid', 'fetchRow', 'pixelTagsTab', 'registerPixelTag', 'statusMessage'
			)
		);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = NULL) {
		parent::initialize($request, $args);

		$router = $request->getRouter();
		$context = $request->getContext();

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_COMMON);

		// Grid columns.
		import('plugins.generic.vgWort.controllers.grid.PixelTagGridCellProvider');
		$pixelTagGridCellProvider = new PixelTagGridCellProvider();

		$this->setTitle('plugins.generic.vgWort.pixelTags');
		$this->addColumn(
			new GridColumn(
				'pixelTagCodes',
				'plugins.generic.vgWort.pixelTags.codes',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 20)
			)
		);
		$this->addColumn(
			new GridColumn(
				'title',
				'submission.title',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);
		$this->addColumn(
			new GridColumn(
				'domain',
				'plugins.generic.vgWort.pixelTag.domain',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
		$this->addColumn(
			new GridColumn(
				'status',
				'common.status',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
		$this->addColumn(
			new GridColumn(
				'message',
				'plugins.generic.vgWort.pixelTag.message',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
		$this->addColumn(
			new GridColumn(
				'dates',
				'plugins.generic.vgWort.pixelTags.dates',
				null,
				'controllers/grid/gridCell.tpl',
				$pixelTagGridCellProvider,
				array('html' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT,
						'width' => 10)
			)
		);
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	function getRowInstance() {
		return new PixelTagGridRow();
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}

	/**
	 * Get which columns can be used by users to filter data.
	 * @return array
	 */
	protected function getFilterColumns() {
		return array(
			PT_FIELD_PRIVCODE => __('plugins.generic.vgWort.pixelTag.privateCode'),
			PT_FIELD_PUBCODE => __('plugins.generic.vgWort.pixelTag.publicCode')
		);
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = array()) {
		$context = $request->getContext();
		$statusNames = array(
			PT_STATUS_ANY => __('plugins.generic.vgWort.pixelTag.any'),
			PT_STATUS_AVAILABLE => __('plugins.generic.vgWort.pixelTag.available'),
			PT_STATUS_UNREGISTERED_ACTIVE => __('plugins.generic.vgWort.pixelTag.unregistered.active'),
			PT_STATUS_UNREGISTERED_REMOVED => __('plugins.generic.vgWort.pixelTag.unregistered.removed'),
			PT_STATUS_REGISTERED_ACTIVE => __('plugins.generic.vgWort.pixelTag.registered.active'),
			PT_STATUS_REGISTERED_REMOVED => __('plugins.generic.vgWort.pixelTag.registered.removed'),
			PT_STATUS_FAILED => __('plugins.generic.vgWort.pixelTag.failed'),
		);
		$filterColumns = $this->getFilterColumns();
		$allFilterData = array_merge(
			$filterData,
			array(
				'columns' => $filterColumns,
				'status' => $statusNames,
				'gridId' => $this->getId(),
		));
		return parent::renderFilter($request, $allFilterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		$search = (string) $request->getUserVar('search');
		$column = (string) $request->getUserVar('column');
		$statusId = (string) $request->getUserVar('statusId');
		return array(
			'search' => $search,
			'column' => $column,
			'statusId' => $statusId,
		);
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 */
	function getFilterForm() {
		//return 'plugins/generic/vgWort/templates/controllers/grid/pixelTagGridFilter.tpl';
		$vgWortPlugin = PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$template = $vgWortPlugin->getTemplatePath() . 'controllers/grid/pixelTagGridFilter.tpl';
		return $template;
	}

	/**
	 * Process filter values, assigning default ones if
	 * none was set.
	 * @param $filter array
	 * @return array
	 */
	protected function getFilterValues($filter) {
		if (isset($filter['search']) && $filter['search']) {
			$search = $filter['search'];
		} else {
			$search = null;
		}
		if (isset($filter['column']) && $filter['column']) {
			$column = $filter['column'];
		} else {
			$column = null;
		}
		if (isset($filter['statusId']) && $filter['statusId'] != PT_STATUS_ANY) {
			$statusId = $filter['statusId'];
		} else {
			$statusId = null;
		}
		return array($search, $column, $statusId);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$sortBy = 'pixel_tag_id';
		$sortDirection = SORT_DIRECTION_DESC;
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$context = $request->getContext();
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		list($search, $column, $statusId) = $this->getFilterValues($filter);

		$pixelTags = $pixelTagDao->getPixelTagsByContextId(
			$context->getId(),
			$column,
			$search,
			$statusId,
			$rangeInfo,
			$sortBy,
			$sortDirection
		);
		return $pixelTags;
	}

	/**
	 * Show pixel tags listing
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function pixelTagsTab($args, $request) {
		$vgWortPlugin = PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('failedExists', $pixelTagDao->failedUnregisteredActiveExists($request->getContext()->getId()));
		return $templateMgr->fetchJson($vgWortPlugin->getTemplatePath() . 'pixelTagsTab.tpl');
	}

	/**
	 * Show pixel tag registration failure message
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function statusMessage($args, $request) {
		$vgWortPlugin = PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$pixelTagId = $request->getUserVar('pixelTagId');
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$pixelTag = $pixelTagDao->getById($pixelTagId);
		$statusMessage = !empty($pixelTag->getMessage()) ? $pixelTag->getMessage() : __('plugins.generic.vgWort.pixelTag.noStatusMessage');
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'statusMessage' => htmlentities($statusMessage),
		));
		return $templateMgr->fetchJson($vgWortPlugin->getTemplatePath() . 'statusMessage.tpl');
	}

	/**
	 * Register pixel tag action.
	 * @param $args array
	 * @param $request Request
	 * @return string JSON message.
	 */
	function registerPixelTag($args = array(), $request) {
		$pixelTagId = $request->getUserVar('rowId');
		if (!$pixelTagId) $pixelTagId = $request->getUserVar('pixelTagId');

		$context = $request->getContext();
		$contextId = $context->getId();

		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$pixelTag = $pixelTagDao->getById($pixelTagId, $contextId);
		// if the pixel tag exists, it is unregistered and not removed, register it
		if ($pixelTag && $pixelTag->getStatus() == PT_STATUS_UNREGISTERED_ACTIVE && !$pixelTag->getDateRemoved()) {
			$vgWortPlugin = PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
			import('plugins.generic.vgWort.classes.VGWortEditorAction');
			$vgWortEditorAction = new VGWortEditorAction($vgWortPlugin);
			$vgWortEditorAction->registerPixelTag($pixelTag, $request);
		}
		return DAO::getDataChangedEvent($pixelTagId);
	}

}

?>
