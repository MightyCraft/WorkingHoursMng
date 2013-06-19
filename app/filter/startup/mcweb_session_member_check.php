<?php

require_once(DIR_APP . '/class/common/dbaccess/Member.php');
/**
 * セッションを確認し、ログイン管理を行う
 */
class mcweb_session_member_check implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		// ログインチェック対象ディレクトリへの遷移の場合
		$pattern = '/^\/(login|errors)\//';	// ログインチェック対象外ディレクトリ
		if(!preg_match($pattern, $entry_path))
		{
			//SESSIONによりログイン確認
			if(!isset($_SESSION['member_id']))
			{
				//COOKIEにログイン情報が保存されていないか確認
				if(isset($_COOKIE['cookie_manhour_member_id']))
				{
					$_SESSION['member_id']	= $_COOKIE['cookie_manhour_member_id'];
				}
				else
				{
					MCWEB_Util::redirectAction('/login/index');
				}
			}
			//ログインが完了している場合、メンバーデータを取得
			if(isset($_SESSION['member_id']))
			{
				$obj_common_member	= new Member();
				$login_member		= $obj_common_member->getMemberById($_SESSION['member_id']);
				//メンバーデータの取得失敗したら、再ログイン
				if(empty($login_member))
				{
					MCWEB_Util::redirectAction('/login/index');
				}
				if(!isset($_SESSION['manhour']))
				{
					$_SESSION['manhour']	= array();
				}

				$_SESSION['manhour']['member']					= array();
				$_SESSION['manhour']['member']['id']			= $login_member['id'];
				$_SESSION['manhour']['member']['member_code']	= $login_member['member_code'];
				$_SESSION['manhour']['member']['name']			= $login_member['name'];
				$_SESSION['manhour']['member']['auth_lv']		= $login_member['auth_lv'];
				$_SESSION['manhour']['member']['post']			= $login_member['post'];
				$_SESSION['manhour']['member']['position']		= $login_member['position'];
			}

			//管理画面の権限設定
			$auth	= $_SESSION['manhour']['member']['auth_lv'];
			$post	= $_SESSION['manhour']['member']['post'];

			// 各種権限判定
			$auth_account_management = checkAuthAccountManagement($auth, $post);
			$auth_project_management = checkAuthProjectManagement($auth, $post);
			$auth_client_management = checkAuthClientManagement($auth, $post);
			$auth_holiday_management = checkAuthHolidayManagement($auth, $post);
			$auth_post_management = checkAuthPostManagement($auth, $post);

			$_SESSION['manhour']['member']['auth']['account'] = $auth_account_management;
			$_SESSION['manhour']['member']['auth']['project'] = $auth_project_management;
			$_SESSION['manhour']['member']['auth']['client'] = $auth_client_management;
			$_SESSION['manhour']['member']['auth']['holiday'] = $auth_holiday_management;
			$_SESSION['manhour']['member']['auth']['post'] = $auth_post_management;

			$management_array = array(
				'/^\/user\/(index|delete|new)/'			=>	$auth_account_management,
				'/^\/project\/(index|delete|new|edit)/'	=>	$auth_project_management,
				'/^\/client\//'							=>	$auth_client_management,
				'/^\/holiday\//'						=>	$auth_holiday_management,
				'/^\/post\//'							=>	$auth_post_management,
			);
			foreach($management_array as $key => $value)
			{
				if(preg_match($key, $entry_path))
				{
					if(!$value)
					{
						MCWEB_Util::redirectAction('/index');
					}
				}
			}
		}
		//上記以外
		else
		{
			//COOKIEにログイン情報が保存されていないか確認
			if(isset($_COOKIE['cookie_manhour_member_id']))
			{
				$_SESSION['member_id']	= $_COOKIE['cookie_manhour_member_id'];
			}
		}
	}
}

?>