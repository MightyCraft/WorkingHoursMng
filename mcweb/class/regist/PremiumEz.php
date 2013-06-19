<?php

require_once('nusoap.php');	// NuSOAPのライブラリ読み込み

class PremiumEz
{
	/**
	 * まとめてAU支払いの月額課金の有効性をチェックします。
	 * @param $cp_code			サービス提供者コード
	 * @param $wsdl_filename	wsdlファイルのファイル名
	 * @return	bool
	 */
	static function checkDGConfReq($cp_code, $wsdl_filename)
	{
		// tran_idは必須パラメータ(28桁)
		if(isset($_GET['tran_id']) && 28 === strlen($_GET['tran_id']))	$tran_id	= $_GET['tran_id'];
		else															return false;
		
		
		$ws1	= new soapclientAU($wsdl_filename, true);	// WSDLファイルよりSOAPクライアント生成
		
		$proxy1	= $ws1->getProxy();	// クライアントプロキシ生成
		
		$confparam[]	= array(
							'cp_cd'		=> $cp_code,	// サービス提供者コード
							'tran_id'	=> $tran_id,	// トランザクションID
						);
		
		$confresp	= $proxy1->call("trx_DGConfReq", $confparam);	//【DGConfReq】ﾌﾟﾚﾐｱﾑEZ 回収代行システム呼び出し＆ 応答受信
		
		unset($proxy1);
		
		$soap_check	= false;
		if('00' === $confresp['rslt_cd'])
		{
			// 処理状況確認要求OK！
			$soap_check	= true;
		}
		
		
		return $soap_check;
	}
}
?>