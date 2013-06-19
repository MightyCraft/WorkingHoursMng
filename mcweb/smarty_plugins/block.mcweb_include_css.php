<?php

function smarty_block_mcweb_include_css($params, $content, &$smarty, &$repeat)
{
	if (is_null($content))
	{
		//	ブロック開始タグで行うことはありません
		return;
	}

	//	MOVAはCSSが利用できません
	if (defined('CLIENT_CARRIER') && 'MOVA' === CLIENT_CARRIER)
	{
		return '';
	}

	$filename = DIR_HTML . $params['path'] . '/' . $content;
	$content = file_get_contents($filename);
	if (!empty($content) && 3 <= strlen($content) && ord($content{0}) == 0xef && ord($content{1}) == 0xbb && ord($content{2}) == 0xbf)
	{
		$content = substr($content, 3);
	}

	//	コメント削除
	$content = preg_replace('/\/\*.*\*\//sU', '', $content);

	//	余分な改行を削除
	$content = "\r\n" . preg_replace('/^\r\n/m', '', $content);

	//	CSSの解析
	$content = preg_replace_callback('/(\w*)\s*\.\s*(\w+)\s*{(.*)}/sU', '__mcweb_parse_css_callback', $content);

	if (defined('CLIENT_IS_SIMULATOR') && CLIENT_IS_SIMULATOR)
	{
		$content = '<style type="text/css">' . $content . "\n" . '</style>';
	}
	else if (defined('CLIENT_CARRIER') && 'AU' === CLIENT_CARRIER)
	{
		$content = '<style type="text/css"><![CDATA[' . str_replace('a:link', 'a', $content) . "\n" . ']]></style>';
	}
	else if (defined('CLIENT_CARRIER') && ('DOCOMO' === CLIENT_CARRIER || 'SOFTBANK' === CLIENT_CARRIER))
	{
		$content = '<style type="text/css"><![CDATA[' . $content . "\n" . ']]></style>';
	}
	else
	{
		$content = '<style type="text/css">' . $content . "\n" . '</style>';
	}

    return  $content . "\r\n";
}

function __mcweb_parse_css_callback($matches)
{
	if (0 === preg_match('/(\w*)\s*\.\s*(\w+)\s*{(.*)}/sU', $matches[0], $m))
	{
		return $matches[0];
	}

	global $smarty;
	$arr = explode(';', $m[3]);
	$sets = array();
	foreach($arr as $value)
	{
		$d = explode(':', $value);
		if (2 == COUNT($d))
		{
			$sets = array_merge($sets, array(trim($d[0]) => trim($d[1])));
		}
	}
	if (!empty($m[1]))
	{
		$smarty->_tpl_vars['INCLUDE_CSS'][strtolower($m[1])][$m[2]] = $sets;
	}
	else
	{
		$smarty->_tpl_vars['INCLUDE_CSS']['ALL'][$m[2]] = $sets;
	}
	return '';
}

?>