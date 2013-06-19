<?php

/**
 * UIDを、'CLIENT_REAL_UID'定数として定義します。
 * 可変部分を取り除いたUIDを、'CLIENT_UID'定数として定義します。
 */
class mcweb_define_client_uid implements MCWEB_filter_startup
{
	var $docomo_uid_type;

	/**
	 * コンストラクタ
	 * @apram $docomo_uid_type		DoCoMoからのアクセスで、uid/guidどちらのIDを参照するかを指定します。'uid'もしくは'guid'を指定してください。
	 */
	function __construct($docomo_uid_type)
	{
		if		('uid' === $docomo_uid_type)	$this->docomo_uid_type = $docomo_uid_type;
		else if ('guid' === $docomo_uid_type)	$this->docomo_uid_type = $docomo_uid_type;
		else									throw new MCWEB_Exception();
	}

	function run($entry_path)
	{
		if (!defined('CLIENT_CARRIER'))	throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');

		$mobile = new MCWEB_Mobile(MCWEB_CARRIER_DOCOMO | MCWEB_CARRIER_SOFTBANK | MCWEB_CARRIER_AU);
		$uid = $mobile->uid();

		if ('DOCOMO' === CLIENT_CARRIER || 'MOVA' === CLIENT_CARRIER)
		{
			if (FALSE === getenv('HTTP_USER_AGENT'))
			{
				//	DoCoMo公式からのアクセスです
				define('CLIENT_REAL_UID', '');
				define('CLIENT_UID', '');
			}
			else
			{
				//	通常のDoCoMoユーザーからのアクセスです
				if ('uid' === $this->docomo_uid_type)
				{
					if (12 !== strlen($uid))	throw new MCWEB_BadRequestException();
					define('CLIENT_REAL_UID', $uid);
					define('CLIENT_UID', substr($uid, 2));
				}
				else
				{
					if (7 !== strlen($uid))		throw new MCWEB_BadRequestException();
					define('CLIENT_REAL_UID', $uid);
					define('CLIENT_UID', $uid);
				}
			}
		}
		else if (CLIENT_CARRIER === 'SOFTBANK')
		{
			define('CLIENT_REAL_UID', $uid);
			define('CLIENT_UID', substr($uid, 1));
		}
		else
		{
			define('CLIENT_REAL_UID', $uid);
			define('CLIENT_UID', $uid);
		}
	}
}
?>