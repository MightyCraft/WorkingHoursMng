<?php
/**
 *	プロジェクト　新規作成
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
class _project_new_index extends PostScene
{
	// パラメータ
	var	$_project_code;					// プロジェクトコード
	var	$_name;							// プロジェクト名
	var	$_client_id;					// クライアントID
	var	$_project_type;					// プロジェクトタイプ
	var	$_total_budget;					// 総予算
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
	var	$_total_budget_manhour;	// 総割当工数
	var	$_end_date_day;			// 案件終了月の末日

	// エラーメッセージ
	var	$_error;

	var $guide_message_project_code;	// プロジェクトコードの入力制限説明


	function check()
	{
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
		$obj_maintenance	= new MasterMaintenance;

		//クライアントリスト取得
		$access->text('client_list', $obj_client->getDataAll());

		//PJコードタイプリスト
		$array_project_type = returnArrayPJtype();
		$access->text('array_project_type', $array_project_type);

		// アカウントリスト取得(営業のみ)
		$access->text('member_list', $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES));

		// 日付項目の年度最小・最大を取得
		$this->start_year	= getSelectYearRangeStart();
		$this->end_year		= getSelectYearRangeEnd();

		// プロジェクトコードの入力制限説明文言取得
		$mm = MessageManager::getInstance();
		$this->guide_message_project_code = $mm->sprintfMessage(MessageDefine::USER_GUIDE_MESSAGE_PROJECT_CODE_FORMAT);
	}
}

?>