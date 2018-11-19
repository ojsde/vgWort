<?php

/**
 * @file plugins/generic/vgWort/classes/PixelTag.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class PixelTag
 * @ingroup plugins_generic_vgWort
 * @see PixelTagDAO
 *
 * @brief Pixel tag metadata class.
 */

define('PT_STATUS_ANY', '');
define('PT_STATUS_AVAILABLE', 0x01);
define('PT_STATUS_UNREGISTERED_ACTIVE', 0x02);
define('PT_STATUS_REGISTERED_ACTIVE', 0x03);
define('PT_STATUS_UNREGISTERED_REMOVED', 0x04);
define('PT_STATUS_REGISTERED_REMOVED', 0x05);
define('PT_STATUS_FAILED', 0x06); // only used for filtering, not saved in DB column status

define('TYPE_TEXT', 0x01);
define('TYPE_LYRIC', 0x02);

class PixelTag extends DataObject {

	/**
	 * Get the context ID.
	 * @return int
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set the context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get the submission ID.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set the submission ID.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * Get the submission object.
	 * @return Submission
	 */
	function &getSubmission() {
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($this->getSubmissionId());
		return $submission;
	}

	/**
	 * Get private code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated here as string.
	 * @return string
	 */
	function getPrivateCode() {
		return $this->getData('privateCode');
	}

	/**
	 * Set private code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated as string here.
	 * @param $privateCode string
	 */
	function setPrivateCode($privateCode) {
		return $this->setData('privateCode', $privateCode);
	}

	/**
	 * Get public code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated as string here.
	 * @return string
	 */
	function getPublicCode() {
		return $this->getData('publicCode');
	}

	/**
	 * Set public code of the pixel tag -- 20, 30 or 32 long hexadecimal number treated as string here.
	 * @param $publicCode string
	 */
	function setPublicCode($publicCode) {
		return $this->setData('publicCode', $publicCode);
	}

	/**
	 * Get VG Wort domain.
	 * @return string
	 */
	function getDomain() {
		return $this->getData('domain');
	}

	/**
	 * Set VG Wort domain.
	 * @param $domain string
	 */
	function setDomain($domain) {
		return $this->setData('domain', $domain);
	}

	/**
	 * Get date ordered.
	 * @return date
	 */
	function getDateOrdered() {
		return $this->getData('dateOrdered');
	}

	/**
	 * Set date ordered.
	 * @param $dateOrdered date
	 */
	function setDateOrdered($dateOrdered) {
		return $this->setData('dateOrdered', $dateOrdered);
	}

	/**
	 * Get date assigned.
	 * @return date
	 */
	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}

	/**
	 * Set date assigned.
	 * @param $dateAssigned date
	 */
	function setDateAssigned($dateAssigned) {
		return $this->setData('dateAssigned', $dateAssigned);
	}

	/**
	 * Get date registered.
	 * @return date
	 */
	function getDateRegistered() {
		return $this->getData('dateRegistered');
	}

	/**
	 * Set date registered.
	 * @param $dateRegistered date
	 */
	function setDateRegistered($dateRegistered) {
		return $this->setData('dateRegistered', $dateRegistered);
	}

	/**
	 * Get date removed.
	 * @return date
	 */
	function getDateRemoved() {
		return $this->getData('dateRemoved');
	}

	/**
	 * Set date removed.
	 * @param $dateRemoved date
	 */
	function setDateRemoved($dateRemoved) {
		return $this->setData('dateRemoved', $dateRemoved);
	}

	/**
	 * Get status.
	 * @return int // PT_STATUS_...
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set status.
	 * @param $status int // PT_STATUS_...
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get pixel tag status locale string.
	 * @return string
	 */
	function getStatusString() {
		switch ($this->getData('status')) {
			case PT_STATUS_AVAILABLE:
				return __('plugins.generic.vgWort.pixelTag.available');
			case PT_STATUS_UNREGISTERED_ACTIVE:
				if (!$this->isPublished()) {
					return __('plugins.generic.vgWort.pixelTag.unregistered.active.notPublished');
				}
				return __('plugins.generic.vgWort.pixelTag.unregistered.active');
			case PT_STATUS_UNREGISTERED_REMOVED:
				return __('plugins.generic.vgWort.pixelTag.unregistered.removed');
			case PT_STATUS_REGISTERED_ACTIVE:
				return __('plugins.generic.vgWort.pixelTag.registered.active');
			case PT_STATUS_REGISTERED_REMOVED:
				return __('plugins.generic.vgWort.pixelTag.registered.removed');
			case PT_STATUS_FAILED:
				return __('plugins.generic.vgWort.pixelTag.failed');
			default:
				return __('plugins.generic.vgWort.pixelTag.status');
		}
	}

	/**
	 * Get text type.
	 * @return int // TYPE_...
	 */
	function getTextType() {
		return $this->getData('textType');
	}

	/**
	 * Set text type.
	 * @param $textType int // TYPE_...
	 */
	function setTextType($textType) {
		return $this->setData('textType', $textType);
	}

	/**
	 * Get an associative array matching text type codes with locale strings.
	 * @return array text type => locale string
	 */
	function getTextTypeOptions() {
		static $textTypeOptions = array(
			TYPE_TEXT => 'plugins.generic.vgWort.pixelTag.textType.text',
			TYPE_LYRIC => 'plugins.generic.vgWort.pixelTag.textType.lyric'
		);
		return $textTypeOptions;
	}

	/**
	 * Get registration check and failure message.
	 * @return string
	 */
	function getMessage() {
		return $this->getData('message');
	}

	/**
	 * Set registration check and failure message.
	 * @param $message string
	 */
	function setMessage($message) {
		return $this->setData('message', $message);
	}

	/**
	 * Check if the submission the pixel tag is assigned to is published
	 * @return boolean
	 */
	function isPublished() {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getByArticleId($this->getSubmissionId());
		if ($publishedArticle) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById($publishedArticle->getIssueId(), $this->getContextId());
			if ($issue->getPublished()) return true;
		}
		return false;
	}

}

?>
