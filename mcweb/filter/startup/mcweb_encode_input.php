<?php

require_once(DIR_MCWEB . '/HTML/Emoji.php');
/**
 * ユーザーの入力内容を、サーバー側のエンコーディングに合わせます。
 */
class mcweb_encode_input implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		if (!defined('CLIENT_CARRIER'))		throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');
		if (!defined('SERVER_ENCODING'))	throw new MCWEB_LogicErrorException('SERVER_ENCODINGが定義されていません');
		if (!defined('CLIENT_ENCODING'))	throw new MCWEB_LogicErrorException('CLIENT_ENCODINGが定義されていません');

		foreach($_GET as &$ref_get)
		{
			$ref_get = self::convert($ref_get);
		}
		foreach($_POST as &$ref_post)
		{
			$ref_post = self::convert($ref_post);
		}
		foreach($_COOKIE as &$ref_cookie)
		{
			$ref_cookie = self::convert($ref_cookie);
		}
	}

	static function convert($input)
	{
		if (is_array($input))
		{
			foreach($input as &$value)
			{
				$value = self::convert($value);
			}
			return $input;
		}
		else
		{
			if (!mb_check_encoding($input, CLIENT_ENCODING))
			{
				throw new MCWEB_BadRequestException();
			}
			else
			{
				//	サーバーとクライアントで、文字列のエンコーディングが違う場合に変換
				if (SERVER_ENCODING != CLIENT_ENCODING)
				{
					if		('DOCOMO' === CLIENT_CARRIER)	$emoji = HTML_Emoji::getInstance('docomo');
					else if	('MOVA' === CLIENT_CARRIER)		$emoji = HTML_Emoji::getInstance('docomo');
					else if	('SOFTBANK' === CLIENT_CARRIER)	$emoji = HTML_Emoji::getInstance('softbank');
					else if	('AU' === CLIENT_CARRIER)		$emoji = HTML_Emoji::getInstance('au');
					else									$emoji = HTML_Emoji::getInstance('docomo');
					$input = $emoji->convertEncoding($input, SERVER_ENCODING, CLIENT_ENCODING);
				}
				return $input;
			}
		}
	}
}

?>