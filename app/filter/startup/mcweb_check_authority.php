<?php

require_once(DIR_APP . '/class/common/dbaccess/Member.php');
require_once(DIR_APP . '/class/common/dbaccess/Authority.php');
require_once(DIR_APP . '/class/message/MessageAuthConfigManager.php');

/**
 * セッションを確認し、ログイン管理を行う
 */
class mcweb_check_authority implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		// ログインチェック対象ディレクトリのチェック
		$pattern = '/^\/(login|errors)\//'; // ログインチェック対象外ディレクトリ

		if (!preg_match($pattern, $entry_path))
		{

			if (!isset($_SESSION['manhour']['member']['auth_lv']))
			{
				MCWEB_Util::redirectAction('/login/index');
			}

			// マスタから権限を取得
			$obj_authority = new Authority();
			$authority = $obj_authority->getDataById($_SESSION['manhour']['member']['auth_lv']);
			
			// 管理者権限の取得
			$administrator_flg = $authority['administrator_flg'];
			$_SESSION['manhour']['member']['auth']['administrator_flg'] = $administrator_flg;

			// ----------------------
			// 入力制限判定
			// ----------------------		
			$auth_config = json_decode($authority['auth_config']);
			if ($auth_config == 'null')
			{
				$auth_config = array();
			}

			// セッションに権限情報を格納
			$disabled_keys = array();
			$auth_config_keys = MessageAuthConfigManager::getInstance()->getAuthConfigKeys();

			foreach ($auth_config_keys as $auth_config_key)
			{
				// 管理者、もしくは権限が設定されている場合はアクセス可能設定を行う
				if (in_array($auth_config_key, $auth_config) || $administrator_flg)
				{
					$_SESSION['manhour']['member']['auth'][str_replace(':', '_', $auth_config_key)] = true;
				}
				else
				{
					$_SESSION['manhour']['member']['auth'][str_replace(':', '_', $auth_config_key)] = false;
					// 接頭語が[open_]のKEY以外の場合はアクセス許可
					if (!strpos('open_', $auth_config_key))
					{
						// 末尾が':'の場合は先方一致で比較する設定
						if (mb_substr($auth_config_key, -1) == ':')
						{
							$disabled_keys[] = '/^\/'.str_replace(':', '\/', $auth_config_key).'/';
						}
						else
						{
							$disabled_keys[] = '/^\/'.str_replace(':', '\/', $auth_config_key).'$/';
						}
					}
				}
			}

			// 管理者の場合はアクセス制限のチェックはおこなわない
			if ($administrator_flg)
			{
				return;
			}
				
			// 権限の無い機能へのアクセスの場合はトップページへリダイレクト
			foreach ($disabled_keys as $key => $value)
			{
				if (preg_match($value, $entry_path))
				{
					MCWEB_Util::redirectAction('/index');
				}
			}

		}
	}
}

?>