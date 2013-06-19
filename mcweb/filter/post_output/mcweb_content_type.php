<?php

/**
 * Content-typeヘッダを出力します。
 */
class mcweb_content_type implements MCWEB_filter_post_output
{
	function run($template_path, &$str)
	{
		if (!defined('CLIENT_CARRIER'))		throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');
		if (!defined('CLIENT_ENCODING'))	throw new MCWEB_LogicErrorException('CLIENT_ENCODINGが定義されていません');

		if ('SJIS-win' === CLIENT_ENCODING)
		{
			//	IEで文字化けするので、'SJIS-win'ではなく'Shift_jis'にしてやる
			$CHARSET = 'Shift_jis';
		}
		else
		{
			$CHARSET = CLIENT_ENCODING;
		}

		//	PCからのアクセスの時は、HTTPヘッダでContent-Typeを出力しない
		//	PCブラウザは厳密解釈をするので、タグの間違いからエラーになることが多いため
		//	また、Movaからのアクセス時にはHTMLと出力する
		if ('MOVA' === CLIENT_CARRIER)
		{
			header("Content-type: text/html; charset=$CHARSET");
			$str = preg_replace('/<\?xml[^>]*>/', '', $str);
			$str = preg_replace('/<!DOCTYPE[^>]*>/', '', $str);
			$str = preg_replace('/^[\r\n]+<html/i', '<html', $str);
		}
		else if (
				'DOCOMO' === CLIENT_CARRIER
			)
		{
			header("Content-type: application/xhtml+xml; charset=$CHARSET");
		}
	}
}

?>