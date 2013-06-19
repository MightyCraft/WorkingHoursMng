<?php
/**
 *	プロジェクト　編集　完了
 */
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
class _project_edit_complete extends PostAndGetScene
{
	var	$_id;

	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		//先程編集したレコードを表示
		$obj_project		= new Project;
		$obj_client			= new Client;
		$obj_maintenance	= new MasterMaintenance;

		if(!empty($this->_id))
		{
			$project	= $obj_project->getDataById($this->_id);
			if(!empty($project))
			{
				$access->text('data', $project);
			}
		}
		//クライアントリスト取得
		$access->text('client_list', $obj_client->getDataAll());

		//PJコードタイプリスト
		$array_project_type = returnArrayPJtype();
		$access->text('array_project_type', $array_project_type);

		// アカウントリスト取得(営業のみ)
		$access->text('member_list', $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES));

	}
}

?>