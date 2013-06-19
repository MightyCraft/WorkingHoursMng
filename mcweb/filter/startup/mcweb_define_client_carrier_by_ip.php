<?php

/**
 * IPフィルタから接続キャリアを判断し、'CLIENT_CARRIER'定数として定義します。
 *
 * 携帯キャリアからの接続だと判断した場合、UserAgentの偽装チェックを行います。
 * 偽装されていた場合MCWEB_BadRequestExceptionをthrowします。
 *
 * IPフィルタで判断した結果が'DEBUG'となった場合は特殊な動作をします。
 * UserAgentが携帯キャリアのものであれば、'CLIENT_CARRIER'定数にはUserAgentに基づいて決定されたキャリアがセットされます。
 * そうでない場合は、'CLIENT_CARRIER'定数には'DEBUG'がセットされます。
 *
 * これによって、FireFoxシミュレータなどからアクセスした場合に、UserAgentを設定により、どのキャリアとしてアクセスするかを制御することができます。
 */
class mcweb_define_client_carrier_by_ip implements MCWEB_filter_startup
{
	var $filter_text_path;

	/**
	 * コンストラクタ
	 * @apram $filter_text_path		IPフィルタを記述したテキストファイルのパス
	 */
	function __construct($filter_text_path)
	{
		$this->filter_text_path = $filter_text_path;
	}

	function run($entry_path)
	{
		$ip = new MCWEB_IP_Filter;

		if (FALSE === $ip->load($this->filter_text_path)){throw new MCWEB_InternalServerErrorException();}
		if (FALSE === ($carrier_name = $ip->check($_SERVER['REMOTE_ADDR']))){throw new MCWEB_InternalServerErrorException();}

		$mobile = new MCWEB_Mobile(MCWEB_CARRIER_DOCOMO | MCWEB_CARRIER_SOFTBANK | MCWEB_CARRIER_AU);
		$user_agent = MCWEB_Mobile::carrier2string($mobile->carrier());

		if ('DEBUG' === $carrier_name)
		{
			//	デバッグアクセスでかつ携帯キャリア用のUserAgentが指定されている場合は、UserAgentで指定されているキャリアからのアクセスとしてふるまう
			if ('OTHER' !== $user_agent)
			{
				$carrier_name = $user_agent;
			}
		}
		else if ('DOCOMO' === $carrier_name && FALSE === getenv('HTTP_USER_AGENT'))
		{
			//	DoCoMoのIP帯からの、UA無し通信は、公式サーバーからの会員登録用の通信であるため許可する
		}
		else if ('DOCOMO' === $carrier_name || 'SOFTBANK' === $carrier_name || 'AU' === $carrier_name)
		{
			//	携帯キャリアの場合、ユーザーエージェントの偽装はアクセス拒否をする
			if ($carrier_name !== $user_agent)
			{
				//	IP帯でのキャリア分類と、ユーザーエージェントによるキャリア分類が不一致だった
				//	ユーザーエージェントを偽装している可能性があります
				throw new MCWEB_BadRequestException();
			}
		}

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