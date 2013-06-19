<?php
/**
 * クライアント管理-削除処理
 *
 */

require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/Manhour.php");
class _client_delete_confirm extends PostAndGetScene
{
	var $_id;

	var $error=array();

	function check()
	{
		// バリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			// id
			, 'id', ValidatorInt::createInstance()
		);

		// エラーメッセージ
		$error_msg = array();

		if (!empty($errors))
		{
			if(isset($errors['id']))
			{
				if($errors['id'][0] == 'format')
				{
					$error_msg['id'] = 'クライアントID値が不正です。';
				}
				else
				{
					$error_msg['id'] = 'クライアントID値が不正です。';
				}
			}
		}

		if (!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/client/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_client		= new Client;
		$obj_project	= new Project;

		$client_by_id = $obj_client->getClientById($this->_id);
		if (!empty($client_by_id))
		{
			// プロジェクトマスタに使用されているかどうか（削除済は除く）
			$project_data = $obj_project->getDataByClientId($this->_id);
			if(!empty($project_data))
			{
				//使用されている
				$this->error[] = 'プロジェクトマスタで使用されているので削除できません。';
			}
		}
		else
		{
			// マスター存在無し
			MCWEB_Util::redirectAction('/client/index');
		}

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('client_by_id', $client_by_id);
	}
}
?>