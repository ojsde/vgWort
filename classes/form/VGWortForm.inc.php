<?php

/**
 * @file classes/components/form/context/FormComponentForm.inc.php
 */
use \PKP\components\forms\FormComponent;

/**
 * Each form should have a unique ID defined in a constant
 */
define('FORM_VGWORT', 'vgwortform');

class VGWortForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_VGWORT;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context Context Journal or Press to change settings for
	 * @param $submission Submission object
	 */
	public function __construct($action, $locales, $context, $submission) {
		$this->action = $action;
		$this->locales = $locales;
		$this->successMessage = "Success!";

		$this->pixelTagStatusLabels = [
			0 => __('plugins.generic.vgWort.pixelTag.representation.notAssigned'),
			PT_STATUS_REGISTERED_ACTIVE => __('plugin.generic.vgWort.pixelTag.status.registeredactive'),
			PT_STATUS_UNREGISTERED_ACTIVE => __('plugin.generic.vgWort.pixelTag.status.unregisteredactive'),
			PT_STATUS_REGISTERED_REMOVED => __('plugin.generic.vgWort.pixelTag.status.registeredremoved'),
			PT_STATUS_UNREGISTERED_REMOVED => __('plugin.generic.vgWort.pixelTag.status.unregisteredremoved')
		];

        $pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
        $pixelTag = $pixelTagDao->getPixelTagBySubmissionId($submission->getId());
		$publication = $submission->getLatestPublication();

        if ($pixelTag == NULL) {
            $pixelTagStatus = 0;
            $pixelTagAssigned = false;
            $pixelTagRemoved = false;
            $publication->setData('vgWort::pixeltag::status', $pixelTagStatus);
            $publication->setData('vgWort::pixeltag::assign', $pixelTagAssigned);
            $publication->setData('vgWort::pixeltag::remove', $pixelTagRemoved);
        } else {
            $pixelTagStatus = $pixelTag->getStatus();
            $publication->setData('vgWort::pixeltag::status', $pixelTagStatus);
        }
        if ($pixelTagStatus == PT_STATUS_UNREGISTERED_ACTIVE || $pixelTagStatus == PT_STATUS_REGISTERED_ACTIVE) {
            $pixelTagAssigned = true;
            $pixelTagRemoved = false;
            $publication->setData('vgWort::pixeltag::assign', $pixelTagAssigned);
            $publication->setData('vgWort::pixeltag::remove', $pixelTagRemoved);
        } elseif ($pixelTagStatus == PT_STATUS_UNREGISTERED_REMOVED || $pixelTagStatus == PT_STATUS_REGISTERED_REMOVED) {
            $pixelTagAssigned = false;
            $pixelTagRemoved = true;
            $publication->setData('vgWort::pixeltag::assign', $pixelTagAssigned);
            $publication->setData('vgWort::pixeltag::remove', $pixelTagRemoved);
        }

		$this->addField(new \PKP\components\forms\FieldSelect('vgWort::texttype', [
			'label' => __('plugins.generic.vgWort.pixelTag.textType'),
			'description' =>  __('plugins.generic.vgWort.pixelTag.textType.description'),
			'value' => TYPE_TEXT,
			'options' => [
				[
					'value' => TYPE_TEXT,
					'label' => __('plugins.generic.vgWort.pixelTag.textType.text')
				],
				[
					'value' => TYPE_LYRIC,
					'label' => __('plugins.generic.vgWort.pixelTag.textType.lyric')
				]
			],
		]));

		$this->addField(new \PKP\components\forms\FieldOptions('vgWort::pixeltag::assign', [
            'label' => __('plugins.generic.vgWort.pixelTag'),
            'description' => "Status: " . $this->pixelTagStatusLabels[$pixelTagStatus],
            'value' => $pixelTagAssigned,
            'type' => 'radio',
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.generic.vgWort.pixelTag.assign'),
                    'disabled' => $pixelTagAssigned
                ],
                [
                    'value' => false,
                    'label' => __('plugins.generic.vgWort.pixelTag.remove'),
                    'disabled' => $pixelTagRemoved || $pixelTagStatus == 0
                ],
            ],
        ]));
	}
}
