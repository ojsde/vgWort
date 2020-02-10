<?php

/**
 * @file plugins/generic/vgWort/classes/form/VGWortSettingsForm.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class VGWortSettingsForm
 * @ingroup plugins_generic_vgWort
 *
 * @brief Form to set up VG Wort plugin
 */


import('lib.pkp.classes.form.Form');

class VGWortSettingsForm extends Form {

	/** @var $contextId int */
	var $contextId;

	/** @var $plugin VGWortPlugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin VGWortPlugin
	 * @param $contextId int
	 */
	function __construct($plugin, $contextId) {

	    $this->contextId = $contextId;
		$this->plugin = $plugin;
		
		parent::__construct(method_exists($plugin, 'getTemplateResource') ? $plugin->getTemplateResource('settingsForm.tpl') : $plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'vgWortUserId', 'required', 'plugins.generic.vgWort.settings.vgWortUserIdRequired'));
		$this->addCheck(new FormValidator($this, 'vgWortUserPassword', 'required', 'plugins.generic.vgWort.settings.vgWortUserPasswordRequired'));
		//$this->addCheck(new FormValidator($this, 'vgWortPrivacy', 'required', 'plugins.generic.vgWort.settings.vgWortPrivacyRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$fieldValue = $this->plugin->getSetting($this->contextId, $fieldName);
			if ($fieldName == 'daysAfterPublication') {
				if (!$fieldValue) $fieldValue = '';
			}
			/*
			elseif ($fieldName =='vgWortPrivacy') {
				if (empty($fieldValue)) {
					$defaultLocale = 'de_DE';
					$defaultTranslatedText = __('plugins.generic.vgWort.settings.vgWortPrivacy.content', array(), $defaultLocale);
					$fieldValue = array($defaultLocale => $defaultTranslatedText);
					$context = $request->getContext();
					$supportedFormLocales = $context->getSupportedFormLocales();
					foreach ($supportedFormLocales as $supportedFormLocale) {
						if ($supportedFormLocale != $defaultLocale) {
							$this->plugin->addLocaleData($supportedFormLocale);
							$translatedText = __('plugins.generic.vgWort.settings.vgWortPrivacy.content', array(), $supportedFormLocale);
							if (substr($translatedText, 0, 2) === '##') $translatedText = $defaultTranslatedText;
							$fieldValue[$supportedFormLocale] = $translatedText;
						}
					}
				}
			}
			*/
			$this->setData($fieldName, $fieldValue);
		}
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = NULL, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * Save settings.
	 */
	function execute(...$functionArgs) {
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			if ($fieldName == 'dateInYear') {
			}
			$this->plugin->updateSetting($this->contextId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	/**
	 * Get all form fields and their types
	 * @return array
	 */
	function _getFormFields() {
		return array(
			'vgWortUserId' => 'string',
			'vgWortUserPassword' => 'string',
			'dateInYear' => 'string',
			'daysAfterPublication' => 'int',
			//'vgWortPrivacy' => 'object',
			'vgWortTestAPI' => 'bool'
		);
	}
}

?>
