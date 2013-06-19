<?php

abstract class GetScene extends APP_Scene
{
	function regist_types()
	{
		return array('GET', 'COOKIE', 'FORWARD');
	}
}

?>