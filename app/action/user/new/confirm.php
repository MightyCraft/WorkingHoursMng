<?php
/**
 * 「アカウント管理」新規作成確認画面
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_new_confirm extends UserScene
{
	var	$_team_list_project_id	= 0;
	var $_member_code;
	var $_name;
	var $_auth_lv;
	var $_post;
	var $_position;
	var $_password;
	var $_password_change;

	function check()
	{
		//セッション保存
		$_SESSION['manhour']['tmp']['user_edit']							= array();
		$_SESSION['manhour']['tmp']['user_edit']['member_code']				= $this->_member_code;
		$_SESSION['manhour']['tmp']['user_edit']['name']					= $this->_name;
		$_SESSION['manhour']['tmp']['user_edit']['auth_lv']					= $this->_auth_lv;
		$_SESSION['manhour']['tmp']['user_edit']['post']					= $this->_post;
		$_SESSION['manhour']['tmp']['user_edit']['position']				= $this->_position;
		$_SESSION['manhour']['tmp']['user_edit']['password']				= $this->_password;
		$_SESSION['manhour']['tmp']['user_edit']['team_list_project_id']	= $this->_team_list_project_id;

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
			, 'position', ValidatorInt::createInstance()
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

		// エラーメッセージ
		$error_msg = array();

		if (!empty($errors))
		{
			if(isset($errors['member_code']))
			{
				$mm = MessageManager::getInstance();
				$error_msg['member_code'] = $mm->sprintfMessage(MessageDefine::USER_ERR_MESSAGE_MEMBER_CODE);
			}

			if(isset($errors['name']))
			{
				if($errors['name'][0] == 'min')
				{
					$error_msg['name'] = '氏名が未入力です。';
				}
				elseif($errors['name'][0] == 'max')
				{
					$error_msg['name'] = '氏名は'.USER_MEMBER_NAME_MAX.'文字以下で入力して下さい。';
				}
				else
				{
					$error_msg['name'] = '氏名の入力値が不正です。';
				}
			}

			if(isset($errors['auth_lv']))
			{
				if($errors['auth_lv'][0] == 'format')
				{
					$error_msg['auth_lv'] = '権限レベルは半角数字で入力して下さい。';
				}
				elseif($errors['auth_lv'][0] == 'min')
				{
					$error_msg['auth_lv'] = '権限レベルが未指定です。';
				}
				elseif($errors['auth_lv'][0] == 'max')
				{
					$error_msg['auth_lv'] = '権限レベル入力値は'.$max_auth_lv.'以下で入力して下さい。';
				}
				else
				{
					$error_msg['auth_lv'] = '権限レベルの入力値が不正です。';
				}
			}

			if(isset($errors['post']))
			{
				if($errors['post'][0] == 'format')
				{
					$error_msg['post'] = '所属は半角数字で入力して下さい。';
				}
				elseif($errors['post'][0] == 'min')
				{
					$error_msg['post'] = '所属が指定されていません。';
				}
				else
				{
					$error_msg['post'] = '所属の入力値が不正です。';
				}
			}


			if(isset($errors['position']))
			{
				if($errors['position'][0] == 'format')
				{
					$error_msg['position'] = '役職は半角数字で入力して下さい。';
				}
				elseif($errors['position'][0] == 'min')
				{
					$error_msg['position'] = '役職が指定されていません。';
				}
				else
				{
					$error_msg['position'] = '役職の入力値が不正です。';
				}
			}
				
			if(isset($errors['password']))
			{
				if($errors['password'][0] == 'format')
				{
					$error_msg['password'] = 'パスワードは半角数字で入力して下さい。';
				}
				elseif($errors['password'][0] == 'min')
				{
					$error_msg['password'] = 'パスワードが未入力です。';
				}
				elseif($errors['password'][0] == 'max')
				{
					$error_msg['password'] = 'パスワードは半角英数字'.USER_MEMBER_PASSWORD_MAX.'文字以下で入力して下さい。';
				}
				else
				{
					$error_msg['password'] = 'パスワードの入力値が不正です。';
				}
			}
		}

		if (!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/user/new/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		//登録されているかの確認
		$obj_member = new Member;
		$chk_flg = $obj_member->isMember($this->_member_code);
		if($chk_flg == 1)
		{
			$error_msg['member_code'] = '社員コード入力値が既に登録されています。';
		}
		// 所属マスタに存在するかの確認
		$obj_post	= new Post();
		$array_post	= $obj_post->getDataAll();
		if (!isset($array_post[$this->_post]))
		{
			$error_msg['post'] = '存在しない部署を指定しています。';
		}
		// 役職マスタに存在するかの確認
		$obj_position = new Position();
		$array_position	= $obj_position->getDataAll();
		if (!isset($array_position[$this->_position]))
		{
			$error_msg['position'] = '存在しない役職を指定しています。';
		}
		if (!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/user/new/index');
			$f->regist('FORWARD', $this);
			return $f;
		}

		$array_auth_lv	= returnArrayAuthLv();
		$password_tmp = changePassWord(sprintf('%'.USER_MEMBER_PASSWORD_MAX.'s', 'a'));

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('password_tmp',	$password_tmp);
		$access->text('array_auth_lv',	$array_auth_lv);
		$access->text('array_post',		$array_post);
		$access->text('array_position',	$array_position);
		
		//セッションのデータを表示用にセット
		$this->setProjectTeamViewListBySession();
	}
}
?>