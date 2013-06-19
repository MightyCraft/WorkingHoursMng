<?php

/**
 * CLIENT_CARRIERから、CLIENT_ENCODINGを決定し定義する。
 */
class mcweb_define_client_encoding implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		if (!defined('CLIENT_CARRIER'))	throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');

		if ('AU' === CLIENT_CARRIER)
		{
			define('CLIENT_ENCODING', 'SJIS-win');
		}
		else if ('MOVA' === CLIENT_CARRIER)
		{
			// Mova
			define('CLIENT_ENCODING', 'SJIS-win');
		}
		else
		{
			define('CLIENT_ENCODING', 'UTF-8');
		}
	}
}
?>