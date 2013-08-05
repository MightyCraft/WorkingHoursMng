<?php
/**
 * 社員タイプ管理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
class _member_type_index extends PostAndGetScene
{
	// パラメータ
	var $_edit_type='';	// 登録or修正
	var $_id;			// 登録修正処理ID
	var $_error_flg;	// 登録修正結果

	var $member_type_list;	// 社員タイプリスト

	function check()
	{

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 社員タイプマスタ情報取得取得
		$obj_member_type		= new MemberType();
		$this->member_type_list = $obj_member_type->getDataAll(true);	// 削除済み含む
	}
}
?>