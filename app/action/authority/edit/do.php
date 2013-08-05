<?php
/**
 *	権限マスタ管理　新規/修正　登録処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Authority.php");
require_once(DIR_APP . "/class/message/MessageAuthConfigManager.php");
class _authority_edit_do extends PostScene
{
	// パラメータ
	var $_id;				// 権限ID
	var $_name;				// 権限名
	var $_administrator_flg;	// 管理者権限
	var $_auth_config;		// 権限タイプ
	var $_delete_flg;		// 削除フラグ
	
	var $type=false;		// 新規or修正

	/* (non-PHPdoc)
	 * @see MCWEB_InterfaceScene::check()
	 */
	function check()
	{
		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->type = 'new';
		}
		else
		{
			$this->type = 'edit';
		}

		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'id',				ValidatorInt::createInstance()->nullable()->min(0)
			, 'administrator_flg',	ValidatorString::createInstance()->min(AdministratorFlgDefine::ADMINISTRATOR_FLG_OFF)->max(AdministratorFlgDefine::ADMINISTRATOR_FLG_ON)
			, 'name',			ValidatorString::createInstance()->min(1)->max(USER_AUTHORITY_NAME_MAX)
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
						$error_msg[] = '権限IDの指定が不正です。';
						break;
					case 'administrator_flg':
						$error_msg[] = '管理者権限を指定して下さい。';
						break;
					case 'name':
						$error_msg[] = '権限名は必須です。'.USER_AUTHORITY_NAME_MAX.'文字以内で入力して下さい。';
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
			$f = new MCWEB_SceneForward('/post/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	/* (non-PHPdoc)
	 * @see MCWEB_InterfaceScene::task()
	 */
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 削除フラグ
		if ($this->_delete_flg)
		{
			$delete_flg = 1;
		}
		else
		{
			$delete_flg = 0;
		}

		// 選択権限KEYを取得
		$message_manager = MessageAuthConfigManager::getInstance();
		$this->auth_config_key = $message_manager->getAuthConfigKeys();

		$obj_authority = new Authority();
		if ($this->type == 'new')
		{
			// 管理者権限が設定されている場合は権限設定にNULL
			$auth_config = json_encode($this->_auth_config);
			if ($this->_administrator_flg || $auth_config == "null")
			{
				$auth_config = json_encode(array());
			}
			// 新規登録の時
			$regist_data = array(
					'name'              => $this->_name,
					'administrator_flg' => $this->_administrator_flg,
					'auth_config'       => $auth_config,
					'delete_flg'        => $delete_flg,
			);

			list($return, $insert_id) = $obj_authority->insert($regist_data);
		}
		else
		{
			// 修正の時
			$authority_data = $obj_authority->getDataById($this->_id, true); // 削除済み含む
			if (empty($authority_data))
			{
				// 修正データが存在しない時は不正なアクセス
				MCWEB_Util::redirectAction("/authority/index?edit_type={$this->type}&id={$this->_id}&error_flg=1");
			}
			
			$auth_config = json_encode($this->_auth_config);
			// 管理者権限が設定されている場合は権限設定にNULL
			if ($this->_administrator_flg || $auth_config == "null")
			{
				$auth_config = json_encode(array());
			}
			$update_data = array(
					'name'              => $this->_name,
					'administrator_flg' => $this->_administrator_flg,
					'auth_config'       => $auth_config,
					'delete_flg'        => $delete_flg,
			);
			$return = $obj_authority->update($this->_id, $update_data);
		}

		if ($return > 0)
		{
			// 影響を与えた行数がある
			if ($this->type == 'new')
			{
				MCWEB_Util::redirectAction("/authority/index?edit_type={$this->type}&id={$insert_id}");
			}
			else
			{
				MCWEB_Util::redirectAction("/authority/index?edit_type={$this->type}&id={$this->_id}");
			}
		}
		else
		{
			MCWEB_Util::redirectAction("/authority/index?edit_typet={$this->type}&id={$this->_id}&error_flg=1");
		}
	}
	
}

?>