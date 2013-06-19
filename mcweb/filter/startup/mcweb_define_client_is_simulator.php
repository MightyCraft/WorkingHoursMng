<?php

/**
 * IPフィルタから接続キャリアを判断し、'DEBUG'からのアクセスかをチェックします。
 * 'DEBUG'からのアクセスの場合、'CLIENT_IS_SIMULATOR'定数をtrueに。そうでない場合はfalseに設定します。
 */
class mcweb_define_client_is_simulator implements MCWEB_filter_startup
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

		if ('DEBUG' === $carrier_name)
		{
			define('CLIENT_IS_SIMULATOR', true);
		}
		else
		{
			define('CLIENT_IS_SIMULATOR', false);
		}
	}
}
?>