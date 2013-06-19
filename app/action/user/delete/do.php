<?php

require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
class _user_delete_do extends PostScene
{
	var $_id;

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
		);
		
		if(!empty($errors))
		{
			throw new MCWEB_BadRequestException();
			exit();
		}
	}
	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_member	= new Member;
		$id			= $this->_id;
		
		$data = array(
			$id,
		);
		
		$res = $obj_member->deleteMember($data);
		
		if($res == 1)
		{
			//所属プロジェクトデータも削除
			$obj_project_team	= new ProjectTeam;
			$obj_project_team->deleteProjectTeamByMemberId($id);
			
			//削除OK
			MCWEB_Util::redirectAction("/user/delete/complete?id=$id");
			exit();
		}
		else
		{
			//削除NG
			MCWEB_InternalServerErrorException();
			exit();
		}
		
		exit();
	}
}
?>