<?php

require_once(DIR_APP . '/class/common/dbaccess/Member.php');
/**
 * セッションを確認し、ログイン管理を行う
 */
class mcweb_session_member_check implements MCWEB_filter_startup
{
	function run($entry_path)
	{
		// ログインチェック対象ディレクトリのチェック
		$pattern = '/^\/(login|errors)\//';	// ログインチェック対象外ディレクトリ

		if(!preg_match($pattern, $entry_path))
		{
			// ログインチェック対象ディレクトリへの遷移の場合

			//--------------------------
			// ログインチェック処理
			//--------------------------
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
				$_SESSION['manhour']['member']['id']					= $login_member['id'];
				$_SESSION['manhour']['member']['member_code']			= $login_member['member_code'];
				$_SESSION['manhour']['member']['name']					= $login_member['name'];
				$_SESSION['manhour']['member']['auth_lv']				= $login_member['auth_lv'];
				$_SESSION['manhour']['member']['post']					= $login_member['post'];
				$_SESSION['manhour']['member']['position']				= $login_member['position'];
				$_SESSION['manhour']['member']['mst_member_type_id']	= $login_member['mst_member_type_id'];
				$_SESSION['manhour']['member']['mst_member_cost_id']	= $login_member['mst_member_cost_id'];
			}
		}
		else
		{
			// ログインチェック対象外ディレクトリへの遷移の場合

			//COOKIEにログイン情報が保存されていないか確認
			if(isset($_COOKIE['cookie_manhour_member_id']))
			{
				$_SESSION['member_id']	= $_COOKIE['cookie_manhour_member_id'];
			}
		}
	}
}

?>