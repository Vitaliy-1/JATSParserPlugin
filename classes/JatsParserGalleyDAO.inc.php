<?php

/**
 * @file classes/JatsParserGalleyDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JatsParserGalleyDAO
 *
 * @brief Extends original ArticleGalleyDAO to add localized fields.
 */

import ('classes.article.ArticleGalleyDAO');

class JatsParserGalleyDAO extends ArticleGalleyDAO {

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array_merge(
			parent::getLocaleFieldNames(),
			array('jatsParserDisplayDefaultXml')
		);
	}
}
