<?php
 
/**
 * @file plugins/generic/jatsParser/index.php
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD
 * Distributed under the GNU GPL v3. 
 *
 * @ingroup plugins_generic_jatsParser
 * @brief JATS Parser that transforms JATS XML into Plain Old PHP Objects
 *
 */

require_once('JatsParserPlugin.inc.php');

return new JatsParserPlugin();

?>
