<?php
/**
 * 「アカウント管理」新規登録
 */
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_new_do extends PostScene
{
	var $_member_code;
	var $_name;
	var $_auth_lv;
	var $_post;
	var $_position;
	var $_password;
	var $_password_change;
	

	function check()
	{
		// TODO: ブラッシュアップ
		$array_auth_lv	= returnArrayAuthLv();
		$max_auth_lv	= count($array_auth_lv) -1;

		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// 氏名
			, 'name', ValidatorString::createInstance()->min(1)->max(USER_MEMBER_NAME_MAX)
			// 権限レベル
			, 'auth_lv', ValidatorInt::createInstance()->min(0)->max($max_auth_lv)
			// 所属
			, 'post', ValidatorInt::createInstance()->min(1)
			// 役職
			, 'position', ValidatorInt::createInstance()->min(1)
			// パスワード
			, 'password', ValidatorAlphanumeric::createInstance()->min(1)->max(USER_MEMBER_PASSWORD_MAX)
		);
		// 社員コード
		if (USER_MEMBER_CODE_FORMAT != '')
		{

			$member_code_error = ValidatorString::createInstance()->preg(USER_MEMBER_CODE_FORMAT)->min(USER_MEMBER_CODE_MIN)->max(USER_MEMBER_CODE_MAX)->validate($this->_member_code);
		}
		else
		{
			$member_code_error = ValidatorString::createInstance()->min(USER_MEMBER_CODE_MIN)->max(USER_MEMBER_CODE_MAX)->validate($this->_member_code);
		}
		if (!empty($member_code_error))
		{
			$errors['member_code'] = $member_code_error;
		}

		if(!empty($errors))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
	}
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$member_code	= $this->_member_code;
		$name			= $this->_name;
		$auth_lv		= $this->_auth_lv;
		$post			= $this->_post;
		$position		= $this->_position;
		$password		= $this->_password;
		$regist_date	= date('Y-m-d H:i:s');
		$update_date	= $regist_date;
		$data = array(
			$member_code,
			$name,
			$auth_lv,
			$post,
			$position,
			hashPassWord($password),
			$regist_date,
			$update_date,
		);

		// 所属マスタの存在チェック
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		if (!isset($array_post[$post]))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
		// 役職存在チェック
		$obj_position = new Position();
		$array_position	= $obj_position->getDataAll();
		if (!isset($array_position[$this->_position]))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}

		//登録されているかの確認
		$obj_member		= new Member;
		$chk_flg = $obj_member->isMember($member_code);


		$res = 0;
		if($chk_flg != 1)
		{
			list($res,$insert_id) = $obj_member->insertMember($data);
			if($res == 1)
			{
				//所属プロジェクト登録
				$obj_project_team	= new ProjectTeam;
				if(is_array($_SESSION['manhour']['tmp']['user_project_team_list']) ) {
					foreach($_SESSION['manhour']['tmp']['user_project_team_list'] as $key => $value) {
						$data	= array($insert_id, $value['project_id'], $regist_date);
						$obj_project_team->writeProjectTeam($data);
					}
				}

				//登録OK
				unset($_SESSION['manhour']['tmp']['user_edit']);				//一時的なセッションを削除
				unset($_SESSION['manhour']['tmp']['user_project_team_list']);	//一時的なセッションを削除
				MCWEB_Util::redirectAction("/user/new/complete?insert_id=$insert_id");
				exit();
			}
			else
			{
				//登録NG
				MCWEB_InternalServerErrorException();
				exit();
			}
		}
		else
		{
			//既に登録されていた
			throw new MCWEB_BadRequestException();
			exit();
		}
	}
}
?>