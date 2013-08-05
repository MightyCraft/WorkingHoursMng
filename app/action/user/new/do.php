<?php
/**
 * 「アカウント管理」新規登録
 */
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
class _user_new_do extends PostScene
{
	var $_member_code;
	var $_name;
	var $_auth_lv;
	var $_post;
	var $_position;
	var $_mst_member_type_id;
	var $_mst_member_cost_id;
	var $_password;
	var $_password_change;


	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// 氏名
			, 'name', ValidatorString::createInstance()->min(1)->max(USER_MEMBER_NAME_MAX)
			// 権限レベル
			, 'auth_lv', ValidatorInt::createInstance()->min(0)
			// 所属
			, 'post', ValidatorInt::createInstance()->min(1)
			// 役職
			, 'position', ValidatorInt::createInstance()->min(1)
			// 社員タイプ
			, 'mst_member_type_id', ValidatorInt::createInstance()->min(1)
			// 社員コスト
			, 'mst_member_cost_id', ValidatorInt::createInstance()->min(1)
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
		// 権限の存在チェック
		$this->array_auth_lv = returnArrayAuthLv();
		if (!isset($this->array_auth_lv[$this->_auth_lv]))
		{
			$errors['auth_lv'][] = 'not_exists';
		}

		if(!empty($errors))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
	}
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 所属マスタの存在チェック
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		if (!isset($array_post[$this->_post]))
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
		// 社員タイプ存在チェック
		$obj_member_type	= new MemberType();
		$array_member_type	= $obj_member_type->getDataAll();
		if (!isset($array_member_type[$this->_mst_member_type_id]))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
		// 社員コスト存在チェック
		$obj_member_cost	= new MemberCost();
		$array_member_cost	= $obj_member_cost->getDataAll();
		if (!isset($array_member_cost[$this->_mst_member_cost_id]))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
		// 登録されている社員かの確認
		$obj_member		= new Member;
		$chk_flg = $obj_member->isMember($this->_member_code);
		if ($chk_flg)
		{
			//既に登録されていた
			throw new MCWEB_BadRequestException();
			exit();
		}


		// 登録処理
		$data = array(
			'member_code'			=>$this->_member_code,
			'name'					=>$this->_name,
			'auth_lv'				=>$this->_auth_lv,
			'post'					=>$this->_post,
			'position'				=>$this->_position,
			'mst_member_type_id'	=>$this->_mst_member_type_id,
			'mst_member_cost_id'	=>$this->_mst_member_cost_id,
			'password'				=>hashPassWord($this->_password),
		);

		$res = 0;
		list($res,$insert_id) = $obj_member->insertMember($data);
		if($res == 1)
		{
			// アカウントの登録が成功後、所属プロジェクト登録
			$obj_project_team	= new ProjectTeam;
			if (is_array($_SESSION['manhour']['tmp']['user_project_team_list']) )
			{
				$regist_date = date('Y-m-d H:i:s');	// 登録日
				foreach($_SESSION['manhour']['tmp']['user_project_team_list'] as $key => $value)
				{
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
}
?>