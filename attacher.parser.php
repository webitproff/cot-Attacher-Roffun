<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=parser.last
Order=15
[END_COT_EXT]
==================== */

/**
 * Attacher plugin: parser
 *
 * @package Attacher
 * @author Roffun
 * @copyright Copyright (c) Roffun, 2018 - 2019 | https://github.com/Roffun
 * @license BSD License
 **/

 defined('COT_CODE') or die('Wrong URL.');

require_once cot_incfile('attacher', 'plug');

if (!empty($cfg['plugin']['attacher']['thumb_clickable']) && $cfg['plugin']['attacher']['thumb_clickable']  !== 'default') {
	$text = isset(cot::$cfg['plugin']['attacher']['thumb_clickable']) ? cot::$cfg['plugin']['attacher']['thumb_clickable'] : null;
	$text = isset(cot::$cfg['plugin']['attacher']['thumb_clickable']) ? cot::$cfg['plugin']['attacher']['thumb_clickable'] : null;
	$text = preg_replace('#<a[^>]+>.*?<img(.+?)src="(.+?)' . cot::$cfg['plugin']['attacher']['folder'] . '/_thumbs/(.+?)[+>].*?</a>#i','<img$1src="$2' . cot::$cfg['plugin']['attacher']['folder'] . '/_thumbs/$3>',$text);
	}


$text = preg_replace_callback('`<img(.+?)src="(.+?)' . cot::$cfg['plugin']['attacher']['folder'] . '/_thumbs/([0-9]+)/' . cot::$cfg['plugin']['attacher']['prefix'] . '([0-9]+)-(.+?)x(.+?)-(.+?)\.(.+?)"(.*?)[+>]`i','att_customizable_thumb',$text);

$text = str_replace('[att_thumb?','[att_image?',$text);
$text = preg_replace_callback('`\[att_image\?(.+?)\]`i','att_customizable_thumb_bbcode',$text);
