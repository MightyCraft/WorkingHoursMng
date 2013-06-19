<?php

/**
 * シーンに、MCWEB_InterfaceUrlParamAutoRegistを利用したURLパラメータの登録を行う
 */
class mcweb_auto_regist_input implements MCWEB_filter_scene_input
{
	function run($action_path, $scene, MCWEB_InterfaceUrlParam $param)
	{
		MCWEB_Framework::auto_regist($scene, $param);
	}
}

?>