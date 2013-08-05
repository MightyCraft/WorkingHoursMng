<?php
/**
 * 社員コスト管理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
class _member_cost_index extends PostAndGetScene
{
	// パラメータ
	var $_edit_type='';	// 登録or修正
	var $_id;			// 登録修正処理ID
	var $_error_flg;	// 登録修正結果

	var $post_list;		// 部署マスタリスト

	function check()
	{

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 社員コストマスタ情報取得取得
		$obj_member_cost		= new MemberCost();
		$this->member_cost_list = $obj_member_cost->getDataAll(true);	// 削除済み含む
	}
}
?>