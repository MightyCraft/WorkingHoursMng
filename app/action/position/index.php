<?php
/**
 * 役職マスタ管理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _position_index extends PostAndGetScene
{
	// パラメータ
	var $_edit_type='';	// 登録or修正
	var $_id;			// 登録修正処理ID
	var $_error_flg;	// 登録修正結果

	var $position_list;		// 部署マスタリスト
	var $position_type;		// 部署タイプ

	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_position		= new Position();
		// 部署マスタ情報取得取得
		$this->position_list = $obj_position->getDataAll(true);	// 削除済み含む
	}
}
?>