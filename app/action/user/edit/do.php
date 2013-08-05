<?php
/**
 * アカウント管理 編集処理
 */
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
class _user_edit_do extends PostScene
{
	var $_submit;

	var $_id;
	var $_member_code;
	var $_name;
	var $_auth_lv;
	var $_post;
	var $_position;
	var $_mst_member_type_id;
	var $_mst_member_cost_id;
	var $_password = '';
	var $_password_change = '';

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
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
		// 権限の存在チェック
		$this->array_auth_lv = returnArrayAuthLv();
		if (!isset($this->array_auth_lv[$this->_auth_lv]))
		{
			$errors['auth_lv'][] = 'not_exists';
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
		// 所属マスタの存在チェック
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		if (!isset($array_post[$this->_post]))
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

		// 更新処理
		$data  = array(
			'member_code'			=> $this->_member_code,
			'name'					=> $this->_name,
			'auth_lv'				=> $this->_auth_lv,
			'post'					=> $this->_post,
			'position'				=> $this->_position,
			'mst_member_type_id'	=> $this->_mst_member_type_id,
			'mst_member_cost_id'	=> $this->_mst_member_cost_id,
			);
		// パスワード変更時
		if($this->_id == $_SESSION['manhour']['member']['id'])
		{
			if (!empty($this->_password_change))
			{
				// パスワードを暗号化
				$data['password'] = hashPassWord($this->_password);
			}
		}
		else
		{
			if (!empty($this->_password_change))
			{
				// 他ユーザのパスワード初期化選択時はパスワードNULL値を暗号化
				$data['password'] = hashPassWord('');
			}
		}


		$obj_member		= new Member;
		$res = $obj_member->updateMemberToParam($this->_id, $data);

		if($res == 1)
		{
			// 更新成功時に所属プロジェクト登録
			$obj_project_team	= new ProjectTeam;
			if(is_array($_SESSION['manhour']['tmp']['user_project_team_list']) )
			{
				$update_date	= date('Y-m-d H:i:s');
				$obj_project_team->deleteProjectTeamByMemberId($this->_id);
				foreach($_SESSION['manhour']['tmp']['user_project_team_list'] as $key => $value)
				{
					$data	= array($this->_id, $value['project_id'], $update_date);
					$obj_project_team->writeProjectTeam($data);
				}
			}

			//登録OK
			unset($_SESSION['manhour']['tmp']['user_edit']);				//一時的なセッションを削除
			unset($_SESSION['manhour']['tmp']['user_project_team_list']);	//一時的なセッションを削除

			//更新OK
			$url = "/user/edit/complete?id={$this->_id}";
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