<?php

/**
 * @file plugins/generic/vgWort/classes/VGWortEditorAction.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class VGWortEditorAction
 * @ingroup plugins_generic_vgWort
 *
 * @brief VGWortEditorAction class.
 */

define('PIXEL_SERVICE_WSDL', 'https://tom.vgwort.de/services/1.0/pixelService.wsdl');
define('MESSAGE_SERVICE_WSDL', 'https://tom.vgwort.de/services/1.13/messageService.wsdl');
/* just to test the plugin, please use the VG Wort test portal: */
define('PIXEL_SERVICE_WSDL_TEST', 'https://tom-test.vgwort.de/services/1.0/pixelService.wsdl');
define('MESSAGE_SERVICE_WSDL_TEST', 'https://tom-test.vgwort.de/services/1.13/messageService.wsdl');


class VGWortEditorAction {
	/** @var $_plugin VGWortPlugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $plugin Plugin
	 */
	function __construct($plugin) {
		$this->_plugin = $plugin;
	}

	/**
	 * Order pixel tags, uses VG Wort service.
	 * @param $contextId int
	 * @return array (boolean successful, mixed errorMsg or result object)
	 */
	function orderPixel($contextId) {
		$vgWortPlugin = $this->_plugin;
		$vgWortUserId = $vgWortPlugin->getSetting($contextId, 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($contextId, 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($contextId, 'vgWortTestAPI');
		$vgWortAPI = PIXEL_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = PIXEL_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
				return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}
			// check web service: availability and credentials
			$this->_checkService($vgWortUserId, $vgWortUserPassword, $vgWortAPI);
			$client = new SoapClient($vgWortAPI, array('login' => $vgWortUserId, 'password' => $vgWortUserPassword, 'exceptions' => true, 'trace' => 1, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
			$result = $client->orderPixel(array("count" => 1));
			return array(true, $result);
		}
		catch (SoapFault $soapFault) {
			if($soapFault->faultcode == 'noWSDL' || $soapFault->faultcode == 'httpError') {
				return array(false, $soapFault->faultstring);
			}
			$detail = $soapFault->detail;
			$function = $detail->orderPixelFault;
			return array(false, __('plugins.generic.vgWort.order.errorCode'.$function->errorcode, array('maxOrder' => $function->maxOrder)));
		}
	}

	/**
	 * Insert the ordered pixel tags in the DB
	 * @param $contextId int
	 * @param $result stdClass object
	 */
	function insertOrderedPixel($contextId, $result) {
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');
		$pixels = $result->pixels;
		$pixel = $pixels->pixel;
	    foreach ($pixel as $currPixel){
			$pixelTag = new PixelTag();
			$pixelTag->setContextId($contextId);
			$pixelTag->setDomain($result->domain);
			$pixelTag->setDateOrdered(strtotime($result->orderDateTime));
			$pixelTag->setStatus(PT_STATUS_AVAILABLE);
			$pixelTag->setTextType(TYPE_TEXT);
			$pixelTag->setPrivateCode($currPixel->privateIdentificationId);
			$pixelTag->setPublicCode($currPixel->publicIdentificationId);
			$pixelTagId = $pixelTagDao->insertObject($pixelTag);
	    }
	}

	/**
	 * Check if a pixel tag can be registered.
	 * @param $pixelTag PixelTag
	 * @return array (successful boolean, errorMsg string)
	 */
	function check($pixelTag) {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getByArticleId($pixelTag->getSubmissionId());
		if (!$publishedArticle) {
			return array(false, __('plugins.generic.vgWort.check.articleNotPublished'));
		} else {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $pixelTag->getContextId());
			if (!$issue->getPublished()) {
				return array(false, __('plugins.generic.vgWort.check.articleNotPublished'));
			} else {
				// get supported galleys
				$galleys = $publishedArticle->getGalleys();
				$supportedGalleys = array_filter($galleys, array($this->_plugin, 'galleySupported'));
				if (empty($supportedGalleys)) {
					return array(false, __('plugins.generic.vgWort.check.galleyRequired'));
				} else {
					// check that all existing card numbers are valid
					foreach ($publishedArticle->getAuthors() as $author) {
						$cardNo = $author->getData('vgWortCardNo');
						if (!empty($cardNo)) {
							$checkAuthorResult = $this->checkAuthor($pixelTag->getContextId(), $cardNo, $author->getLastName());
							if (!$checkAuthorResult[0]) {
								return array(false, $checkAuthorResult[1]);
							}
						}
					}
				}
			}
		}
		return array(true, '');
	}

	/**
	 * Register the pixel tag.
	 * @param $pixelTag PixelTag
	 * @param $request Request
	 */
	 function registerPixelTag($pixelTag, $request) {
		$pixelTagDao = DAORegistry::getDAO('PixelTagDAO');

		// check if the requirements for the registration are fulfilled
		$checkResult = $this->check($pixelTag, $request);

		$isError = !$checkResult[0];
		$errorMsg = null;
		if ($isError) {
			$errorMsg = $checkResult[1];
		} else {
			// register
			$registerResult = $this->newMessage($pixelTag, $request);

			$isError = !$registerResult[0];
			$errorMsg = $registerResult[1];
			if (!$isError) {
				// update the registered pixel tag
				$pixelTag->setDateRegistered(Core::getCurrentDate());
				$pixelTag->setMessage(NULL);
				$pixelTag->setStatus(PT_STATUS_REGISTERED_ACTIVE);
				$pixelTagDao->updateObject($pixelTag);

				// remove the VG Wort error notification for editors
				$this->_removeNotification($pixelTag);
				// set parameters fo rthe trivial notification for the current user
				$notificationType = NOTIFICATION_TYPE_SUCCESS;
				$notificationMsg = __('plugins.generic.vgWort.pixelTags.register.success');
			}
		}
		if ($isError) {
			// save the error message
			// the error message is in a specific language: either it is
			// already translated in the UI language of the last registration or
			// coming directly from the SOAP API
			$pixelTag->setMessage($errorMsg);
			$pixelTagDao->updateObject($pixelTag);

			// create the VG Wort error notification for editors
			$this->_createNotification($request, $pixelTag);
			// set parameters fo rthe trivial notification for the current user
			$notificationType = NOTIFICATION_TYPE_FORM_ERROR;
			$notificationMsg = $errorMsg;
		}

		// create the trivial notification for the current user i.e.
		// if the function is not called from the scheduled task
		$user = $request->getUser();
		if ($user) {
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification(
				$user->getId(), $notificationType, array('contents' => $notificationMsg)
			);
		}
	}

	/**
	 * Check if the card number is valid for the autor, uses VG Wort service.
	 * @param $contextId int
	 * @param $cardNo int VG Wort card number
	 * @param $lastName string author last name
	 * @return array (valid boolean, errorMsg string)
	 */
	function checkAuthor($contextId, $cardNo, $lastName) {
		$vgWortPlugin = $this->_plugin;
		$vgWortUserId = $vgWortPlugin->getSetting($contextId, 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($contextId, 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($contextId, 'vgWortTestAPI');
		$vgWortAPI = MESSAGE_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = MESSAGE_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
				return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}
			// check web service: availability and credentials
			$this->_checkService($vgWortUserId, $vgWortUserPassword, $vgWortAPI);
			$client = new SoapClient($vgWortAPI, array('login' => $vgWortUserId, 'password' => $vgWortUserPassword));
			$result = $client->checkAuthor(array("cardNumber" => $cardNo, "surName" => $lastName));
			return array($result->valid, __('plugins.generic.vgWort.check.notValid', array('surName' => $lastName)));
		}
		catch (SoapFault $soapFault) {
			if($soapFault->faultcode == 'noWSDL' || $soapFault->faultcode == 'httpError') {
				return array(false, $soapFault->faultstring);
			}
			$detail = $soapFault->detail;
			$function = $detail->checkAuthorFault;
			return array(false, __('plugins.generic.vgWort.check.errorCode'.$function->errorcode));
		}
	}

	/**
	 * Register a pixel tag with VG Wort, uses VG Worrt service.
	 * @param $pixelTag PixelTag
	 * @param $request Request
	 * @return array (successful boolean, errorMsg string)
	 * the check is already done, i.e. the article and the issue are published,
	 * there is a supported galley and the existing card numbers are valid
	 */
	function newMessage($pixelTag, $request) {
		$vgWortPlugin = $this->_plugin;

		$dispatcher = $request->getDispatcher();
		$context = $request->getContext();
		$contextId = $context->getId();

		$vgWortPlugin->import('classes.PixelTag');

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =  $publishedArticleDao->getByArticleId($pixelTag->getSubmissionId(), $contextId);
		assert($publishedArticle);

		// get all submission contributors
		$contributors = $publishedArticle->getAuthors();
		// get submission authors
		$submissionAuthors = array_filter($contributors, array($this, '_filterAuthors'));
		// get submission translators
		$submissionTranslators = array_filter($contributors, array($this, '_filterTranslators'));
		// there has to be either an author or a translator
		assert (!empty($submissionAuthors) || !empty($submissionTranslators));

		// get authors information: vg wort card number, first (max. 40 characters) and last name
		if (!empty($submissionAuthors)) {
			$authors = array('author' => array());
			foreach ($submissionAuthors as $author) {
				$cardNo = $author->getData('vgWortCardNo');
				if (!empty($cardNo)) {
					$authors['author'][] = array('cardNumber' => $author->getData('vgWortCardNo'), 'firstName' => substr($author->getFirstName(), 0, 39), 'surName' => $author->getLastName());
				} else {
					$authors['author'][] = array('firstName' => substr($author->getFirstName(), 0, 39), 'surName' => $author->getLastName());
				}
			}
			$parties = array('authors' => $authors);
		}
		// get translators information: vg wort card number, first (max. 40 characters) and last name
		if (!empty($submissionTranslators)) {
			$translators = array('translator' => array());
			foreach ($submissionTranslators as $translator) {
				$cardNo = $translator->getData('vgWortCardNo');
				if (!empty($cardNo)) {
					$translators['translator'][] = array('cardNumber' => $translator->getData('vgWortCardNo'), 'firstName' => substr($translator->getFirstName(), 0, 39), 'surName' => $translator->getLastName());
				} else {
					$translators['translator'][] = array('firstName' => substr($translator->getFirstName(), 0, 39), 'surName' => $translator->getLastName());
				}
			}
			$parties['translators'] = $translators;
		}

		// get supported galleys
		$galleys = (array) $publishedArticle->getGalleys();
		$supportedGalleys = array_filter($galleys, array($vgWortPlugin, 'galleySupported'));
		// construct the VG Wort webranges for the supported galleys
		$webranges = array('webrange' => array());
		foreach ($supportedGalleys as $supportedGalley) {
			$url = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'article', 'view', array($publishedArticle->getBestArticleId(), $supportedGalley->getBestGalleyId()));
			$webrange = array('url' => array($url));
			$webranges['webrange'][] = $webrange;
			$downlaodUrl1 = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'article', 'view', array($publishedArticle->getBestArticleId(), $supportedGalley->getBestGalleyId()));
			$webrange = array('url' => array($downlaodUrl1));
			$webranges['webrange'][] = $webrange;
			$downlaodUrl2 = $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'article', 'view', array($publishedArticle->getBestArticleId(), $supportedGalley->getBestGalleyId(), $supportedGalley->getFileId()));
			$webrange = array('url' => array($downlaodUrl2));
			$webranges['webrange'][] = $webrange;
		}

		// get the text/content:
		// if there is no German text, then try English, else any
		$deGalleys = array_filter($supportedGalleys, array($this, '_filterDEGalleys'));
		if (!empty($deGalleys)) {
			reset($deGalleys);
			$galley = current($deGalleys);
		} else {
			$enGalleys = array_filter($supportedGalleys, array($this, '_filterENGalleys'));
			if (!empty($enGalleys)) {
				reset($enGalleys);
				$galley = current($enGalleys);
			} else {
				reset($supportedGalleys);
				$galley = current($supportedGalleys);
			}
		}
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($contextId, $pixelTag->getSubmissionId());
		$galleyFile = $galley->getFile();
		$content = $submissionFileManager->readFileFromPath($galleyFile->getFilePath());
		$galleyFileType = $galleyFile->getFileType();
		if ($galleyFileType == 'text/html') {
			$text = array('plainText' => strip_tags($content));
		} elseif ($galleyFileType == 'application/pdf') {
			$text = array('pdf' => $content);
		} elseif ($galleyFileType == 'application/epub+zip') {
			$text = array('epub' => $content);
		}

		// get the title (max. 100 characters):
		// if there is no German title, then try English, else in the primary language
		$submissionLocale = $publishedArticle->getLocale();
		$primaryLocale = AppLocale::getPrimaryLocale();
		$title = $publishedArticle->getTitle('de_DE');
		if (!isset($title) || $title == '') $title = $publishedArticle->getTitle('en_US');
		if (!isset($title) || $title == '') $title = $publishedArticle->getTitle($submissionLocale);
		if (!isset($title) || $title == '') $title = $publishedArticle->getTitle($primaryLocale);
		$shortText = substr($title, 0, 99);

		// is it a poem
		$isLyric = ($pixelTag->getTextType() == TYPE_LYRIC);

		// create a VG Wort message
		$message = array('lyric' => $isLyric, 'shorttext' => $shortText, 'text' => $text);

		$vgWortUserId = $vgWortPlugin->getSetting($contextId, 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($contextId, 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($contextId, 'vgWortTestAPI');
		$vgWortAPI = MESSAGE_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = MESSAGE_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
				return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}

			// check web service: availability and credentials
			$this->_checkService($vgWortUserId, $vgWortUserPassword, $vgWortAPI);

			$client = new SoapClient($vgWortAPI, array('login' => $vgWortUserId, 'password' => $vgWortUserPassword));
			$result = $client->newMessage(array("parties" => $parties, "privateidentificationid" => $pixelTag->getPrivateCode(), "messagetext" => $message, "webranges" => $webranges));
			return array($result->status == 'OK', '');
		}
		catch (SoapFault $soapFault) {
			if($soapFault->faultcode == 'noWSDL' || $soapFault->faultcode == 'httpError') {
				return array(false, $soapFault->faultstring);
			}
			$detail = $soapFault->detail;
			$function = $detail->newMessageFault;
			if ($function->errorcode == 4) {
				return array(false, __('plugins.generic.vgWort.register.errorCode'.$function->errorcode, array('cardNumber' => $function->cardNumber, 'surName' => $function->surName)));
			}
			return array(false, __('plugins.generic.vgWort.register.errorCode'.$function->errorcode));
		}
	}

	/**
	 * Check if the contributor is an author.
	 * @param $contributor Author
	 * @return boolean
	*/
	function _filterAuthors($contributor) {
		$userGroup = $contributor->getUserGroup();
		return $userGroup->getData('nameLocaleKey') == 'default.groups.name.author';
	}

	/**
	 * Check if the contributor is a translator.
	 * @param $contributor Author
	 * @return boolean
	*/
	function _filterTranslators($contributor) {
		$userGroup = $contributor->getUserGroup();
		return $userGroup->getData('nameLocaleKey') == 'default.groups.name.translator';
	}

	/**
	 * Check if the galley locale is de_DE.
	 * @param $galley ArticleGalley
	 * @return boolean
	*/
	function _filterDEGalleys($galley) {
		return $galley->getLocale() == 'de_DE';
	}

	/**
	 * Check if the galley locale is en_US.
	 * @param $galley ArticleGalley
	 * @return boolean
	*/
	function _filterENGalleys($galley) {
		return $galley->getLocale() == 'en_US';
	}

	/**
	 * Check web service availability and credentials.
	 * @param $vgWortUserId string
	 * @param $vgWortUserPassword string
	 * @param $vgWortAPI string WSDL URL
	 */
	function _checkService($vgWortUserId, $vgWortUserPassword, $vgWortAPI) {
		// catch and throw an exception if the VG Wort server is down i.e.
		// the WSDL not found
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $vgWortAPI);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$wsdlContent = curl_exec($curl);
		$httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($httpStatusCode != 200) {
			throw new SoapFault('noWSDL', __('plugins.generic.vgWort.noWSDL', array('wsdl' => $vgWortAPI)));
		}
		curl_close($curl);
		// catch and throw an exception if the authentication or the authorization error occurs
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, str_replace('://', '://'.$vgWortUserId.':'.$vgWortUserPassword.'@', $vgWortAPI));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$wsdlContent = curl_exec($curl);
		$httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($httpStatusCode != 200) {
			throw new SoapFault('httpError', __('plugins.generic.vgWort.'.$httpStatusCode));
		}
		curl_close($curl);
	}

	/**
	 * Remove the VG Wort notification.
	 * @param $pixelTag PixelTag
	 */
	function _removeNotification($pixelTag) {
		$submission = $pixelTag->getSubmission();
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		// get the editors assigned to the submisison in the production stage
		$editorStageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($submission->getId(), WORKFLOW_STAGE_ID_PRODUCTION);
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		foreach ($editorStageAssignments as $editorStageAssignment) {
			$notificationDao->deleteByAssoc(
				ASSOC_TYPE_SUBMISSION,
				$submission->getId(),
				$editorStageAssignment->getUserId(),
				NOTIFICATION_TYPE_VGWORT_ERROR,
				$pixelTag->getContextId()
			);
		}
	}

	/**
	 * Create the VG Wort notification if none exists.
	 * The notification will only be created for editors that
	 * are assigned to the submission in the production stage.
	 * @param $request Request
	 * @param $pixelTag PixelTag
	 */
	function _createNotification($request, $pixelTag) {
		$submission = $pixelTag->getSubmission();
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		// get the editors assigned to the submisison in the production stage
		$editorStageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($submission->getId(), WORKFLOW_STAGE_ID_PRODUCTION);
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		foreach ($editorStageAssignments as $editorStageAssignment) {
			$notificationFactory = $notificationDao->getByAssoc(
				ASSOC_TYPE_SUBMISSION,
				$submission->getId(),
				$editorStageAssignment->getUserId(),
				NOTIFICATION_TYPE_VGWORT_ERROR,
				$pixelTag->getContextId()
			);
			if ($notificationFactory->wasEmpty()) {
				$notificationMgr = new NotificationManager();
				$notificationMgr->createNotification(
					$request,
					$editorStageAssignment->getUserId(),
					NOTIFICATION_TYPE_VGWORT_ERROR,
					$pixelTag->getContextId(),
					ASSOC_TYPE_SUBMISSION,
					$submission->getId(),
					NOTIFICATION_LEVEL_NORMAL,
					null,
					true
				);
			}
		}
	}

}

?>