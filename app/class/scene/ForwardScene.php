<?php

abstract class ForwardScene extends APP_Scene implements MCWEB_InterfaceScenePrivate
{
	function regist_types()
	{
		return array('FORWARD');
	}
}
?>