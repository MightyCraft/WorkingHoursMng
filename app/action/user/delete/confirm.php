<?php
/**
 * アカウント削除
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
class _user_delete_confirm extends UserScene
{
	var $_id;

	// 画面
	var $member_by_id;
	var $array_auth_lv;
	var $array_post;
	var $array_position;
	var $array_member_type;
	var $array_member_cost;
	var $password_tmp;

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
		$this->member_by_id	= $obj_member->getMemberById($this->_id);

		$this->array_auth_lv	= returnArrayAuthLv();

		$obj_post			= new Post();
		$this->array_post	= $obj_post->getDataAll();

		$obj_position			= new Position();
		$this->array_position	= $obj_position->getDataAll();

		$obj_member_type			= new MemberType();
		$this->array_member_type	= $obj_member_type->getDataAll();

		$obj_member_cost			= new MemberCost();
		$this->array_member_cost	= $obj_member_cost->getDataAll();

		$password_tmp		= changePassWord($this->member_by_id['password']);
		$this->password_tmp	= substr($password_tmp, 0, USER_MEMBER_PASSWORD_MAX);


		//所属リスト表示処理
		$this->setProjectTeamViewListByProjectTeamTable($this->_id);
	}
}
?>