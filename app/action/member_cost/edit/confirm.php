<?php
/**
 *	社員コスト管理　新規/修正確認画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _member_cost_edit_confirm extends PostScene
{
	// パラメータ
	var	$_id;				// 社員コストID
	var	$_name;				// 社員コスト名
	var	$_cost;				// 社員コストタイプ
	var	$_delete_flg;		// 削除フラグ

	var $new_flg=false;		// 新規登録フラグ

	function check()
	{
		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',				ValidatorInt::createInstance()->nullable()->min(1)
			, 'name',			ValidatorString::createInstance()->min(1)->max(USER_MEMBER_COST_NAME_MAX)
			, 'cost',			ValidatorInt::createInstance()->min(0)->max(2147483647)
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
						$error_msg[] = '社員コスト名は必須です。'.USER_MEMBER_COST_NAME_MAX.'文字以内で入力して下さい。';
						break;
					case 'cost':
						$error_msg[] = 'コストは(0～2147483647)の範囲内で入力して下さい。';
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
			$f = new MCWEB_SceneForward('/member_cost/edit/index');
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
			$obj_member_cost	= new MemberCost();
			$member_cost_data	= $obj_member_cost->getDataById($this->_id,true);	// 削除済み含む
			if (empty($member_cost_data))
			{
				// 修正データが無かった時はエラー
				$this->_error[] = '指定されたIDの社員コストデータがありません。';
			}
			elseif ($this->_delete_flg)
			{
				// 削除状態にする場合に社員コストを使用している社員が存在するかチェック
				$obj_member		= new Member();
				$member_data	= $obj_member->getMemberByMemberCost($this->_id);	// 削除済み社員含まない
				if (!empty($member_data))
				{
					// 使用している社員が存在した時はエラー
					$this->_error[] = 'この社員コストを使用している社員が存在しますので削除できません。';
				}
				// 削除状態にする場合に社員コストを使用しているプロジェクトが存在するかチェック
				$obj_project	= new Project();
				$project_data	= $obj_project->getDataByCost($this->_id);		// 削除済みプロジェクトは含まない
				if (!empty($project_data))
				{
					// 使用しているプロジェクトが存在した時はエラー
					$this->_error[] = 'この社員コストを使用しているプロジェクトが存在しますので削除できません。';
				}
			}
			if (!empty($this->_error))
			{
				$this->_back_flg	= true;
				$f = new MCWEB_SceneForward('/member_cost/edit/index');
				$f->regist('FORWARD', $this);
				return $f;
			}
		}
	}
}

?>