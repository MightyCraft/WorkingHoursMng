<?php
/**
 * アカウント削除
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_delete_confirm extends UserScene
{
	var $_id;

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
		);

		// エラーメッセージ
		$error_msg = array();

		if (!empty($errors))
		{
			if(isset($errors['id']))
			{
				if($errors['id'][0] == 'format')
				{
					$error_msg['id'] = '社員ID値が不正です。';
				}
				else
				{
					$error_msg['id'] = '社員ID値が不正です。';
				}
			}
		}

		if (!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/user/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_member		= new Member;

		$member_by_id	= $obj_member->getMemberById($this->_id);
		$array_auth_lv	= returnArrayAuthLv();
		
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		
		$obj_position = new Position();
		$array_position	= $obj_position->getDataAll();
		
		$password_tmp	= changePassWord($member_by_id['password']);

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('member_by_id',	$member_by_id);
		$access->text('password_tmp',	substr($password_tmp, 0, USER_MEMBER_PASSWORD_MAX));
		$access->text('array_auth_lv',	$array_auth_lv);
		$access->text('array_post',		$array_post);
		$access->text('array_position',		$array_position);
		

		//所属リスト表示処理
		$this->setProjectTeamViewListByProjectTeamTable($this->_id);
	}
}
?>