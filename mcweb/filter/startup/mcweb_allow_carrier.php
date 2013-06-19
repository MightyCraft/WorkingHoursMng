<?php

/**
 * 'CLIENT_CARRIER'から判断し、指定したキャリア以外からのアクセスをブロックします。
 * 指定したキャリア以外からのアクセスの場合、MCWEB_DenyCarrierExceptionをthrowします。
 */
class mcweb_allow_carrier implements MCWEB_filter_startup
{
	protected $arrow_carriers;

	/**
	 * コンストラクタ
	 * @apram $arrow_carriers		アクセス許可をするキャリア名を格納した配列
	 */
	function __construct($arrow_carriers)
	{
		$this->arrow_carriers = $arrow_carriers;
	}

	function run($entry_path)
	{
		if (!is_array($this->arrow_carriers))	throw new MCWEB_LogicErrorException();
		if (!defined('CLIENT_CARRIER'))			throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');

		if (!in_array(CLIENT_CARRIER, $this->arrow_carriers))
		{
			throw new MCWEB_DenyCarrierException();
		}
	}
}

?>