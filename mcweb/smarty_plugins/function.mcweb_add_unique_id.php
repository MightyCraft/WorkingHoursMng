<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.mcweb_add_unique_id.php
 * Type:     function
 * Name:     mcweb_add_unique_id
 * Purpose:  パスにユニークIDを返す
 * -------------------------------------------------------------
 */
require_once DIR_MCWEB . '/filter/rewrite_template/mcweb_add_unique_id.php';
function smarty_function_mcweb_add_unique_id($params, &$smarty)
{
	$hash = md5($params['index'] . microtime(true));
	return $hash;
}
?>