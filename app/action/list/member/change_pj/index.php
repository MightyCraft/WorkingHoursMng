<?php
/**
 * 年月/PJコード指定のPJコード一括変換－選択画面
 *
 * ログインしているユーザの情報のみ修正可としています
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Manhour.php');
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _list_member_change_pj_index extends PostAndGetScene
{
	// パラメータ
	var $_date_Year;		// 変更対象年月
	var $_date_Month;
	var $_befor_project_id;	// 後発の場合は終了した実際の案件の方のコード

	// 画面表示用
	var $member_data;		// 社員情報
	var $project_list;		// プロジェクトリスト(通常・仮のみ、削除除く)
	var $befor_project_data;// 変更前プロジェクト情報
	var $monhour_list;		// 工数リスト
	var $calendar;			// 変更対象年月の日付一覧

	// エラー戻り用
	var $_errors;				// エラー内容
	var $_after_project_id;		// 変更後プロジェクトID
	var $_change_day=array();	// 変更対象日付

	function check()
	{
		// エラーチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'befor_project_id',	ValidatorString::createInstance()
			, 'date_Year',	ValidatorString::createInstance()->min(1)->max(4)
			, 'date_Month',	ValidatorString::createInstance()->min(1)->max(2)
		);

		// プロジェクトIDチェック
		if (!preg_match("/^[0-9]+$/",$this->_befor_project_id))
		{
			$errors["change_id"] = 'error';
		}
		// 修正年月の正当性チェック
		if (!checkdate($this->_date_Month,1,$this->_date_Year))
		{
			$errors["change_date"] = 'error';
		}

		// 値が不正な場合はエラー
		if(!empty($errors))
		{
			MCWEB_Util::redirectAction('/list/member');
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_monhour	= new Manhour();
		$obj_project	= new Project();
		$obj_member		= new Member();
		$obj_client		= new Client();

		// カレンダー情報取得
		$this->calendar	= getWeekendsHolidays($this->_date_Year, $this->_date_Month);
		// 社員情報取得（削除以外）
		$member_id = $_SESSION["member_id"];
		$this->member_data = $obj_member->getMemberById($member_id);
		// プロジェクト情報取得
		$this->project_list			= $obj_project->getDataByType(array(PROJECT_TYPE_NORMAL,PROJECT_TYPE_INFORMAL));	// プロジェクトリスト取得
		$this->befor_project_data	= $obj_project->getDataById($this->_befor_project_id,true);							// 変更前プロジェクト情報
		$this->befor_project_data['client_data'] = $obj_client->getClientById($this->befor_project_data['client_id'],true);

		// 社員情報、プロジェクト情報が存在しなければエラー
		if (empty($this->member_data) || empty($this->project_list) || empty($this->befor_project_data))
		{
			MCWEB_Util::redirectAction('/list/member');
		}

		// 工数情報取得
		$tmp_monhour_list = $obj_monhour->getDataByProjectIdAndMemberIdYearMonth($this->_befor_project_id,$member_id,$this->_date_Year,$this->_date_Month);
		if (!empty($tmp_monhour_list))
		{
			// 終了案件情報追加
			$end_date = $this->befor_project_data['end_date'];
			$project_end_date	= '';
			if (!empty($end_date))
			{
				$project_end_date	= strtotime($end_date);
			}

			$this->monhour_list = array();
			foreach ($tmp_monhour_list as $key => $date)
			{
				$now_date = mktime(0,0,0,$date['work_month'],$date['work_day'],$date['work_year']);

				$this->monhour_list["{$date["work_day"]}"] 			= $date;
				$this->monhour_list["{$date["work_day"]}"]['end_flg']	= false;
				if (!empty($project_end_date) && ($now_date > $project_end_date))
				{
					$this->monhour_list["{$date["work_day"]}"]['end_flg'] = true;		// 現時点のマスタ情報で終了案件扱いになっていた
				}
			}
		}
	}
}

?>