<?php
/**
 *	部署マスタ管理　新規/修正画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
class _post_edit_index extends PostAndGetScene
{
	// パラメータ
	var	$_id=null;			// 部署ID
	var	$_type=null;		// 部署タイプ
	var	$_name=null;		// 部署名
	var	$_delete_flg=null;	// 削除フラグ

	var	$_error;			// エラーメッセージ
	var $_back_flg=false;	// 戻りフラグ

	var $post_type;			// 部署タイプ
	var $new_flg=false;		// 新規登録フラグ

	function check()
	{

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->new_flg = true;
		}

		if (!$this->new_flg && !$this->_back_flg)
		{
			// 修正の時はデータ取得（確認画面からの戻りの時は取得しない）
			$obj_post			= new Post();
			$post_data	= $obj_post->getDataById($this->_id,true);	// 削除済み含む
			if (!empty($post_data))
			{
				$this->_type		= $post_data['type'];
				$this->_name		= $post_data['name'];
				$this->_delete_flg	= $post_data['delete_flg'];
			}
			else
			{
				$this->_error[] = '指定されたIDの部署データがありません。';
			}
		}

		$this->post_type = returnArrayPostType();
	}
}

?>