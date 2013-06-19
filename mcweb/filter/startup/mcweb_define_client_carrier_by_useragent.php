<?php

/**
 * ユーザーエージェントから接続キャリアを判断し、'CLIENT_CARRIER'定数として定義します。
 */
class mcweb_define_client_carrier_by_useragent implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		$mobile = new MCWEB_Mobile(MCWEB_CARRIER_DOCOMO | MCWEB_CARRIER_SOFTBANK | MCWEB_CARRIER_AU);
		$carrier_name = MCWEB_Mobile::carrier2string($mobile->carrier());

		if ('DOCOMO' === $carrier_name && preg_match('/^DoCoMo\/1\.0.*$/', getenv('HTTP_USER_AGENT')))
		{
			define('CLIENT_CARRIER', 'MOVA');
		}
		else
		{
			define('CLIENT_CARRIER', $carrier_name);
		}
	}
}
?>