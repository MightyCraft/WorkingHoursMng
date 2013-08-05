<?php
/**
 *	社員タイプ管理　新規/修正画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
class _member_type_edit_index extends PostAndGetScene
{
	// パラメータ
	var	$_id=null;			// 社員タイプID

	var $_back_flg=false;	// 戻りフラグ
	var	$_name=null;		// 社員タイプ名
	var	$_delete_flg=null;	// 削除フラグ
	var	$_error;			// エラーメッセージ

	var $new_flg=false;		// 新規登録フラグ

	function check()
	{
		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',		ValidatorInt::createInstance()->nullable()->min(1)
		);
		if (!empty($errors))
		{
			$this->_error[] = 'IDの指定が不正です。';
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
			// 「修正」時はデータ取得（※確認画面からの戻ってきた時は取得しない）
			$obj_member_type			= new MemberType();
			$member_type_data	= $obj_member_type->getDataById($this->_id,true);	// 削除済み含む
			if (!empty($member_type_data))
			{
				$this->_name		= $member_type_data['name'];
				$this->_delete_flg	= $member_type_data['delete_flg'];
			}
			else
			{
				$this->_error[] = '指定されたIDの社員タイプデータがありません。';
			}
		}
	}
}

?>