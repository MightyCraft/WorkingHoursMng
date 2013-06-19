<?php

require_once(DIR_APP . "/class/common/dbaccess/Client.php");

class _client_edit_index extends PostAndGetScene
{
	var $_error;

	var $_submit;

	var $_id;
	var $_name;
	var $_memo;

	function check()
	{

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_client = new Client;

		$client_by_id = $obj_client->getClientById($this->_id);
		if (!empty($client_by_id))
		{
			if(empty($this->_submit) || $this->_submit != 2)
			{
				$this->_id = $client_by_id['id'];
				$this->_name = $client_by_id['name'];
				$this->_memo = $client_by_id['memo'];
			}
		}
		else
		{
			// マスター存在無し
			MCWEB_Util::redirectAction('/client/index');
		}

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
	}
}

?>