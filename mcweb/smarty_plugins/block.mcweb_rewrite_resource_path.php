<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
require_once DIR_MCWEB . '/filter/rewrite_template/mcweb_rewrite_link.php';

function smarty_block_mcweb_rewrite_resource_path($params, $content, &$smarty, &$repeat)
{
	if (is_null($content))
	{
		//	ブロック開始タグで行うことはありません
		return;
	}

	if ('src' == $params['type'])
	{
		if (
			FALSE !== strpos($content, ":")	//	絶対パス、及び'mailto:'などをはじく
		)
		{
			//	相対リンクではなかったので、特に加工はしない
			return $content;
		}
	}


	//	リンク生成
	if (defined('WITHOUT_REWRITE_ENGINE') && WITHOUT_REWRITE_ENGINE)
	{
		$content = MCWEB_Framework::normalize_path(MCWEB_Framework::getInstance()->base_path . 'dummy/../' . $content);
		$content = MCWEB_Framework::getInstance()->to_action_root_path . '../link' . $content;
	}
	else
	{
		$content = mcweb_rewrite_link::rewrite_link($content,  $params['template_path'], $arr);
	}

	return $content;
}


?>
