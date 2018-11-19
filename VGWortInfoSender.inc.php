<?php

/**
 * @file plugins/generic/vgWort/VGWortInfoSender.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class VGWortInfoSender
 * @ingroup plugins_generic_vgWort
 *
 * @brief Scheduled task to automatically register VG Wort pixel tags.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');


class VGWortInfoSender extends ScheduledTask {
	/** @var $_plugin VGWortPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function __construct($args) {
		PluginRegistry::loadCategory('generic');
		$plugin = PluginRegistry::getPlugin('generic', 'vgwortplugin'); /* @var $plugin VGWortPlugin */
		$this->_plugin = $plugin;

		if (is_a($plugin, 'VGWortPlugin')) {
			$plugin->addLocaleData();
			$plugin->import('classes.PixelTag');
			$plugin->import('classes.PixelTagDAO');
			$pixelTagDao = new PixelTagDAO($plugin->getName());
			$returner = DAORegistry::registerDAO('PixelTagDAO', $pixelTagDao);
		}

		parent::__construct($args);
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.vgwort.senderTask.name');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {
		if (!$this->_plugin) return false;

		$plugin = $this->_plugin;
		$journals = $this->_getJournals();

		$application = PKPApplication::getApplication();
		$request = $application->getRequest();

		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		import('plugins.generic.vgWort.classes.VGWortEditorAction');
		$vgWortEditorAction = new VGWortEditorAction($plugin);

		foreach ($journals as $journal) {
			// get this task's last run date
			$taskDao = DAORegistry::getDAO('ScheduledTaskDAO'); /* @var $taskDao ScheduledTaskDAO */
			$lastRunTime = $taskDao->getLastRunTime('plugins.generic.vgwort.VGWortInfoSender');
			$lastRunShortDate = date('Y-m-d', strtotime($lastRunTime));

			// In case when both plugin settings for the automatic registration (dateInYear and daysAfterPublication) exist,
			// and the dateInYear can be applied,
			// the daysAfterPublication does not have to be consiedered, because
			// with dateInYear applied all necessary pixel tags would be considered.
			// The variable thus checks if the dateInYear has just been applied.
			$justRegistered = false;

			// check the plugin setting dateInYear, if the pixel tags
			// should be registered on a specific day in a year
			$dateInYear = $plugin->getSetting($journal->getId(), 'dateInYear');
			if ($dateInYear != '') {
				$day = date('d', strtotime($dateInYear));
				$month = date('m', strtotime($dateInYear));
				$currYear = date("Y");
				//$checkDateInYear = new DateTime();
				$checkDateInYear = date("Y-m-d", mktime(0, 0, 0, $month, $day, $currYear));

				if (time() >= strtotime($checkDateInYear) && strtotime($lastRunShortDate) < strtotime($checkDateInYear)) {
					// get all unregistered and active pixel tags,
					// the failed pixel tags (containing a message) are considered here as well
					$unregisteredActivePixelTags = $pixelTagDao->getAllForRegistration($journal->getId());
					// register the pixel tags
					$this->_registerPixelTags($unregisteredActivePixelTags);
					$justRegistered = true;
				}
			}

			// check the plugin setting daysAfterPublication, if the pixel tags
			// should be registered in specific time after the article publication
			$daysAfterPublication = $plugin->getSetting($journal->getId(), 'daysAfterPublication');
			$publicationDate = date('Y-m-d', strtotime('-'.$daysAfterPublication.' days', time()));

			if ($daysAfterPublication > 0 && !$justRegistered) {
				// get all unregistered, active and failed pixel tags
				// assigned to articles that are published before the specified date
				$unregisteredActivePixelTags = $pixelTagDao->getAllForRegistration($journal->getId(), $publicationDate);
				// register the pixel tags
				$this->_registerPixelTags($unregisteredActivePixelTags);
			}
		}
		return true;
	}

	/**
	 * Register the pixel tags.
	 * @param $pixelTags DAOResultFactory
	 */
	function _registerPixelTags($pixelTags) {
		while ($pixelTag = $pixelTags->next()) {
			// double check that the pixel tag was not removed
			if (!$pixelTag->getDateRemoved()) {
				$vgWortEditorAction->registerPixelTag($pixelTag, $request);
			}
		}
	}

	/**
	 * Get all journals that meet the requirements to have
	 * their VG Wort pixel tags automatically registered.
	 * @return array
	 */
	function _getJournals() {
		$plugin = $this->_plugin;
		$contextDao = Application::getContextDAO(); /* @var $contextDao JournalDAO */
		$journalFactory = $contextDao->getAll(true);

		$journals = array();
		while($journal = $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$plugin->getSetting($journalId, 'vgWortUserId') || !$plugin->getSetting($journalId, 'vgWortUserPassword') ||
				($plugin->getSetting($journalId, 'dateInYear') == '' && !$plugin->getSetting($journalId, 'daysAfterPublication'))) continue;
			$journals[] = $journal;
		}
		return $journals;
	}

}
?>
