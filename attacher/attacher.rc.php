<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=rc
[END_COT_EXT]
==================== */

/**
 * Attacher plugin: rc
 * Filename attacher.rc.php
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, Webitproff 2018 - 2025 
 * @license BSD License
 **/

defined('COT_CODE') or die('Wrong URL');

if ($cfg['plugin']['attacher']['css'] == 1) {
    Resources::addFile($cfg['plugins_dir'] . '/attacher/css/attacher.css');
}
