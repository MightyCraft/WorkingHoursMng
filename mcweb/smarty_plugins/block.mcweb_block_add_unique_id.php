<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
require_once DIR_MCWEB . '/filter/rewrite_template/mcweb_add_unique_id.php';

function smarty_block_mcweb_block_add_unique_id($params, $content, &$smarty, &$repeat)
{
	if (is_null($content))
	{
		//	ブロック開始タグで行うことはありません
		return;
	}

	$hash = md5($params['index'] . microtime(true));
	return mcweb_add_unique_id::rewrite_link($content, $params['key'] . '=' . $hash);
}


?>
