<?php
/**
 *	プロジェクト　編集
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
class _project_edit_index extends PostAndGetScene
{
	// パラメータ
	var	$_id;
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
		//指定管理IDが不正であれば、一覧へ
		if(empty($this->_id))
		{
			MCWEB_Util::redirectAction("/project/index");
		}
		//全て空だったらIDでマスタ情報取り直す
		if(	empty($this->_project_code)			&&
			empty($this->_name)					&&
			empty($this->_client_id)			&&
			empty($this->_project_type)			&&
			empty($this->_total_budget_manhour)	&&
			empty($this->_total_budget)			&&
			empty($this->_project_start_date_year)		&&
			empty($this->_project_start_date_month)		&&
			empty($this->_project_start_date_day)		&&
			empty($this->_project_pending_start_date)	&&
			empty($this->_project_end_date_year)		&&
			empty($this->_project_end_date_month)		&&
			empty($this->_project_end_date_day)			&&
			empty($this->_project_pending_end_date)		&&
			empty($this->_end_date_year)		&&
			empty($this->_end_date_month)		&&
			empty($this->_pending_end_date)		&&
			empty($this->_nouki)		&&
			empty($this->_memo_flg)		&&
			empty($this->_member_id)	&&
			empty($this->_memo)
		 	)
		{
			//プロジェクトデータを取得
			$obj_project	= new Project;
			$project		= $obj_project->getDataById($this->_id);
			if(!empty($project))
			{
				$this->_project_code			= $project['project_code'];
				$this->_name					= $project['name'];
				$this->_client_id				= $project['client_id'];
				$this->_project_type			= $project['project_type'];
				$this->_total_budget_manhour	= $project['total_budget_manhour'];
				$this->_total_budget			= $project['total_budget'];
				if(!empty($project['project_start_date']))
				{
					$project_start_date_array			= explode('-', $project['project_start_date']);
					$this->_project_start_date_year		= $project_start_date_array[0];
					$this->_project_start_date_month	= $project_start_date_array[1];
					$this->_project_start_date_day		= $project_start_date_array[2];
				}
				else
				{
					$this->_project_pending_start_date	= 1;
				}
				if(!empty($project['project_end_date']))
				{
					$project_end_date_array			= explode('-', $project['project_end_date']);
					$this->_project_end_date_year	= $project_end_date_array[0];
					$this->_project_end_date_month	= $project_end_date_array[1];
					$this->_project_end_date_day	= $project_end_date_array[2];
				}
				else
				{
					$this->_project_pending_end_date	= 1;
				}
				if(!empty($project['end_date']))
				{
					$end_date_array			= explode('-', $project['end_date']);
					$this->_end_date_year	= $end_date_array[0];
					$this->_end_date_month	= $end_date_array[1];
				}
				else
				{
					$this->_pending_end_date	= 1;
				}
				$this->_nouki		= $project['nouki'];
				$this->_memo_flg	= $project['memo_flg'];
				$this->_member_id	= $project['member_id'];
				$this->_memo		= $project['memo'];
			}
			else
			{
				MCWEB_Util::redirectAction("/project/index");
			}
		}

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