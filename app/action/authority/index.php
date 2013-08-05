<?php
/**
 * 権限マスタ管理
 */
require_once(DIR_APP . "/class/common/dbaccess/Authority.php");

class _authority_index extends PostAndGetScene
{
	// パラメータ
	var $_edit_type='';	// 登録or修正
	var $_id;			// 登録修正処理ID
	var $_error_flg;	// 登録修正結果

	var $authority_list;		// 権限マスタリスト
	var $authority;				// 権限

	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 権限マスタ情報取得取得
		$obj_authority		= new Authority();
		$this->authority_list = $obj_authority->getDataAll(true);	// 削除済み含む
	}
}
?>