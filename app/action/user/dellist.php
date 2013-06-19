<?php
/**
 * 「アカウント管理」
 */
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _user_dellist extends PostAndGetScene
{
	//削除する番号
	var $_index_no;
	
	//次のアクション
	var	$_next;
	
	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		unset($_SESSION['manhour']['tmp']['user_project_team_list'][$this->_index_no]);
		
		//整列
		$alignment	= array();
		$loop		= 0;
		foreach($_SESSION['manhour']['tmp']['user_project_team_list'] as $value) {
			$alignment[$loop]	= $value;
			$loop++;
		}
		$_SESSION['manhour']['tmp']['user_project_team_list']	= $alignment;
		
		MCWEB_Util::redirectAction(urldecode($this->_next));
	}
}
?>