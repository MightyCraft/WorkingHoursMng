<?php
/**
 * アカウント管理 編集
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_edit_confirm extends UserScene
{
	//編集対象のメンバーID
	var	$_id;
	//社員コード
	var $_member_code;
	//社員名
	var $_name;
	//権限
	var $_auth_lv;
	//所属
	var $_post;
	//役職
	var $_position;
	//パスワード
	var $_password;
	//パスワードリセット有無
	var $_password_change;
	//所属プロジェクト選択のプロジェクトID
	var $_team_list_project_id;
	
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
			, 'post', ValidatorInt::createInstance()->min(0)
			// 役職
			, 'position', ValidatorInt::createInstance()->min(0)
			// パスワード
			, 'password', ValidatorAlphanumeric::createInstance()->min(1)->max(USER_MEMBER_PASSWORD_MAX)
			// パスワード
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
					$error_msg['name'] = '氏名は'.USER_MEMBER_NAME_MAX.'文字以下をご入力して下さい。';
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
					$error_msg['post'] = '所属が未指定です。';
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
					$error_msg['position'] = '役職が未指定です。';
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
		// 廃止コードのプロジェクト指定は不可
		if (!empty($_SESSION['manhour']['tmp']['user_project_team_list']))
		{
			foreach ($_SESSION['manhour']['tmp']['user_project_team_list'] as $arr_team_project)
			{
				$check_project = $this->obj_project->getDataById($arr_team_project['project_id']);
				if ($check_project['project_type'] == PROJECT_TYPE_REMOVAL)
				{
					$error_msg['project_type'] = '廃止プロジェクトは所属プロジェクトに設定できません。';
					break;
				}
			}
		}

		if(!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/user/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}

		//権限チェック
		$this->checkAuthEditOtherAccount($this->_id);
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
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
		
		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('password_change',	$this->_password_change);
		$access->text('array_auth_lv',	$array_auth_lv);
		$access->text('array_post',		$array_post);
		$access->text('array_position',	$array_position);

		//セッションのデータを表示用にセット
		$this->setProjectTeamViewListBySession();
	}
}
?>