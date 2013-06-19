<?php

abstract class PostScene extends APP_Scene
{
	function regist_types()
	{
		return array('POST', 'COOKIE', 'FORWARD');
	}
}
?>