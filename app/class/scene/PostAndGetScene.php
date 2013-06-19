<?php

abstract class PostAndGetScene extends APP_Scene
{
	function regist_types()
	{
		return array('GET', 'POST', 'COOKIE', 'FORWARD');
	}
}
?>