<?php

require_once(DIR_APP . "/class/common/dbaccess/Client.php");

class _client_edit_complete extends GetScene
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
	}
	
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_client = new Client;
		
		$client_by_id = $obj_client->getClientById($this->_id);
		
		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('client_by_id', $client_by_id);
	}
}

?>