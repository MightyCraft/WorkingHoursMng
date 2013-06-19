<?php

/**
 * HTTPリクエストヘッダのHOSTが不正なアクセスを拒否します。
 * DNS Rebindingなどによるアクセスだと考えられます。
 */
class mcweb_deny_illegal_httphost implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		$parse = parse_url(URL_DOMAIN_ROOT);
		$defined_host = $parse['host'];
		if (isset($parse['port'])) $defined_host .= ':' . $parse['port'];
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))	$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
		else											$host = $_SERVER['HTTP_HOST'];
		if (empty($host) || $defined_host != $host)
		{
			throw new MCWEB_BadRequestException();
		}
	}
}

?>