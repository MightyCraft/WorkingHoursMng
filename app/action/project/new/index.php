<?php
/**
 *	プロジェクト　新規作成
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/MemberCost.php');
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
class _project_new_index extends PostAndGetScene
{
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	// パラメータ
	var	$_project_code;					// プロジェクトコード
	var	$_name;							// プロジェクト名
	var	$_client_id;					// クライアントID
	var	$_project_type;					// プロジェクトタイプ
	var	$_budget_type;					// 予算タイプ
	var	$_total_budget;					// 総予算
	var	$_exclusion_budget=0;			// コスト管理外予算
	var	$_cost_rate=USER_PROJECT_COST_RATE_BASE;		// 原価率
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
	var	$_total_cost_manhour=NULL;	// 総割当コスト工数
	var $_use_cost_manhour=NULL;	// 使用済コスト工数
	var $_cost_budget=NULL;			// コスト予算

	// エラーメッセージ
	var	$_error;

	// 画面表示用
	var $guide_message_project_code;	// プロジェクトコードの入力制限説明文
	var $project_type_name_informal;	// プロジェクトコードの説明文
	var $budget_type_name_contents;		// 予算タイプの説明文
	var $project_type_list;				// 以下プルダウン用
	var $client_list;					//
	var $budget_type_list;				//
	var $member_cost_list;				//
	var $member_list;					//
	var $start_year;					//
	var $end_year;						//

	function check()
	{
		// 開発開始日付、開発終了日付、案件終了月が未セットの時の初期値セット
		if(empty($this->_project_start_date_year))
		{
			$this->_project_start_date_year	= date('Y');
		}
		if(empty($this->_project_start_date_month))
		{
			$this->_project_start_date_month	= date('m');
		}
		if(empty($this->_project_start_date_day))
		{
			$this->_project_start_date_day	= date('d');
		}
		if(empty($this->_project_end_date_year))
		{
			$this->_project_end_date_year	= date('Y');
		}
		if(empty($this->_project_end_date_month))
		{
			$this->_project_end_date_month	= date('m');
		}
		if(empty($this->_project_end_date_day))
		{
			$this->_project_end_date_day	= date('d');
		}
		if(empty($this->_end_date_year))
		{
			$this->_end_date_year	= date('Y');
		}
		if(empty($this->_end_date_month))
		{
			$this->_end_date_month	= date('m');
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_client			= new Client;
		$obj_member_cost	= new MemberCost;
		$obj_maintenance	= new MasterMaintenance;
		$mm = MessageManager::getInstance();

		//クライアントリスト取得
		$this->client_list = $obj_client->getDataAll();

		//PJコードタイプリスト
		$this->project_type_list = returnArrayPJtype();
		$this->project_type_name_informal = $mm->sprintfMessage(MessageDefine::PROJECT_TYPE_NAME_INFORMAL);

		// 予算タイプリスト関連
		$this->budget_type_list = returnArrayBudgetType();
		$this->budget_type_name_contents = $mm->sprintfMessage(MessageDefine::USER_BUDGET_TYPE_NAME_CONTENTS);

		// 社員コストリスト取得
		$this->member_cost_list = $obj_member_cost->getDataAll();

		// アカウントリスト取得(営業のみ)
		$this->member_list = $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES);

		// 日付項目の年度最小・最大を取得
		$this->start_year	= getSelectYearRangeStart();
		$this->end_year		= getSelectYearRangeEnd();

		// プロジェクトコードの入力制限説明文言取得
		$mm = MessageManager::getInstance();
		$this->guide_message_project_code = $mm->sprintfMessage(MessageDefine::USER_GUIDE_MESSAGE_PROJECT_CODE_FORMAT);
	}
}

?>