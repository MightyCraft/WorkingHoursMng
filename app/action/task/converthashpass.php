<?php
/**
 * 社員パスワードコンバート
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
class _task_converthashpass extends PostAndGetScene
{
	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$member_dao = new Member();
		$menber_list = $member_dao->getMemberAll(true);
		foreach ($menber_list as $menber)
		{
			$menber['password'] = hashPassWord($menber['password']);
			$member_dao->updateMemberToParam($menber['id'], $menber, false);
		}
		echo 'コンバート処理終了';
		exit;
	}
}

?>