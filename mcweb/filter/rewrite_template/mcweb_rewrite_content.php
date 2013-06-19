<?php

/**
 * 開始xmlタグのencoding=の中身を、'{$CHARSET}'に書き換えます
 * METAタグcontentの中身を、'{$CONTENTTYPE}; charset={$CHARSET}'に書き換えます
 */
class mcweb_rewrite_content implements MCWEB_filter_rewrite_template
{
	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		foreach($dom->noise as &$value)
		{
			if (FALSE !== strpos($value, '<?xml'))
			{
				$n = strpos($value, 'encoding="');
				if (FALSE !== $n)
				{
					$n += strlen('encoding="');
					$start = $n;
					$str = "";
					while('"' != $value[$n])
					{
						$str .= $value[$n];
						$n++;
					}
					$value = substr($value, 0, $start) . $LD . '$CHARSET' . $RD . substr($value, $n);
					break;
				}
			}
		}

		$meta = $dom->find('META');
		if (!empty($meta))
		{
			foreach($meta as $tag)
			{
				if (HDOM_TYPE_ELEMENT == $tag->nodetype && 0 == strcasecmp('Content-Type', trim($tag->{'http-equiv'})))
				{
					$tag->content = $LD . '$CONTENTTYPE' . $RD . '; charset=' . $LD . '$CHARSET' . $RD;
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		if (!defined('CLIENT_ENCODING'))	throw new MCWEB_LogicErrorException('CLIENT_ENCODINGが定義されていません');
		if (!defined('CLIENT_CARRIER'))		throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');

		if ('SJIS-win' === CLIENT_ENCODING)
		{
			//	IEで文字化けするので、'SJIS-win'ではなく'Shift_jis'にしてやる
			//	そもそもSJIS-winという指定は正確ではない
			$output->text('CHARSET', 'Shift_jis');
		}
		else
		{
			$output->text('CHARSET', CLIENT_ENCODING);
		}

		if ('MOVA' === CLIENT_CARRIER)	$output->text('CONTENTTYPE', 'text/html');
		else							$output->text('CONTENTTYPE', 'application/xhtml+xml');
	}
}

?>