<?php

/**
 * DoCoMo端末なのに、uid=NULLGWDOCOMOが存在しなかった場合にリダイレクトさせます
 */
class mcweb_docomo_uid_redirect implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		if (!defined('CLIENT_CARRIER'))	throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');

		if ('DOCOMO' ===  CLIENT_CARRIER || 'MOVA' ===  CLIENT_CARRIER)
		{
			if ('GET' == $_SERVER['REQUEST_METHOD'] && empty($_GET['uid']))
			{
				$url = URL_FRAMEWORK_PHP . $entry_path . '?uid=NULLGWDOCOMO';
				foreach($_GET as $key => $value)
				{
					$url .= '&' . $key . '=' . urlencode($value);
				}
				header('Location: ' . $url);
				exit;
			}
		}
	}
}

?>