<?php
/**
 *	権限マスタ管理　新規/修正画面
 */
require_once(DIR_APP . "/class/common/dbaccess/Authority.php");
require_once(DIR_APP . "/class/message/MessageAuthConfigManager.php");
class _authority_edit_index extends PostAndGetScene
{	
	// パラメータ
	var $_id=null;				// 権限ID
	var $_administrator_flg=0;	// 管理者権限
	var $_name=null;			// 権限名
	var $_delete_flg=null;		// 削除フラグ
	var $_auth_config;			// 権限設定
	
	var $_error;				// エラーメッセージ
	var $_back_flg=false;		// 戻りフラグ
	var $new_flg=false;		// 新規登録フラグ

	/* (non-PHPdoc)
	 * @see MCWEB_InterfaceScene::check()
	 */
	function check()
	{
		// 妥当性チェック
		// バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
				$this
				, 'id',		ValidatorInt::createInstance()->nullable()->min(1)
		);
		if (!empty($errors))
		{
			$this->_error[] = 'IDの指定が不正です。';
		}
		// 管理者フラグ
		if (!($this->_administrator_flg == AdministratorFlgDefine::ADMINISTRATOR_FLG_ON || $this->_administrator_flg == AdministratorFlgDefine::ADMINISTRATOR_FLG_OFF))
		{
			$this->_administrator_flg = AdministratorFlgDefine::ADMINISTRATOR_FLG_OFF;
		}
	}

	/* (non-PHPdoc)
	 * @see MCWEB_InterfaceScene::task()
	 */
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 管理者権限
		$this->administrator_flg_list = array(
				AdministratorFlgDefine::ADMINISTRATOR_FLG_ON  => '有',
				AdministratorFlgDefine::ADMINISTRATOR_FLG_OFF => '無'
		);

		// 権限情報取得
		$message_manager = MessageAuthConfigManager::getInstance();
		$this->auth_config_key = $message_manager->getAuthConfigKeys();
		$this->auth_config_name = $message_manager->getAuthConfigNames();

		// ID未指定の時は新規登録扱い
		if (empty($this->_id))
		{
			$this->new_flg = true;
		}

		if (!$this->new_flg && !$this->_back_flg)
		{
			// 修正の時はデータ取得（確認画面からの戻りの時は取得しない）
			$obj_authority = new Authority();
			$authority_data = $obj_authority->getDataById($this->_id, true); // 削除済み含む
			if (!empty($authority_data))
			{
				$this->_name = $authority_data['name'];
				$this->_selected_keys = $message_manager->getAuthConfigSelectedIndex(json_decode($authority_data['auth_config']));
				$this->_administrator_flg = $authority_data['administrator_flg'];
				$this->_delete_flg = $authority_data['delete_flg'];
			}
			else
			{
				$this->_error[] = '指定されたIDの権限データがありません。';
			}
		}
		else 
		// 確認画面から戻った場合
		if ($this->_back_flg)
		{
			$this->_selected_keys = $message_manager->getAuthConfigSelectedIndex($this->_auth_config);
		}
	}
}

?>