<?php

abstract class APP_Scene extends MCWEB_Scene
{
	function do_task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$access->text('session', $_SESSION);
		return $this->task($access);
	}
}
?>
