<?php
/**
 *	役職マスタ管理　新規/修正画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _position_edit_index extends PostAndGetScene
{
	// パラメータ
	var	$_id=null;			// 役職ID
	var	$_type=null;		// 役職タイプ
	var	$_name=null;		// 役職名
	var	$_delete_flg=null;	// 削除フラグ

	var	$_error;			// エラーメッセージ
	var $_back_flg=false;	// 戻りフラグ

	var $new_flg=false;		// 新規登録フラグ

	function check()
	{
		// 権限が無い場合は工数入力画面に強制遷移
		if (!checkAuthPostManagement($_SESSION['manhour']['member']['auth_lv'],$_SESSION['manhour']['member']['post']))
		{
			MCWEB_Util::redirectAction("/input/index/");
		}
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
			$obj_position			= new Position();
			$position_data	= $obj_position->getDataById($this->_id,true);	// 削除済み含む
			if (!empty($position_data))
			{
				$this->_name		= $position_data['name'];
				$this->_delete_flg	= $position_data['delete_flg'];
			}
			else
			{
				$this->_error[] = '指定されたIDの役職データがありません。';
			}
		}
	}
}

?>