<?php
/**
 * 「アカウント管理」－ 新規・編集時の所属プロジェクトの「行削除」処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _user_dellist extends PostAndGetScene
{
	// 削除する番号
	var $_index_no;

	// 戻るアクション
	var	$_next;

	// 行削除前のアカウント情報状態保存用
	var	$_id;
	var	$_member_code;
	var	$_name;
	var	$_auth_lv;
	var	$_post;
	var	$_position;
	var $_mst_member_type_id;
	var $_mst_member_cost_id;
	var	$_password;
	var	$_password_change;

	function check()
	{
		// 行削除前のアカウント情報状態をSESSIONにセット
		$_SESSION['manhour']['tmp']['user_edit']							= array();
		$_SESSION['manhour']['tmp']['user_edit']['id']						= ($this->_id					? $this->_id					: false);
		$_SESSION['manhour']['tmp']['user_edit']['member_code']				= ($this->_member_code			? $this->_member_code			: false);
		$_SESSION['manhour']['tmp']['user_edit']['name']					= ($this->_name					? $this->_name					: false);
		$_SESSION['manhour']['tmp']['user_edit']['auth_lv']					= ($this->_auth_lv				? $this->_auth_lv				: false);
		$_SESSION['manhour']['tmp']['user_edit']['post']					= ($this->_post					? $this->_post					: false);
		$_SESSION['manhour']['tmp']['user_edit']['position']				= ($this->_position				? $this->_position				: false);
		$_SESSION['manhour']['tmp']['user_edit']['mst_member_type_id']		= ($this->_mst_member_type_id	? $this->_mst_member_type_id	: false);
		$_SESSION['manhour']['tmp']['user_edit']['mst_member_cost_id']		= ($this->_mst_member_cost_id	? $this->_mst_member_cost_id	: false);
		$_SESSION['manhour']['tmp']['user_edit']['password']				= ($this->_password				? $this->_password				: false);
		$_SESSION['manhour']['tmp']['user_edit']['password_change']			= ($this->_password_change		? $this->_password_change		: false);
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