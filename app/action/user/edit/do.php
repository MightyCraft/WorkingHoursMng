<?php
/**
 * アカウント管理 編集
 */
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_edit_do extends PostScene
{
	var $_submit;

	var $_id;
	var $_member_code;
	var $_name;
	var $_auth_lv;
	var $_post;
	var $_position;
	var $_password = '';
	var $_password_change = '';
	
	function check()
	{
		// TODO: ブラッシュアップ
		$array_auth_lv	= returnArrayAuthLv();
		$max_auth_lv	= count($array_auth_lv) -1;

		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
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
			// パスワード変更
			, 'password_change', ValidatorAlphanumeric::createInstance()->min(0)->max(1)
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

		//自身のアカウント以外なら、下記の検査は無視する
		if(!empty($this->_id) && ($this->_id != $_SESSION['manhour']['member']['id']))
		{
			unset($errors['password']);
		}
		else
		{
			// 他ユーザ編集時にパスワード変更無しの場合は、下記の検査は無視する
			if ($this->_password_change != 1)
			{
				unset($errors['password']);
			}
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
		$password_change	= $this->_password_change;
		$update_date	= date('Y-m-d H:i:s');
		$id				= $this->_id;

		$data					= array();
		$data['member_code']	= $member_code;
		$data['name']			= $name;
		$data['auth_lv']		= $auth_lv;
		$data['post']			= $post;
		$data['position']		= $position;
		$data['update_date']	= $update_date;

		// パスワードを暗号化
		if($id == $_SESSION['manhour']['member']['id'])
		{

			
			if (!empty($this->_password_change))
			{
				$data['password']	= hashPassWord($password);
			}
		} 
		else 
		{
			// 他ユーザのパスワード初期化選択時はパスワードNULL値を暗号化
			if (!empty($this->_password_change))
			{
				$data['password']	= hashPassWord('');
			}
		}

		// 所属マスタの存在チェック
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		if (!isset($array_post[$post]))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
		// 役職マスタの存在チェック
		$obj_position = new Position();
		$array_position	= $obj_position->getDataAll();
		if (!isset($array_position[$this->_position]))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}

		$obj_member		= new Member;
		$res = $obj_member->updateMemberToParam($id, $data);

		if($res == 1)
		{
			//所属プロジェクト登録
			$obj_project_team	= new ProjectTeam;
			if(is_array($_SESSION['manhour']['tmp']['user_project_team_list']) ) {
				$obj_project_team->deleteProjectTeamByMemberId($id);
				foreach($_SESSION['manhour']['tmp']['user_project_team_list'] as $key => $value) {
					$data	= array($id, $value['project_id'], $update_date);
					$obj_project_team->writeProjectTeam($data);
				}
			}

			//登録OK
			unset($_SESSION['manhour']['tmp']['user_edit']);				//一時的なセッションを削除
			unset($_SESSION['manhour']['tmp']['user_project_team_list']);	//一時的なセッションを削除

			//更新OK
			$url = "/user/edit/complete?id=$id";
			if (!empty($this->_password_change))
			{
				$url .= '&password_change=1';
			}
			
			MCWEB_Util::redirectAction($url);
			exit();
		}
		else
		{
			//更新NG
			MCWEB_InternalServerErrorException();
			exit();
		}
	}
}
?>