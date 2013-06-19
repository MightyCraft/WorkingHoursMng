<?php

require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");

class _client_delete_do extends PostScene
{
	var $_id;

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
		);
		
		if(!empty($errors))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
		
		// プロジェクトマスタに使用されているかどうか（削除済は除く）
		$obj_project	= new Project;
		$project_data = $obj_project->getDataByClientId($this->_id);
		if(!empty($project_data))
		{
			//使用されている
			$errors = 'プロジェクトマスタで使用されているので削除できません。';
		}
		if (!empty($errors))
		{
			$this->_error = $errors;
			$f = new MCWEB_SceneForward('/client/delete/confirm');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_client = new Client;
		
		$id = $this->_id;
		
		$data = array(
		$id,
		);
		
		$res = $obj_client->deleteClient($data);
		
		if($res == 1)
		{
			//削除OK
			MCWEB_Util::redirectAction("/client/delete/complete?id=$id");
			exit();
		}
		else
		{
			//削除NG
			MCWEB_InternalServerErrorException();
			exit();
		}
		
		exit();
	}
}
?>