<?php
/**
 *	プロジェクト　削除　確認
 */
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . "/class/common/dbaccess/Manhour.php");
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/MemberCost.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _project_delete_confirm extends PostAndGetScene
{
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	var	$_id;							//プロジェクトID

	var	$_project_code;					// プロジェクトコード
	var	$_name;							// プロジェクト名
	var	$_client_id;					// クライアントID
	var	$_project_type;					// プロジェクトタイプ
	var	$_budget_type;					// 予算タイプ
	var	$_total_budget;					// 総予算
	var	$_exclusion_budget;				// コスト管理外予算
	var	$_cost_rate;					// 原価率
	var	$_mst_member_cost_id;			// 基準社員コストID
	var	$_project_start_date_year;		// 開発開始日付
	var	$_project_start_date_month;		//
	var	$_project_start_date_day;		//
	var	$_project_pending_start_date;	//
	var	$_project_end_date_year;		// 開発終了日付
	var	$_project_end_date_month;		//
	var	$_project_end_date_day;			//
	var	$_project_pending_end_date;		//
	var	$_end_date_year;				// 終了案件切り替え日
	var	$_end_date_month;				//
	var	$_pending_end_date;				//
	var $_nouki;						// 納期
	var $_memo_flg;						// 工数入力時備考必須フラグ
	var $_member_id;					// 担当営業
	var $_memo;							// 備考

	// 自動セット項目
	var	$_total_cost_manhour;	// 総割当工数
	var $_use_cost_manhour;		// 使用済コスト工数
	var $_cost_budget;			// コスト予算
	var	$_end_date_day;			// 案件終了月の末日

	// 画面表示用
	var $project_type_list;
	var $client_data;
	var $member_data;
	var $budget_type_list;
	var $member_cost_data;

	function check()
	{
		// IDチェック
		$error_id = MCWEB_ValidationManager::validate(
			$this
			, 'id',			ValidatorInt::createInstance()->min(1)
		);
		if (!empty($error_id))
		{
			MCWEB_Util::redirectAction("/project/index");
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_project		= new Project;
		$obj_client			= new Client;
		$obj_member			= new Member;
		$obj_member_cost	= new MemberCost;
		$obj_projectteam	= new ProjectTeam;
		$obj_manhour		= new Manhour;

		// プロジェクトマスタに存在するか
		$data	= $obj_project->getDataById($this->_id);
		if (empty($data))
		{
			MCWEB_Util::redirectAction("/project/index");
		}

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

		// 画面にマスタの値を表示
		$this->_project_code			= $data['project_code'];
		$this->_name					= $data['name'];
		$this->_client_id				= $data['client_id'];
		$this->_project_type			= $data['project_type'];
		$this->_budget_type				= $data['budget_type'];
		$this->_total_budget			= $data['total_budget'];
		$this->_exclusion_budget		= $data['exclusion_budget'];
		$this->_cost_rate				= $data['cost_rate'];
		$this->_mst_member_cost_id		= $data['mst_member_cost_id'];

		if(!empty($data['project_start_date']))
		{
			$project_start_date_array	= explode('-', $data['project_start_date']);
			$this->_project_start_date_year		= $project_start_date_array[0];
			$this->_project_start_date_month	= $project_start_date_array[1];
			$this->_project_start_date_day		= $project_start_date_array[2];
			$this->_project_pending_start_date	= 0;
		}
		else
		{
			$this->_project_pending_start_date = 1;
		}

		if(!empty($data['project_end_date']))
		{
			$project_end_date_array		= explode('-', $data['project_end_date']);
			$this->_project_end_date_year		= $project_end_date_array[0];
			$this->_project_end_date_month		= $project_end_date_array[1];
			$this->_project_end_date_day		= $project_end_date_array[2];
			$this->_project_pending_end_date	= 0;
		}
		else
		{
			$this->_project_pending_end_date = 1;
		}

		if(!empty($data['end_date']))
		{
			$end_date_array		= explode('-', $data['end_date']);
			$this->_end_date_year		= $end_date_array[0];
			$this->_end_date_month		= $end_date_array[1];
			$this->_end_date_day		= $end_date_array[2];
			$this->_pending_end_date	= 0;
		}
		else
		{
			$this->_pending_end_date = 1;
		}

		$this->_nouki		= $data['nouki'];
		$this->_memo_flg	= $data['memo_flg'];
		$this->_member_id	= $data['member_id'];
		$this->_memo		= $data['memo'];

		$this->_total_cost_manhour	= $data['total_cost_manhour'];
		$this->_use_cost_manhour	= $data['use_cost_manhour'];
		$this->_cost_budget			= $data['cost_budget'];


		//クライアントリスト取得
		$this->client_data = $obj_client->getClientById($data['client_id']);

		//PJコードタイプリスト
		$this->project_type_list = returnArrayPJtype();

		// 予算タイプ
		$this->budget_type_list = returnArrayBudgetType();

		// 担当営業
		if ($data['member_id'])
		{
			$this->member_data = $obj_member->getMemberById($data['member_id'],true);
		}
		// 予算タイプ
		$this->member_cost_data = $obj_member_cost->getDataById($data['mst_member_cost_id']);

	}
}

?>