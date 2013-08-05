<?php

abstract class APP_Scene extends MCWEB_Scene
{
	function do_task(MCWEB_InterfaceSceneOutputVars $access)
	{
		if (!MCWEB_Framework::getInstance()->commandline)
		{
			$access->text('session', $_SESSION);
		}
		return $this->task($access);
	}
}
?>
