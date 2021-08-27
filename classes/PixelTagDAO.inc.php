<?php

/**
 * @file plugins/generic/vgWort/classes/PixelTagDAO.inc.php
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * @class PixelTagDAO
 * @ingroup plugins_generic_vgWort
 * @see PixelTag
 *
 * @brief Operations for retrieving and modifying PixelTag objects.
 */

import('lib.pkp.classes.db.DAO');

/* These constants are used for user-selectable search fields. */
define('PT_FIELD_PRIVCODE', 	'private_code');
define('PT_FIELD_PUBCODE', 		'public_code');

class PixelTagDAO extends DAO {

	/** @var $parentPluginName string Name of the parent plugin VGWortPlugin */
	var $parentPluginName;

	/**
	 * Constructor.
	 * @param $parentPluginName string
	 */
	function __construct($parentPluginName) {
		parent::__construct();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Instantiate a new data object.
	 * @return PixelTag
	 */
	function newDataObject() {
		$vgWortPlugin = PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$vgWortPlugin->import('classes.PixelTag');
		return new PixelTag();
	}

	/**
	 * Retrieve a pixel tag by ID.
	 * @param $pixelTagId int
	 * @param $contextId int optional
	 * @return PixelTag
	 */
	function getById($pixelTagId, $contextId = null) {
		$params = array((int) $pixelTagId);
		if ($contextId) $params[] = (int) $contextId;
		$result = $this->retrieve(
			'SELECT * FROM pixel_tags WHERE pixel_tag_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Internal function to return a PixelTag object from a row.
	 * @param $row array
	 * @return PixelTag
	 */
	function _fromRow($row) {
		$pixelTag = $this->newDataObject();
		$pixelTag->setId($row['pixel_tag_id']);
		$pixelTag->setContextId($row['context_id']);
		$pixelTag->setSubmissionId($row['submission_id']);
		$pixelTag->setPrivateCode($row['private_code']);
		$pixelTag->setPublicCode($row['public_code']);
		$pixelTag->setDomain($row['domain']);
		$pixelTag->setDateOrdered($row['date_ordered']);
		$pixelTag->setDateAssigned($row['date_assigned']);
		$pixelTag->setDateRegistered($row['date_registered']);
		$pixelTag->setDateRemoved($row['date_removed']);
		$pixelTag->setStatus($row['status']);
		$pixelTag->setTextType($row['text_type']);
		$pixelTag->setMessage($row['message']);

		HookRegistry::call('PixelTagDAO::_fromRow', array(&$pixelTag, &$row));

		return $pixelTag;
	}

	/**
	 * Insert a new PixelTag.
	 * @param $pixelTag PixelTag
	 * @return int new pixel tag ID
	 */
	function insertObject($pixelTag) {
		$returner = $this->update(
			sprintf('
				INSERT INTO pixel_tags
					(context_id,
					submission_id,
					private_code,
					public_code,
					domain,
					date_ordered,
					date_assigned,
					date_registered,
					date_removed,
					status,
					text_type,
					message)
				VALUES
					(?, ?, ?, ?, ?, %s, %s, %s, %s, ?, ?, ?)',
				$this->datetimeToDB($pixelTag->getDateOrdered()),
				$this->datetimeToDB($pixelTag->getDateAssigned()),
				$this->datetimeToDB($pixelTag->getDateRegistered()),
				$this->datetimeToDB($pixelTag->getDateRemoved())
				),
			array(
				$pixelTag->getContextId(),
				$pixelTag->getSubmissionId(),
				$pixelTag->getPrivateCode(),
				$pixelTag->getPublicCode(),
				$pixelTag->getDomain(),
				$pixelTag->getStatus(),
				$pixelTag->getTextType(),
				$pixelTag->getMessage()
			)
		);
		$pixelTag->setId($this->getInsertPixelTagId());
		return $pixelTag->getId();
	}

	/**
	 * Update an existing pixel tag.
	 * @param $pixelTag PixelTag
	 * @return boolean
	 */
	function updateObject($pixelTag) {
		return $this->update(
			sprintf('UPDATE pixel_tags
				SET
					context_id = ?,
					submission_id = ?,
					private_code = ?,
					public_code = ?,
					domain = ?,
					date_ordered = %s,
					date_assigned = %s,
					date_registered = %s,
					date_removed = %s,
					status = ?,
					text_type = ?,
					message = ?
					WHERE pixel_tag_id = ?',
				$this->datetimeToDB($pixelTag->getDateOrdered()),
				$this->datetimeToDB($pixelTag->getDateAssigned()),
				$this->datetimeToDB($pixelTag->getDateRegistered()),
				$this->datetimeToDB($pixelTag->getDateRemoved())
				),
			array(
				$pixelTag->getContextId(),
				$pixelTag->getSubmissionId(),
				$pixelTag->getPrivateCode(),
				$pixelTag->getPublicCode(),
				$pixelTag->getDomain(),
				$pixelTag->getStatus(),
				$pixelTag->getTextType(),
				$pixelTag->getMessage(),
				$pixelTag->getId()
			)
		);
	}


	/**
	 * Delete a pixel tag.
	 * @param $pixelTag PixelTag
	 */
	function deleteObject($pixelTag) {
		$this->deletePixelTagById($pixelTag->getId());
	}

	/**
	 * Delete a pixel tag by ID.
	 * @param $pixelTagId int
	 */
	function deletePixelTagById($pixelTagId) {
		$this->update('DELETE FROM pixel_tags WHERE pixel_tag_id = ?', (int) $pixelTagId);
	}

	/**
	 * Delete pixel tags by context ID.
	 * @param $contextId int
	 */
	function deletePixelTagsByContextId($contextId) {
		$pixelTags = $this->getPixelTagsByContextId($contextId);
		while ($pixelTag = $pixelTags->next()) {
			$this->deletePixelTagById($pixelTag->getId());
		}
	}

	/**
	 * Retrieve all pixel tags matching a particular context ID and the specified search parameters.
	 * @param $contextId int
	 * @param $searchType int optional, which field to search
	 * @param $search string optional, string to match
	 * @param $status int optional, status to match
	 * @param $rangeInfo object optional, DBRangeInfo object describing range of results to return
	 * @param $sortBy string optional, column name the results should be ordered by
	 * @param $sortDirection int optional, ascending (SORT_DIRECTION_ASC) or descending (SORT_DIRECTION_DESC)
	 * @return DAOResultFactory containing matching PixelTags
	 */
	function getPixelTagsByContextId($contextId, $searchType = null, $search = null, $status = null, $rangeInfo = null, $sortBy = null, $sortDirection = SORT_DIRECTION_ASC) {
		$sql = 'SELECT DISTINCT * FROM pixel_tags ';
		$paramArray = array();

		switch ($searchType) {
			case PT_FIELD_PRIVCODE:
				$sql .= ' WHERE LOWER(private_code) LIKE LOWER(?)';
				$paramArray[] = "%$search%";
				break;
			case PT_FIELD_PUBCODE:
				$sql .= ' WHERE LOWER(public_code) LIKE LOWER(?)';
				$paramArray[] = "%$search%";
				break;
			default:
				$searchType = null;
		}

		if (empty($searchType)) {
			$sql .= ' WHERE';
		} else {
			$sql .= ' AND';
		}

		if ($status != '' AND $status != null) {
			if ($status == PT_STATUS_FAILED) {
				$sql .= ' message IS NOT NULL AND message <> \'\' AND';
			} else {
				$sql .= ' status = ? AND';
				$paramArray[] = (int) $status;
			}
		}

		$sql .= ' context_id = ?' . ($sortBy ? (' ORDER BY ' . $sortBy . ' ' . $this->getDirectionMapping($sortDirection)) : '');
		$paramArray[] = (int) $contextId;

		$result = $this->retrieveRange($sql, $paramArray, $rangeInfo);
		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}


	/**
	 * Retrieve a pixel tag by submisison ID.
	 * @param $submissionId int
	 * @param $contextId int optional
	 * @return PixelTag
	 */
	function getPixelTagBySubmissionId($submissionId, $contextId = null) {
		$params = array((int) $submissionId);
		if ($contextId) $params[] = (int) $contextId;
		$result = $this->retrieve(
			'SELECT * FROM pixel_tags WHERE submission_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
			$params
		);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all pixel tags for registration, i.e.unregistered and active pixel tags assigned to published submissions.
	 * @param $contextId int
	 * @param $publicationDate string (optional)
	 * @return DAOResultFactory containing matching PixelTags
	 */
	function getAllForRegistration($contextId, $publicationDate = null) {
		//echo ("STATUS_DECLINED");
		import('classes.publication.Publication'); // STATUS_DECLINED
		$params = array((int) $contextId, PT_STATUS_UNREGISTERED_ACTIVE, STATUS_DECLINED);
        $result = $this->retrieve(
			'SELECT pt.*
			FROM pixel_tags pt
			LEFT JOIN publications ps ON ps.submission_id = pt.submission_id
			LEFT JOIN submissions s ON s.submission_id = ps.submission_id
			LEFT JOIN publication_settings ps_set ON ps_set.publication_id = ps.publication_id
            LEFT JOIN issues i ON i.issue_id = ps_set.setting_value
			WHERE ps_set.setting_name = "issueId" AND pt.context_id = ? AND pt.status = ? AND
				i.published = 1	AND s.status <> ?' .
				($publicationDate ? sprintf(' AND ps.date_published < %s AND i.date_published < %s', $this->datetimeToDB($publicationDate), $this->datetimeToDB($publicationDate)) : ''),
			$params
		);
		$returner = new DAOResultFactory($result, $this, '_fromRow');
		return $returner;
	}

	/**
	 * Retrieve the next available pixel tag for a context.
	 * @param $contextId int
	 * @return PixelTag
	 */
	function getAvailablePixelTag($contextId) {
		$result = $this->retrieveLimit(
			'SELECT * FROM pixel_tags
			WHERE context_id = ? AND submission_id IS NULL AND date_assigned IS NULL AND status = ?
			ORDER BY date_ordered',
			array((int) $contextId,	PT_STATUS_AVAILABLE),
			1
		);
		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Checks if a failed registration of an unregistered and active pixel tag exists
	 * @param $contextId int
	 * @return boolean
	 */
	function failedUnregisteredActiveExists($contextId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM pixel_tags WHERE message <> \'\' AND message IS NOT NULL AND context_id = ?',
			array((int) $contextId)
		);
		return $result->fields[0] ? true : false;
	}

	/**
	 * Get the ID of the last inserted pixel tag.
	 * @return int
	 */
	function getInsertPixelTagId() {
		return $this->_getInsertId('pixel_tags', 'pixel_tag_id');
	}
}

?>
