<?php

/**
 * @file classes/form/VGWortSettingsForm.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: September 25, 2015
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.vgWort
 * @class VGWortSettingsForm
 *
 * @brief Form for journal managers to setup VG Wort plugin
 */


import('lib.pkp.classes.form.Form');

class VGWortSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function VGWortSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'vgWortUserId', 'required', 'plugins.generic.vgWort.manager.settings.vgWortUserIdRequired'));
		$this->addCheck(new FormValidator($this, 'vgWortUserPassword', 'required', 'plugins.generic.vgWort.manager.settings.vgWortUserPasswordRequired'));
		$this->addCheck(new FormValidator($this, 'vgWortEditors', 'required', 'plugins.generic.vgWort.manager.settings.vgWortEditorsRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'vgWortPixelTagMin', 'required', 'plugins.generic.vgWort.manager.settings.vgWortPixelTagMinRequired', '/^([1-9]|10)$/'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$editors = array();
		$users =& $roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $journalId);
		foreach ($users->toArray() as $user) {
			$editors[$user->getId()] = $user->getFullName();
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('editors', $editors);
		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($journalId, $fieldName));
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($journalId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	//
	// Private helper methods
	//
	/**
	 * Get all form fields and their types
	 * @return array
	 */
	function _getFormFields() {
		return array(
			'vgWortEditors' => 'object',
			'vgWortUserId' => 'string',
			'vgWortUserPassword' => 'string',
			'vgWortPixelTagMin' => 'int',
			'vgWortTestAPI' => 'bool'
		);
	}
}

?>
