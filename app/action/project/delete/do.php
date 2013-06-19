<?php
/**
 *	プロジェクト　削除　実行
 */
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _project_delete_do extends PostAndGetScene
{
	var	$_id;

	function check()
	{
		if(empty($this->_id))
		{
			MCWEB_Util::redirectAction("/project/index");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_project	= new Project;

		//削除実行
		$res	= $obj_project->deleteProject(array($this->_id));
		if($res)
		{
			MCWEB_Util::redirectAction("/project/delete/complete?id={$this->_id}");
		}
		else
		{
			MCWEB_InternalServerErrorException();
		}
	}
}

?>