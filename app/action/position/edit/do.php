<?php
/**
 *	役職マスタ管理　新規/修正　登録処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _position_edit_do extends PostScene
{
	// パラメータ
	var	$_id;				// 役職ID
	var	$_type;				// 役職タイプ
	var	$_name;				// 役職名
	var	$_delete_flg;		// 削除フラグ

	var $type=false;		// 新規or修正

	function check()
	{
		// 権限が無い場合は工数入力画面に強制遷移
		if (!checkAuthPostManagement($_SESSION['manhour']['member']['auth_lv'],$_SESSION['manhour']['member']['post']))
		{
			MCWEB_Util::redirectAction("/input/index/");
		}
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->type = 'new';
		}
		else
		{
			$this->type = 'edit';
		}

		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',				ValidatorInt::createInstance()->nullable()->min(0)
			, 'name',			ValidatorString::createInstance()->width()->min(1)->max(64)
			, 'delete_flg',		ValidatorInt::createInstance()->nullable()->min(1)->max(1)
		);

		// エラー
		if (!empty($errors))
		{
			// この時点のエラーは不正なアクセス
			MCWEB_Util::redirectAction("/position/index/?edit_type={$this->type}&id={$this->_id}&error_flg=1");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

		$obj_position			= new Position();
		if ($this->type == 'new')
		{
			// 新規登録の時
			$regist_data = array(
				'name'			=> $this->_name,
				'delete_flg'	=> $this->_delete_flg,
			);
			list($return,$insert_id) = $obj_position->insertposition($regist_data);
		}
		else
		{
			// 修正の時
			$position_data	= $obj_position->getDataById($this->_id,true);	// 削除済み含む
			if (empty($position_data))
			{
				// 修正データが存在しない時は不正なアクセス
				MCWEB_Util::redirectAction("/position/index/?edit_type={$this->type}&id={$this->_id}&error_flg=1");
			}

			$update_data = array(
				'name'			=> $this->_name,
				'delete_flg'	=> $this->_delete_flg,
			);
			$return = $obj_position->updateposition($this->_id,$update_data);
		}

		if ($return > 0)
		{
			// 影響を与えた行数がある
			if ($this->type == 'new')
			{
				MCWEB_Util::redirectAction("/position/index/?edit_type={$this->type}&id={$insert_id}");
			}
			else
			{
				MCWEB_Util::redirectAction("/position/index/?edit_type={$this->type}&id={$this->_id}");
			}
		}
		else
		{
			MCWEB_Util::redirectAction("/position/index/?edit_typet={$this->type}&id={$this->_id}&error_flg=1");
		}
	}
}

?>