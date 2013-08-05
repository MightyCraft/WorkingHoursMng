<?php
/**
 *	社員タイプ管理　新規/修正確認画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
class _member_type_edit_confirm extends PostScene
{
	// パラメータ
	var	$_id;				// 社員タイプID
	var	$_name;				// 社員タイプ名
	var	$_delete_flg;		// 削除フラグ

	var $new_flg=false;		// 新規登録フラグ

	function check()
	{
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
			$error_msg = array();
			foreach ($errors as $param => $error)
			{
				switch ($param)
				{
					case 'id':
						$error_msg[] = '管理IDの指定が不正です。';
						break;
					case 'name':
						$error_msg[] = '社員タイプ名は必須です。'.USER_MEMBER_TYPE_NAME_MAX.'文字以内で入力して下さい。';
						break;
					case 'delete_flg':
						$error_msg[] = '削除状態の指定が不正です。';
						break;
					default:
						$error_msg[] = '入力項目にエラーがあります。';
						break;
				}
			}

			$this->_error		= $error_msg;
			$this->_back_flg	= true;
			$f = new MCWEB_SceneForward('/member_type/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->new_flg = true;
		}

		if (!$this->new_flg)
		{
			// 修正の時は修正可能かチェック
			$obj_member_type	= new MemberType();
			$member_type_data	= $obj_member_type->getDataById($this->_id,true);	// 削除済み含む
			if (empty($member_type_data))
			{
				// 修正データが無かった時はエラー
				$this->_error[] = '指定されたIDの社員タイプデータがありません。';
			}
			elseif ($this->_delete_flg)
			{
				// 削除状態にする場合に社員タイプを使用している社員が存在するかチェック
				$obj_member		= new Member();
				$member_data	= $obj_member->getMemberByMemberType($this->_id);	// 削除済み社員含まない
				if (!empty($member_data))
				{
					// 使用している社員が存在した時はエラー
					$this->_error[] = 'この社員タイプを使用している社員が存在しますので削除できません。';
				}
			}
			if (!empty($this->_error))
			{
				$this->_back_flg	= true;
				$f = new MCWEB_SceneForward('/member_type/edit/index');
				$f->regist('FORWARD', $this);
				return $f;
			}
		}
	}
}

?>