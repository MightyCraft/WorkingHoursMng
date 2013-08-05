<?php
/**
 *	プロジェクト　編集　完了
 */
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/MemberCost.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _project_edit_complete extends PostAndGetScene
{
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	var	$_id;

	var $data;
	var $client_data;
	var $member_data;
	var $project_type_list;
	var $budget_type_list;
	var $member_cost_data;

	function check()
	{
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_project		= new Project;
		$obj_client			= new Client;
		$obj_member			= new Member;
		$obj_member_cost	= new MemberCost;

		//先程編集したレコードを表示
		if(!empty($this->_id))
		{
			$project	= $obj_project->getDataById($this->_id);
			if(!empty($project))
			{
				$this->data = $project;
			}
		}

		// クライアントデータ
		$this->client_data = $obj_client->getClientById($this->data['client_id']);

		// 営業担当データ
		$this->member_data = $obj_member->getMemberById($this->data['member_id'],true);

		// プロジェクトタイプリスト
		$this->project_type_list = returnArrayPJtype();

		// 予算タイプ
		$this->budget_type_list = returnArrayBudgetType();

		// 基準社員コストIDデータ
		$this->member_cost_data = $obj_member_cost->getDataById($this->data['mst_member_cost_id']);

	}
}

?>