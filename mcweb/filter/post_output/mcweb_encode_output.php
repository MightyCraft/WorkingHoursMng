<?php

require_once(DIR_MCWEB . '/HTML/Emoji.php');
/**
 * 出力内容を、クライアント側のエンコーディングに合わせます。
 * HTML_Emojiファイブラリを使っています。
 */
class mcweb_encode_output implements MCWEB_filter_post_output
{
	function run($template_path, &$str)
	{
		if (!defined('CLIENT_CARRIER'))		throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');
		if (!defined('SERVER_ENCODING'))	throw new MCWEB_LogicErrorException('SERVER_ENCODINGが定義されていません');
		if (!defined('CLIENT_ENCODING'))	throw new MCWEB_LogicErrorException('CLIENT_ENCODINGが定義されていません');

		if		('DOCOMO' === CLIENT_CARRIER)	$emoji = HTML_Emoji::getInstance('docomo');
		else if	('MOVA' === CLIENT_CARRIER)		$emoji = HTML_Emoji::getInstance('docomo');
		else if	('SOFTBANK' === CLIENT_CARRIER)	$emoji = HTML_Emoji::getInstance('softbank');
		else if	('AU' === CLIENT_CARRIER)		$emoji = HTML_Emoji::getInstance('au');
		else									$emoji = HTML_Emoji::getInstance('docomo');

		$emoji->useHalfwidthKatakana();
		$str = $emoji->convertCarrier($str);

		if (SERVER_ENCODING != CLIENT_ENCODING)
		{
			if (!empty($str) && 3 <= strlen($str) && ord($str{0}) == 0xef && ord($str{1}) == 0xbb && ord($str{2}) == 0xbf)
			{
				$str = substr($str, 3);
			}
			$str = $emoji->convertEncoding($str, CLIENT_ENCODING, SERVER_ENCODING);
		}
	}
}

?>