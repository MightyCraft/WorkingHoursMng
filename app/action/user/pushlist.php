<?php
/**
 * 「アカウント管理」にて「新規追加」ボタンをクリック時の処理
 */
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
class _user_pushlist extends PostAndGetScene
{
	//セッション更新用
	var	$_team_list_project_id	= 0;
	var	$_team_list_project_id_keyword = "";

	//状態保存用
	var	$_id;
	var	$_member_code;
	var	$_name;
	var	$_auth_lv;
	var	$_post;
	var	$_position;
	var	$_password;
	var	$_password_change;
	var	$_next;
	
	function check()
	{
		$_SESSION['manhour']['tmp']['user_edit']							= array();
		$_SESSION['manhour']['tmp']['user_edit']['id']						= ($this->_id			? $this->_id			: false);
		$_SESSION['manhour']['tmp']['user_edit']['member_code']				= ($this->_member_code	? $this->_member_code	: false);
		$_SESSION['manhour']['tmp']['user_edit']['name']					= ($this->_name			? $this->_name			: false);
		$_SESSION['manhour']['tmp']['user_edit']['auth_lv']					= ($this->_auth_lv		? $this->_auth_lv		: false);
		$_SESSION['manhour']['tmp']['user_edit']['post']					= ($this->_post			? $this->_post			: false);
		$_SESSION['manhour']['tmp']['user_edit']['position']				= ($this->_position		? $this->_position		: false);
		$_SESSION['manhour']['tmp']['user_edit']['password']				= ($this->_password		? $this->_password		: false);
		$_SESSION['manhour']['tmp']['user_edit']['password_change']			= ($this->_password_change		? $this->_password_change		: false);
		$_SESSION['manhour']['tmp']['user_edit']['team_list_project_id']	= ($this->_team_list_project_id	? $this->_team_list_project_id	: false);
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		//未選択エラー
		if(empty($this->_team_list_project_id))
		{
			MCWEB_Util::redirectAction($this->_next . '?team_error=no_select');
		}
		
		$obj_client		= new Client;
		$obj_project	= new Project;
		
		//念の為、クライアントIDとプロジェクトIDの整合性をチェック
		$check_client	= $obj_client->getClientByProject($this->_team_list_project_id);
		if(!$check_client) {
			MCWEB_Util::redirectAction($this->_next . '?team_error=fraud');
		}
		
		//追加
		$work_array = array(
			'project_id'	=> $this->_team_list_project_id,
		);
		
		if(is_array($_SESSION['manhour']['tmp']['user_project_team_list']) ) {
			//既に追加されているプロジェクトIDか確認
			foreach($_SESSION['manhour']['tmp']['user_project_team_list'] as $key => $value) {
				if($value['project_id'] == $this->_team_list_project_id) {
					MCWEB_Util::redirectAction($this->_next . '?team_error=overlap');
					break;
				}
			}
			array_push($_SESSION['manhour']['tmp']['user_project_team_list'], $work_array);
		} else {
			$_SESSION['manhour']['tmp']['user_project_team_list'][0]=$work_array;
		}
		MCWEB_Util::redirectAction($this->_next);
	}
}
?>