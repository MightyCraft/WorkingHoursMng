<?php
/**
 *	プロジェクト　削除　実行
 */
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _project_delete_do extends PostAndGetScene
{
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	var	$_id;

	function check()
	{
		$error_id = MCWEB_ValidationManager::validate(
			$this
			, 'id',			ValidatorInt::createInstance()->min(1)
		);
		if (!empty($error_id))
		{
			throw new MCWEB_BadRequestException();
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_project	= new Project;

		//削除実行
		$update_columns = array(
			'delete_flg'	=> 1,
		);
		$res	= $obj_project->updateProject($this->_id,$update_columns);

		if($res)
		{
			MCWEB_Util::redirectAction("/project/delete/complete?id={$this->_id}&type={$this->_type}");
		}
		else
		{
			MCWEB_InternalServerErrorException();
		}
	}
}

?>