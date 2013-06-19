<?php

require_once(DIR_APP . "/class/common/dbaccess/Client.php");

class _client_new_complete extends GetScene
{
	var $_insert_id;

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'insert_id', ValidatorInt::createInstance()
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

		$client_by_id = $obj_client->getClientById($this->_insert_id);

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('client_by_id', $client_by_id);
	}
}

?>