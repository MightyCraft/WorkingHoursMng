<?php

class _index extends GetScene
{
	function check()
	{
		if(!isset($_SESSION["member_id"]))
		{
			MCWEB_Util::redirectAction('/login/index');
		}
		
		MCWEB_Util::redirectAction('/input/index');
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
	}
}

?>