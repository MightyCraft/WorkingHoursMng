<?php
/**
 *	権限マスタ管理　新規/修正確認画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Authority.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/message/MessageAuthConfigManager.php");
class _authority_edit_confirm extends PostScene
{
	// パラメータ
	var $_id;				// 権限ID
	var $_name;				// 権限名
	var $_administrator_flg;	// 管理者権限
	var $_auth_config;		// 権限タイプ
	var $_delete_flg;		// 削除フラグ

	var $new_flg=false;		// 新規登録フラグ
	var $member_flg=false;	// 削除時有効社員フラグ
	var $type;	// 削除時有効社員フラグ
	
	
	/* (non-PHPdoc)
	 * @see MCWEB_InterfaceScene::check()
	 */
	function check()
	{
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
			$f = new MCWEB_SceneForward('/authority/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	/* (non-PHPdoc)
	 * @see MCWEB_InterfaceScene::task()
	 */
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 管理者権限要素
		$this->administrator_flg_list = array(
				AdministratorFlgDefine::ADMINISTRATOR_FLG_ON  => '有',
				AdministratorFlgDefine::ADMINISTRATOR_FLG_OFF => '無'
		);

		// 権限選択部品生成
		$message_manager = MessageAuthConfigManager::getInstance();
		$this->auth_config_key = $message_manager->getAuthConfigKeys();
		$this->auth_config_name = $message_manager->getAuthConfigNames();

		// 管理者権限が付与されている場合は全て使用可能の表示をおこなう
		if ($this->_administrator_flg == AdministratorFlgDefine::ADMINISTRATOR_FLG_ON)
		{
			$this->_auth_config = $message_manager->getAuthConfigKeys();
		}
		$this->_selected_keys = $message_manager->getAuthConfigSelectedIndex($this->_auth_config);

		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->new_flg = true;
		}

		if (!$this->new_flg)
		{
			// 修正の時はデータ取得
			$obj_authority = new Authority();
			$authority_data = $obj_authority->getDataById($this->_id, true); // 削除済み含む
			if (empty($authority_data))
			{
				// 修正データが無かった時はエラー
				$this->_error[] = '指定されたIDの権限データがありません。';

			}

			// 削除状態にする場合紐付く社員が存在するかチェック（存在しても使用不可にするのは可能とする）
			if ($this->_delete_flg)
			{
				$obj_member = new Member();
				$post_member_data = $obj_member->getMemberByAuthLv($this->_id); // 削除済み社員含まない
				if (!empty($post_member_data))
				{
					$this->_error[] = 'この権限を使用している社員が存在しますので削除できません。';
				}
			}
			if (!empty($this->_error))
			{
				$this->_back_flg = true;
				$f = new MCWEB_SceneForward('/authority/edit/index');
				$f->regist('FORWARD', $this);
				return $f;
			}
		}

	}
}

?>