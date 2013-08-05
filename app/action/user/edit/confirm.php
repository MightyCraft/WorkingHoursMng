<?php
/**
 * アカウント管理 編集
 */
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
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
	//社員タイプ
	var $_mst_member_type_id;
	//社員コスト
	var $_mst_member_cost_id;
	//パスワード
	var $_password;
	//パスワードリセット有無
	var $_password_change;
	//所属プロジェクト選択のプロジェクトID
	var $_team_list_project_id;

	// 画面
	var $password_change;
	var $array_auth_lv;
	var $array_post;
	var $array_position;
	var $array_member_type;
	var $array_member_cost;

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
			, 'post', ValidatorInt::createInstance()->min(0)
			// 役職
			, 'position', ValidatorInt::createInstance()->min(0)
			// 社員タイプ
			, 'mst_member_type_id', ValidatorInt::createInstance()->min(1)
			// 社員コスト
			, 'mst_member_cost_id', ValidatorInt::createInstance()->min(1)
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
				if($errors['auth_lv'][0] == 'min')
				{
					$error_msg['auth_lv'] = '権限が未指定です。';
				}
				else
				{
					$error_msg['auth_lv'] = '権限の入力値が不正です。';
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

			if(isset($errors['mst_member_type_id']))
			{
				if($errors['mst_member_type_id'][0] == 'format')
				{
					$error_msg['mst_member_type_id'] = '社員タイプは半角数字で入力して下さい。';
				}
				elseif($errors['mst_member_type_id'][0] == 'min')
				{
					$error_msg['mst_member_type_id'] = '社員タイプが指定されていません。';
				}
				else
				{
					$error_msg['mst_member_type_id'] = '社員タイプの入力値が不正です。';
				}
			}

			if(isset($errors['mst_member_cost_id']))
			{
				if($errors['mst_member_cost_id'][0] == 'format')
				{
					$error_msg['mst_member_cost_id'] = '社員コストは半角数字で入力して下さい。';
				}
				elseif($errors['mst_member_cost_id'][0] == 'min')
				{
					$error_msg['mst_member_cost_id'] = '社員コストが指定されていません。';
				}
				else
				{
					$error_msg['mst_member_cost_id'] = '社員コストの入力値が不正です。';
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
		$this->checkAuthEditMyAccount($this->_id);
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 所属マスタに存在するかの確認
		$obj_post	= new Post();
		$this->array_post	= $obj_post->getDataAll();
		if (!isset($this->array_post[$this->_post]))
		{
			$error_msg['post'] = '存在しない部署を指定しています。';
		}
		// 役職マスタに存在するかの確認
		$obj_position = new Position();
		$this->array_position	= $obj_position->getDataAll();
		if (!isset($this->array_position[$this->_position]))
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
		// 社員タイプマスタに存在するかの確認
		$obj_member_type			= new MemberType();
		$this->array_member_type	= $obj_member_type->getDataAll();
		if (!isset($this->array_member_type[$this->_mst_member_type_id]))
		{
			$error_msg['mst_member_type_id'] = '存在しない社員タイプを指定しています。';
		}
		// 社員コストマスタに存在するかの確認
		$obj_member_cost			= new MemberCost();
		$this->array_member_cost	= $obj_member_cost->getDataAll();
		if (!isset($this->array_member_cost[$this->_mst_member_cost_id]))
		{
			$error_msg['mst_member_cost_id'] = '存在しない社員コストを指定しています。';
		}

		$this->password_change = $this->_password_change;

		//セッションのデータを表示用にセット
		$this->setProjectTeamViewListBySession();
	}
}
?>