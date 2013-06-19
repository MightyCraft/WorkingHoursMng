<?php
/**
 *	プロジェクト　削除　確認
 */
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . "/class/common/dbaccess/Manhour.php");
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
class _project_delete_confirm extends PostAndGetScene
{
	var	$_id;//プロジェクトid

	function check()
	{
		if(empty($this->_id))
		{
			MCWEB_Util::redirectAction("/project/index");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_project		= new Project;
		$obj_client			= new Client;
		$obj_maintenance	= new MasterMaintenance;
		$obj_projectteam	= new ProjectTeam;
		$obj_manhour		= new Manhour;

		//所属データに使用されているかどうか
		$pt_project_id = $obj_projectteam->getDataByProjectId($this->_id);
		if(!empty($pt_project_id))
		{
			//使用されている
			$access->text('pt_error','所属プロジェクトデータに使用されているので削除出来ません。');
		}

		//工数データに使用されているかどうか
		$mh_project_id = $obj_manhour->getDataByProjectId($this->_id);
		if(!empty($mh_project_id))
		{
			//使用されている
			$access->text('mh_error','工数データに使用されているので削除出来ません。');
		}

		//指定プロジェクトデータ取得
		$data	= $obj_project->getDataById($this->_id);

		if(!empty($data))
		{
			$access->text('_project_code',			$data['project_code']);
			$access->text('_name',					$data['name']);
			$access->text('_client_id',				$data['client_id']);
			$access->text('_project_type',			$data['project_type']);
			$access->text('_total_budget',			$data['total_budget']);
			$access->text('_total_budget_manhour',	$data['total_budget_manhour']);

			if(!empty($data['project_start_date']))
			{
				$project_start_date_array	= explode('-', $data['project_start_date']);
				$access->text('_project_start_date_year',	$project_start_date_array[0]);
				$access->text('_project_start_date_month',	$project_start_date_array[1]);
				$access->text('_project_start_date_day',	$project_start_date_array[2]);
				$access->text('_project_pending_start_date',0);
			}
			else
			{
				$access->text('_project_pending_start_date',1);
			}

			if(!empty($data['project_end_date']))
			{
				$project_end_date_array		= explode('-', $data['project_end_date']);
				$access->text('_project_end_date_year',		$project_end_date_array[0]);
				$access->text('_project_end_date_month',	$project_end_date_array[1]);
				$access->text('_project_end_date_day',		$project_end_date_array[2]);
				$access->text('_project_pending_end_date',	0);
			}
			else
			{
				$access->text('_project_pending_end_date',	1);
			}

			if(!empty($data['end_date']))
			{
				$end_date_array		= explode('-', $data['end_date']);
				$access->text('_end_date_year',		$end_date_array[0]);
				$access->text('_end_date_month',	$end_date_array[1]);
				$access->text('_end_date_day',		$end_date_array[2]);
				$access->text('_pending_end_date',	0);
			}
			else
			{
				$access->text('_pending_end_date',	1);
			}

			$access->text('_nouki',		$data['nouki']);
			$access->text('_memo_flg',		$data['memo_flg']);
			$access->text('_member_id',	$data['member_id']);
			$access->text('_memo',		$data['memo']);
		}
		else
		{
			MCWEB_Util::redirectAction("/project/index");
		}

		//クライアントリスト取得
		$access->text('client_data', $obj_client->getClientById($data['client_id']));

		//PJコードタイプリスト
		$array_project_type = returnArrayPJtype();
		$access->text('array_project_type', $array_project_type);

		// アカウントリスト取得(営業のみ)
		$access->text('member_list', $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES));

	}
}

?>