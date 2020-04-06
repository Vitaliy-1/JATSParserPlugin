<?php

/**
 * @file classes/JatsParserPublicationDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsParserPublicationDAO
 *
 * @brief Extends original ArticleGalleyDAO to add localized fields.
 */

import ('classes.publication.PublicationDAO');

class JatsParserPublicationDAO extends PublicationDAO {

	/**
	 * @param $galleyId int
	 * @return DataObject|null
	 * @brief return Publication object
	 */
	function getByGalleyId($galleyId) {
		$params[] = $galleyId;

		$sql = 'SELECT publication_id FROM publication_galleys WHERE galley_id = ?';

		$result = $this->retrieve($sql, $params);
		$publicationId = $result->fields[0];

		if (!$publicationId) return null;

		return $this->getById($publicationId);
	}

	/**
	 * @param $publicationId int
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $locale null|string
	 * @return void
	 */
	function changeJatsParserSetting($publicationId, $settingName, $settingValue, $locale = null) {
		if (!$locale) $locale = '';

		$idFields = array(
			'publication_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'publication_id' => (int) $publicationId,
			'locale' => $locale,
			'setting_name' => 'jatsparser::' . $settingName,
			'setting_value' => $settingValue
		);
		$this->replace('publication_settings', $updateArray, $idFields);
	}

	/**
	 * @param $publicationId int
	 * @param $settingName string
	 * @return void
	 */
	function deleteJatsParserSetting($publicationId, $settingName, $locale = null) {

		$sql = 'DELETE FROM publication_settings WHERE setting_name = ? AND publication_id = ?';
		if ($locale) {
			$sql .= ' AND locale = ?';
		}

		$params = array(
			'jatsparser::' . $settingName,
			(int) $publicationId
		);
		if ($locale) {
			$params[] = $locale;
		}

		$this->update(
			$sql,
			$params
		);
		$this->flushCache();
	}
}
