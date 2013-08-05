<?php
/**
 *	プロジェクト　削除　完了
 */
class _project_delete_complete extends PostAndGetScene
{
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	var	$_id;

	function check()
	{
		if(empty($this->_id))
		{
			MCWEB_Util::redirectAction("/project/index");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
	}
}

?>