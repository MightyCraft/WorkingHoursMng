<?php
/**
 *	社員タイプ管理　新規/修正　登録処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
class _member_type_edit_do extends PostScene
{
	// パラメータ
	var	$_id;				// 社員タイプID
	var	$_name;				// 社員タイプ名
	var	$_delete_flg;		// 削除フラグ

	var $edit_type;			// 新規or修正

	function check()
	{
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->edit_type = 'new';
		}
		else
		{
			$this->edit_type = 'edit';
		}

		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',				ValidatorInt::createInstance()->nullable()->min(1)
			, 'name',			ValidatorString::createInstance()->min(1)->max(USER_MEMBER_TYPE_NAME_MAX)
			, 'delete_flg',		ValidatorInt::createInstance()->nullable()->min(1)->max(1)
		);

		// エラー
		if (!empty($errors))
		{
			// この時点のエラーは不正なアクセス
			MCWEB_Util::redirectAction("/member_type/index?edit_type={$this->edit_type}&id={$this->_id}&error_flg=1");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 削除フラグ
		if ($this->_delete_flg)
		{
			$delete_flg = 1;
		}
		else
		{
			$delete_flg = 0;
		}

		$obj_member_type = new MemberType();
		if ($this->edit_type == 'new')
		{
			// 新規登録の時
			$regist_data = array(
				'name'			=> $this->_name,
				'delete_flg'	=> $delete_flg,
			);
			list($return,$insert_id) = $obj_member_type->insertMemberType($regist_data);
		}
		else
		{
			// 修正の時は修正可能かチェック
			$member_type_data	= $obj_member_type->getDataById($this->_id,true);	// 削除済み含む
			if (empty($member_type_data))
			{
				// 修正データが存在しない時は不正なアクセス
				MCWEB_Util::redirectAction("/member_type/index?edit_type={$this->edit_type}&id={$this->_id}&error_flg=1");
							}
			elseif ($this->_delete_flg)
			{
				// 削除状態にする場合に社員タイプを使用している社員が存在するかチェック
				$obj_member = new Member();
				$member_data = $obj_member->getMemberByMemberType($this->_id);	// 削除済み社員含まない
				if (!empty($member_data))
				{
					// 使用している社員が存在した時は不正なアクセス
					MCWEB_Util::redirectAction("/member_type/index?edit_type={$this->edit_type}&id={$this->_id}&error_flg=1");
				}
			}

			$update_data = array(
				'name'			=> $this->_name,
				'delete_flg'	=> $delete_flg,
			);
			$return = $obj_member_type->updateMemberType($this->_id,$update_data);
		}

		if ($return > 0)
		{
			// 影響を与えた行数がある
			if ($this->edit_type == 'new')
			{
				MCWEB_Util::redirectAction("/member_type/index?edit_type={$this->edit_type}&id={$insert_id}");
			}
			else
			{
				MCWEB_Util::redirectAction("/member_type/index?edit_type={$this->edit_type}&id={$this->_id}");
			}
		}
		else
		{
			MCWEB_Util::redirectAction("/member_type/index?edit_typet={$this->edit_type}&id={$this->_id}&error_flg=1");
		}
	}
}

?>