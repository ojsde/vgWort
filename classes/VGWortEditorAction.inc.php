<?php

/**
 * @file classes/VGWortEditorAction.inc.php
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: May 10, 2016
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.vgWort
 * @class VGWortEditorAction
 *
 * @brief VGWortEditorAction class.
 */

define('PIXEL_SERVICE_WSDL', 'https://tom.vgwort.de/services/1.0/pixelService.wsdl');
define('MESSAGE_SERVICE_WSDL', 'https://tom.vgwort.de/services/1.2/messageService.wsdl');
/* just to test the plug-in, please use the VG Wort test portal: */
define('PIXEL_SERVICE_WSDL_TEST', 'https://tom-test.vgwort.de/services/1.0/pixelService.wsdl');
define('MESSAGE_SERVICE_WSDL_TEST', 'https://tom-test.vgwort.de/services/1.2/messageService.wsdl');

class VGWortEditorAction {

	/**
	 * Constructor.
	 */
	function VGWortEditorAction() {
	}

	private function filterGalleys($galley) {
		return $galley->isPdfGalley() || $galley->isHTMLGalley();
	}
	private function filterDEGalleys($galley) {
		return $galley->getLocale() == 'de_DE';
	}
	private function filterENGalleys($galley) {
		return $galley->getLocale() == 'en_US';
	}


	/**
	 * Actions.
	 */

	/**
	 * Order pixel tags.
	 * @param $journalId int
	 * @param $count int count of new pixel tags to be ordered
	 * @return array (boolean successful, mixed errorMsg or result object)
	 */
	function orderPixel($journalId, $count) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$vgWortUserId = $vgWortPlugin->getSetting($journalId, 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($journalId, 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($journalId, 'vgWortTestAPI');
		$vgWortAPI = PIXEL_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = PIXEL_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
        		return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}
			// catch and throw an exception if the VG Wort server is down
			if(!@file_get_contents($vgWortAPI)) {
				throw new SoapFault('noWSDL', __('plugins.generic.vgWort.noWSDL', array('wsdl' => $vgWortAPI)));
    		}
    		// catch and throw an exception if the authentication or the authorization error occurs
    		if(!@fopen(str_replace('://', '://'.$vgWortUserId.':'.$vgWortUserPassword.'@', $vgWortAPI), 'r')) {
				$httpString = explode(" ", $http_response_header[0]);
				throw new SoapFault('httpError', __('plugins.generic.vgWort.'.$httpString[1]));
    		}
			$client = new SoapClient($vgWortAPI, array('login' => $vgWortUserId, 'password' => $vgWortUserPassword, 'exceptions' => true, 'trace' => 1, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
			$result = $client->orderPixel(array("count" => $count));
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
	 * Insert the ordered pixel tags
	 * @param $journalId int
	 * @param $result stdClass Object
	 */
	function insertOrderedPixel($journalId, $result) {
		$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
		$pixels = $result->pixels;
		$pixel = $pixels->pixel;
	    foreach ($pixel as $currPixel){
			$pixelTag = new PixelTag();
			$pixelTag->setJournalId($journalId);
			$pixelTag->setDomain($result->domain);
			$pixelTag->setDateOrdered(strtotime($result->orderDateTime));
			$pixelTag->setStatus(PT_STATUS_AVAILABLE);
			$pixelTag->setTextType(TYPE_DEFAULT);
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
	function check(&$pixelTag) {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = & $publishedArticleDao->getPublishedArticleByArticleId($pixelTag->getArticleId());
		// the article has to be published
		if (!isset($publishedArticle)) {
			return array(false, __('plugins.generic.vgWort.check.articleNotPublished'));
		} else {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue = & $issueDao->getIssueById($publishedArticle->getIssueId(), $pixelTag->getJournalId());
			// the issue has to be published
			if (!$issue->getPublished()) {
				return array(false, __('plugins.generic.vgWort.check.articleNotPublished'));
			} else {
				// there has to be a HTML or a PDF galley -- VG Wort concerns only HTML und PDF formats
				// get all galleys
				$galleys =& $publishedArticle->getGalleys();
				// filter HTML und PDF galleys
				$filteredGalleys = array_filter($galleys, array($this, 'filterGalleys'));
				if (empty($filteredGalleys)) {
					return array(false, __('plugins.generic.vgWort.check.galleyRequired'));
				} else {
					// All existing vg wort card numbers have to be valid
					// For authors
					foreach ($publishedArticle->getAuthors() as $author) {
						$cardNo = $author->getData('cardNo');
						if (!empty($cardNo)) {
							// is the card number valid?
							$checkAuthorResult = $this->checkAuthor($pixelTag->getJournalId(), $cardNo, $author->getLastName());
							if (!$checkAuthorResult[0]) {
								return array(false, $checkAuthorResult[1]);
							}
						}
					}
					// For translators
					$vgWortTranslators = $publishedArticle->getData('vgWortTranslators');
					if ($vgWortTranslators && !empty($vgWortTranslators)) {
						foreach ($vgWortTranslators as $vgWortTranslator) {
							$cardNo = $vgWortTranslator['cardNo'];
							if (!empty($cardNo)) {
								// is the card number valid?
								$checkAuthorResult = $this->checkAuthor($pixelTag->getJournalId(), $cardNo, $vgWortTranslator['lastName']);
								if (!$checkAuthorResult[0]) {
									return array(false, $checkAuthorResult[1]);
								}
							}
						}
					}
				}
			}
		}
		return array(true, '');
	}

	/**
	 * Check if the card number is valid for the autor.
	 * @param $journalId int
	 * @param $cardNo int VG Wort card number
	 * @param $surName string author last name
	 * @return array (valid boolean, errorMsg string)
	 */
	function checkAuthor($journalId, $cardNo, $surName) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$vgWortUserId = $vgWortPlugin->getSetting($journalId, 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($journalId, 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($journalId, 'vgWortTestAPI');
		$vgWortAPI = MESSAGE_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = MESSAGE_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
        		return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}
			// catch and throw an exception if the VG Wort server is down
			if(!@file_get_contents($vgWortAPI)) {
				throw new SoapFault('noWSDL', __('plugins.generic.vgWort.noWSDL', array('wsdl' => $vgWortAPI)));
    		}
    		// catch and throw an exception if the authentication or the authorization error occurs
			if(!@fopen(str_replace('://', '://'.$vgWortUserId.':'.$vgWortUserPassword.'@', $vgWortAPI), 'r')) {
				$httpString = explode(" ", $http_response_header[0]);
				throw new SoapFault('httpError', __('plugins.generic.vgWort.'.$httpString[1]));
    		}
    		$client = new SoapClient($vgWortAPI, array('login' => $vgWortUserId, 'password' => $vgWortUserPassword));
			$result = $client->checkAuthor(array("cardNumber" => $cardNo, "surName" => $surName));
			return array($result->valid, __('plugins.generic.vgWort.check.notValid', array('surName' => $surName)));
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
	 * Register a pixel tag.
	 * @param $pixelTagId int pixel tag id
	 * @param $request Request
	 * @return array (successful boolean, errorMsg string)
	 * the check is already done, i.e. the article and the issue are published,
	 * there is a HTML or a PDF galley and the existing card numbers are valid
	 */
	function newMessage($pixelTagId, &$request) {

		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$journalId = $journal->getId();

		$vgWortPlugin =& PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$vgWortPlugin->import('classes.PixelTag');
		$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
		$pixelTag =& $pixelTagDao->getPixelTag($pixelTagId);

		// get the published article
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = & $publishedArticleDao->getPublishedArticleByArticleId($pixelTag->getArticleId());

		// get authors information: vg wort card number, first (max. 40 characters) and last name
		$authors = array('author' => array());
		foreach ($publishedArticle->getAuthors() as $author) {
			$cardNo = $author->getData('cardNo');
			if (!empty($cardNo)) {
				$authors['author'][] = array('cardNumber' => $author->getData('cardNo'), 'firstName' => substr($author->getFirstName(), 0, 39), 'surName' => $author->getLastName());
			} else {
				$authors['author'][] = array('firstName' => substr($author->getFirstName(), 0, 39), 'surName' => $author->getLastName());
			}
		}
		$parties = array('authors' => $authors);
		// get translators information: vg wort card number, first (max. 40 characters) and last name
		$vgWortTranslators = $publishedArticle->getData('vgWortTranslators');
		if ($vgWortTranslators && !empty($vgWortTranslators)) {
			$translators = array('translator' => array());
			foreach ($vgWortTranslators as $vgWortTranslator) {
				$cardNo = $vgWortTranslator['cardNo'];
				if (!empty($cardNo)) {
					$translators['translator'][] = array('cardNumber' => $vgWortTranslator['cardNo'], 'firstName' => substr($vgWortTranslator['firstName'], 0, 39), 'surName' => $vgWortTranslator['lastName']);
				} else {
					$translators['translator'][] = array('firstName' => substr($vgWortTranslator['firstName'], 0, 39), 'surName' => $vgWortTranslator['lastName']);
				}
			}
			$parties['translators'] = $translators;
		}

		// get all galleys
		$galleys =& $publishedArticle->getGalleys();
		// filter HTML und PDF galleys -- VG Wort concerns only HTML und PDF formats
		$filteredGalleys = array_filter($galleys, array($this, 'filterGalleys'));
		// construct the VG Wort webranges for all HTML and PDF galleys
		$webranges = array('webrange' => array());
		foreach ($filteredGalleys as $filteredGalley) {
			$url = $request->url($request->getRequestedContextPath(), 'article', 'view', array($publishedArticle->getBestArticleId($journal), $filteredGalley->getBestGalleyId($journal)));
			$webrange = array('url' => array($url));
			$webranges['webrange'][] = $webrange;
			if ($filteredGalley->isPdfGalley()) {
				$downlaodUrl = $request->url($request->getRequestedContextPath(), 'article', 'download', array($publishedArticle->getBestArticleId($journal), $filteredGalley->getBestGalleyId($journal)));
				$webrange = array('url' => array($downlaodUrl));
				$webranges['webrange'][] = $webrange;
			}
		}

		// get the text/content:
		// if there is no German text, then try English, else anyone
		$deGalleys = array_filter($filteredGalleys, array($this, 'filterDEGalleys'));
		if (!empty($deGalleys)) {
			reset($deGalleys);
			$galley = current($deGalleys);
		} else {
			$enGalleys = array_filter($filteredGalleys, array($this, 'filterENGalleys'));
			if (!empty($enGalleys)) {
				reset($enGalleys);
				$galley = current($enGalleys);
			} else {
				reset($filteredGalleys);
				$galley = current($filteredGalleys);
			}
		}
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($pixelTag->getArticleId());
		$content =& $articleFileManager->readFile($galley->getFileId());
		if ($galley->isHTMLGalley()) {
			$text = array('plainText' => strip_tags($content));
		} else { // PDF
			$text = array('pdf' => $content);
		}

		// get the title (max. 100 characters):
		// if there is no German title, then try English, else in the primary language
		$articleLocale = $publishedArticle->getLocale();
		$primaryLocale = AppLocale::getPrimaryLocale();
		$title = $publishedArticle->getTitle('de_DE');
		if (!isset($title) || $title == '') $title = $publishedArticle->getTitle('en_US');
		if (!isset($title) || $title == '') $title = $publishedArticle->getTitle($articleLocale);
		if (!isset($title) || $title == '') $title = $publishedArticle->getTitle($primaryLocale);
		$shortText = substr($title, 0, 99);

		// is it a poem
		$isLyric = ($pixelTag->getTextType() == TYPE_LYRIC);

		// create a VG Wort message
		$message = array('lyric' => $isLyric, 'shorttext' => $shortText, 'text' => $text);

		$vgWortUserId = $vgWortPlugin->getSetting($pixelTag->getJournalId(), 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($pixelTag->getJournalId(), 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($journalId, 'vgWortTestAPI');
		$vgWortAPI = MESSAGE_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = MESSAGE_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
        		return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}
			// catch and throw an exception if the VG Wort server is down
			if(!@file_get_contents($vgWortAPI)) {
				throw new SoapFault('noWSDL', __('plugins.generic.vgWort.noWSDL', array('wsdl' => $vgWortAPI)));
    		}
    		// catch and throw an exception if the authentication or the authorization error occurs
    		if(!@fopen(str_replace('://', '://'.$vgWortUserId.':'.$vgWortUserPassword.'@', $vgWortAPI), 'r')) {
				$httpString = explode(" ", $http_response_header[0]);
				throw new SoapFault('httpError', __('plugins.generic.vgWort.'.$httpString[1]));
    		}
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
			return array(false, __('plugins.generic.vgWort.register.errorCode'.$function->errorcode, array('cardNumber' => $function->cardNumber, 'surName' => $function->surName)));
		}
	}

	/**
	 * Send a registration notification email to the authors.
	 * @param $journal Journal
	 * @param $pixelTag PixelTag
	 */
	function notifyAuthors(&$journal, &$pixelTag) {
		import('classes.mail.MailTemplate');
		$email = new MailTemplate('VGWORT_REGISTER_NOTIFY');
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$email->addCc($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$article =& $pixelTag->getArticle();
		foreach ($article->getAuthors() as $author) {
			$email->addRecipient($author->getEmail(), $author->getFullName());
			$emailParamArray = array(
					'authorName' => $author->getFullName(),
					'privateCode' => $pixelTag->getPrivateCode(),
					'articleTitle' => $article->getLocalizedTitle(),
					'journalName' => $journal->getLocalizedTitle(),
					'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle()
			);
			$email->assignParams($emailParamArray);
			$email->send();
		}
		$vgWortTranslators = $article->getData('vgWortTranslators');
		if ($vgWortTranslators && !empty($vgWortTranslators)) {
			foreach ($vgWortTranslators as $vgWortTranslator) {
				$vgWortTranslatorName = $vgWortTranslator['firstName'] . ' ' . ($vgWortTranslator['middleName'] != '' ? $vgWortTranslator['middleName'] . ' ' : '') . $vgWortTranslator['lastName'];
				$email->addRecipient($vgWortTranslator['email'], $vgWortTranslatorName);
				$emailParamArray = array(
						'authorName' => $vgWortTranslatorName,
						'privateCode' => $pixelTag->getPrivateCode(),
						'articleTitle' => $article->getLocalizedTitle(),
						'journalName' => $journal->getLocalizedTitle(),
						'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle()
				);
				$email->assignParams($emailParamArray);
				$email->send();
			}
		}
	}

	/**
	 * Get VG Wort pixel tags statistics.
	 * @param $journalId int
	 * @return array (boolean valid, mixed errorMsg or result object)
	 */
	function qualityControl($journalId) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$vgWortUserId = $vgWortPlugin->getSetting($journalId, 'vgWortUserId');
		$vgWortUserPassword = $vgWortPlugin->getSetting($journalId, 'vgWortUserPassword');
		$vgWortTestAPI = $vgWortPlugin->getSetting($journalId, 'vgWortTestAPI');
		$vgWortAPI = MESSAGE_SERVICE_WSDL;
		if ($vgWortTestAPI) {
			$vgWortAPI = MESSAGE_SERVICE_WSDL_TEST;
		}
		try {
			// check if the system requirements are fulfilled
			if (!$vgWortPlugin->requirementsFulfilled()) {
        		return array(false, __('plugins.generic.vgWort.requirementsRequired'));
			}
			// catch and throw an exception if the VG Wort server is down
			if(!@file_get_contents($vgWortAPI)) {
				throw new SoapFault('noWSDL', __('plugins.generic.vgWort.noWSDL', array('wsdl' => $vgWortAPI)));
    		}
    		// catch and throw an exception if the authentication or the authorization error occurs
    		if(!@fopen(str_replace('://', '://'.$vgWortUserId.':'.$vgWortUserPassword.'@', $vgWortAPI), 'r')) {
				$httpString = explode(" ", $http_response_header[0]);
				throw new SoapFault('httpError', __('plugins.generic.vgWort.'.$httpString[1]));
    		}
    		$client = new SoapClient($vgWortAPI, array('login' => $vgWortUserId, 'password' => $vgWortUserPassword));
			$result = $client->qualityControl();
			return array(true, $result);
		}
		catch (SoapFault $soapFault) {
			if($soapFault->faultcode == 'noWSDL' || $soapFault->faultcode == 'httpError') {
				return array(false, $soapFault->faultstring);
			}
			$detail = $soapFault->detail;
			$function = $detail->qualityControlFault;
			return array(false, __('plugins.generic.vgWort.statistics.errorCode'.$function->errorcode));
		}
	}

	/**
	 * Assign a pixel tag to an article.
	 * @param $journal Journal
	 * @param $articleId int
	 * @param $vgWortTextType int
	 * @return boolean
	 */
	function assignPixelTag(&$journal, $articleId, $vgWortTextType) {
		$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
		$pixelTag =& $pixelTagDao->getPixelTagByArticleId($journal->getId(), $articleId);
		if (!isset($pixelTag)) { // no pixel assigned to the text yet --> assign
			$availablePixelTag =& $pixelTagDao->getAvailablePixelTag($journal->getId());
			if($availablePixelTag) {
				// there is an available pixel tag --> assign
				$availablePixelTag->setArticleId($articleId);
				$availablePixelTag->setDateAssigned(Core::getCurrentDate());
				$availablePixelTag->setStatus(PT_STATUS_UNREGISTERED);
				$availablePixelTag->setTextType($vgWortTextType);
				$pixelTagDao->updateObject($availablePixelTag);
			} else {
				// there is no available pixel tag
				$this->notifyEditors($journal, 0);
				return false;
			}
			// check if the minimum of available pixel tags is reached and send a remider if necessary
			$this->pixelTagMinReached($journal);
		}
		return true;
	}

	/**
	 * Send a reminder email to the responsible editors if the minimul of available pixel tags is reached.
	 * @param $journal Journal
	 */
	function pixelTagMinReached(&$journal) {
		$pixelTagDao =& DAORegistry::getDAO('PixelTagDAO');
		$availablePixelTagsCount = $pixelTagDao->getPixelTagsStatusCount($journal->getId(), PT_STATUS_AVAILABLE);
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		$vgWortPixelTagMin = $vgWortPlugin->getSetting($journal->getId(), 'vgWortPixelTagMin');
		if ($availablePixelTagsCount <= $vgWortPixelTagMin) {
			// minimum of available pixel tags reached --> send a reminder email to the selected editors in the plugin settings
			$this->notifyEditors($journal, $availablePixelTagsCount);
		}
	}

	/**
	 * Send a order reminder email to the selected editors.
	 * @param $journal Journal
	 * @param $availablePixelTagsCount int
	 */
	function notifyEditors(&$journal, $availablePixelTagsCount) {
		$vgWortPlugin =& PluginRegistry::getPlugin('generic', VGWORT_PLUGIN_NAME);
		import('classes.mail.MailTemplate');
		$email = new MailTemplate('VGWORT_ORDER_REMINDER');
		$email->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		$editors = $vgWortPlugin->getSetting($journal->getId(), 'vgWortEditors');
		$userDao =& DAORegistry::getDAO('UserDAO');
		foreach ($editors as $editorId) {
			$user =& $userDao->getById($editorId);
			$email->addRecipient($user->getEmail(), $user->getFullName());
			unset($user);
		}
		$emailParamArray = array(
			'journalName' => $journal->getLocalizedTitle(),
			'availablePixelTagCount' => $availablePixelTagsCount,
			'editorialContactSignature' => $journal->getSetting('contactName') . "\n" . $journal->getLocalizedTitle()
		);
		$email->assignParams($emailParamArray);
		$email->send();
	}

}

?>