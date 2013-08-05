<?php
/**
 * 所属マスタ管理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
class _post_index extends PostAndGetScene
{
	// パラメータ
	var $_edit_type='';	// 登録or修正
	var $_id;			// 登録修正処理ID
	var $_error_flg;	// 登録修正結果

	var $post_list;		// 部署マスタリスト
	var $post_type;		// 部署タイプ

	function check()
	{

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_post		= new Post();
		// 部署マスタ情報取得取得
		$this->post_list = $obj_post->getDataAll(true);	// 削除済み含む
		$this->post_type = returnArrayPostType();
	}
}
?>