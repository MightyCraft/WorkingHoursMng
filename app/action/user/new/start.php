<?php
/**
 * アカウント管理 新規作成開始
 */
class _user_new_start extends UserScene
{
	//初期チェック
	function check()
	{
	}

	//処理
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$_SESSION['manhour']['tmp']['user_edit']				= array();
		$_SESSION['manhour']['tmp']['user_project_team_list']	= array();
		MCWEB_Util::redirectAction("/user/new/index");
	}
}

?>