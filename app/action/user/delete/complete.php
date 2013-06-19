<?php

class _user_delete_complete extends GetScene
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
		
	}
}

?>