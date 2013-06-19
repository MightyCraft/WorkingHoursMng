<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
require_once DIR_MCWEB . '/filter/rewrite_template/mcweb_rewrite_link.php';

function smarty_block_mcweb_rewrite_link($params, $content, &$smarty, &$repeat)
{
	if (is_null($content))
	{
		//	ブロック開始タグで行うことはありません
		return;
	}

	if ('action' == $params['type'])
	{
		//	FORMでは、ページ内リンクをはじかない
		if (
				FALSE !== strpos($content, ':')				//	絶対パス、及び'mailto:'などをはじく
		)
		{
			//	相対リンクではなかったので、INPUTタグを出力するのをやめる
			$smarty->assign('TRANS_HIDDEN_POST_PARAM', '');
			$smarty->assign('TRANS_HIDDEN_GET_PARAM', '');
			return $content;
		}
		$smarty->assign('TRANS_HIDDEN_POST_PARAM',  $smarty->get_template_vars('TRANS_HIDDEN_POST_PARAM_COPY'));
		$smarty->assign('TRANS_HIDDEN_GET_PARAM',  $smarty->get_template_vars('TRANS_HIDDEN_GET_PARAM_COPY'));
	}
	else if ('href' == $params['type'])
	{
		if (
				FALSE !== strpos($content, ":")				//	絶対パス、及び'mailto:'などをはじく
			||	(!empty($content) && '#' === $content[0])	//	ページ内リンクをはじく
		)
		{
			//	相対リンクではなかったので、特に加工はしない
			return $content;
		}
	}
	else if ('src' == $params['type'])
	{
		if (
			FALSE !== strpos($content, ":")	//	絶対パス、及び'mailto:'などをはじく
		)
		{
			//	相対リンクではなかったので、特に加工はしない
			return $content;
		}
	}

	//	相対パス調整用のパスを、先頭に追加する
	//	画像へのアクセスが、エントリーポイントPHPファイルの名前階層分だけずれるのを補正
	if (isset($params['adjust_path']))
	{
		$content = $params['adjust_path'] . $content;
	}

	//	追加パラメータ
	$arr = array();
	if (!empty($params['params']))
	{
		parse_str(str_replace('&amp;', '&', $params['params']), $arr);
	}

	//	リンク生成
	$content = mcweb_rewrite_link::rewrite_link($content,  $params['template_path'], $arr);
	return $content;
}


?>
