<?php

abstract class MCWEB_Scene implements MCWEB_InterfaceScene, MCWEB_InterfaceUrlParamAutoRegist, MCWEB_InterfaceSceneOutputableVarsObject
{

	function prefix_binary()
	{
		return '_bin_';
	}

	function prefix_text()
	{
		return '_';
	}

	function do_input(MCWEB_InterfaceUrlParam $param)
	{
		return $this->input($param);
	}
	function do_check()
	{
		return $this->check();
	}
	function do_task(MCWEB_InterfaceSceneOutputVars $access)
	{
		return $this->task($access);
	}
	function do_draw(MCWEB_InterfaceSceneOutputVars $access)
	{
		return $this->draw($access);
	}

	function input(MCWEB_InterfaceUrlParam $param)
	{
	}

	function draw(MCWEB_InterfaceSceneOutputVars $access)
	{
	}
}
?>
