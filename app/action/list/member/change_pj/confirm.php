<?php
/**
 * 年月/PJコード指定のPJコード一括変換－確認画面
 *
 * ログインしているユーザの情報のみ修正可としています
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Manhour.php');
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
class _list_member_change_pj_confirm extends PostScene
{
	// パラメータ
	var $_after_project_id;		// 変更後プロジェクトID
	var $_change_day=array();	// 変更対象日付
	// 共通
	var $_date_Year;			// 変更対象年月
	var $_date_Month;
	var $_befor_project_id;		// 変更前プロジェクトID

	// 画面表示用
	var $befor_project;	// 変更前プロジェクト
	var $after_project;	// 変更後プロジェクト
	var $befor_client;	// 変更前クライアント
	var $after_client;	// 変更後クライアント
	// 共通
	var $member_data;	// 社員情報
	var $monhour_list;	// 工数リスト
	var $calendar;		// 変更対象年月の日付一覧

	// エラー戻り用
	var $_errors;			// エラー内容


	function check()
	{
		// エラーチェック
		$this->_errors = MCWEB_ValidationManager::validate(
			$this
			, 'after_project_id',	ValidatorUnsignedInt::createInstance()->min(1)
			, 'befor_project_id',	ValidatorUnsignedInt::createInstance()->min(1)
			, 'date_Year',			ValidatorString::createInstance()->min(1)->max(4)
			, 'date_Month',			ValidatorString::createInstance()->min(1)->max(2)
		);

		// 変更年月の正当性チェック
		if (!checkdate($this->_date_Month,1,$this->_date_Year))
		{
			$this->_errors["change_date"] = 'error';
		}
		// 変更日付のチェック
		if (empty($this->_change_day))
		{
			$this->_errors["change_day"] = 'null';
		}
		elseif (!is_array($this->_change_day))
		{
			$this->_errors["change_day"] = 'no array';
		}
		else
		{
			foreach ($this->_change_day as $day)
			{
				if (!checkdate($this->_date_Month, $day, $this->_date_Year))
				{
					$this->_errors["change_day"] = 'checkdate';
				}
			}
		}

		// 値が不正な場合はエラー
		if(!empty($this->_errors))
		{
			$f = new MCWEB_SceneForward('/list/member/change_pj/index');
			$f->regist('FORWARD', $this);
			return $f;
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
		// プロジェクト/クライアント情報取得
		$this->befor_project = $obj_project->getDataById($this->_befor_project_id);
		$this->befor_client	 = $obj_client->getClientById($this->befor_project['client_id']);
		$this->after_project = $obj_project->getDataById($this->_after_project_id);
		$this->after_client	 = $obj_client->getClientById($this->after_project['client_id']);

		// 社員情報、プロジェクト情報が存在しなければエラー
		if (empty($this->member_data) || empty($this->befor_project) || empty($this->after_project))
		{
			MCWEB_Util::redirectAction('/list/member');
		}
		// 変更後プロジェクトのプロジェクトタイプが後発や廃止の時はエラー
		if (($this->after_project['project_type'] == PROJECT_TYPE_REMOVAL) || ($this->after_project['project_type'] == PROJECT_TYPE_BACK))
		{
			MCWEB_Util::redirectAction('/list/member');
		}

		// 変更対象日付で既に変更後のPJコードが既に使用されているかのチェック
		$after_project_monhour_list = $obj_monhour->getDataByProjectIdAndMemberIdYearMonth($this->_after_project_id,$member_id,$this->_date_Year,$this->_date_Month);
		foreach ($after_project_monhour_list as $key => $date)
		{
			// 変更対象日付以外はチェック対象外
			if (!in_array($date['work_day'], $this->_change_day))
			{
				continue;
			}
			// 変更後プロジェクトに該当するデータが既に存在する場合はエラー
			if ($this->_after_project_id == $date['project_id'])
			{
				$this->_errors['after_project_id'][] = 'exists';
				$f = new MCWEB_SceneForward('/list/member/change_pj/index');
				$f->regist('FORWARD', $this);
				return $f;
			}
		}

		// 工数情報取得
		$tmp_monhour_list = $obj_monhour->getDataByProjectIdAndMemberIdYearMonth($this->_befor_project_id,$member_id,$this->_date_Year,$this->_date_Month);
		if (!empty($tmp_monhour_list))
		{

			// 終了案件情報追加
			$befor_end_date = $this->befor_project['end_date'];
			$befor_project_end_date	= '';
			if (!empty($befor_end_date))
			{
				$befor_project_end_date	= strtotime($befor_end_date);
			}
			$after_end_date = $this->after_project['end_date'];
			$after_project_end_date	= '';
			if (!empty($after_end_date))
			{
				$after_project_end_date	= strtotime($after_end_date);
			}

			$this->monhour_list = array();
			foreach ($tmp_monhour_list as $key => $date)
			{
				$this->monhour_list["{$date["work_day"]}"] 				= $date;	// 既存情報
				$this->monhour_list["{$date["work_day"]}"]['change_flg'] = false;	// 変更対象日フラグ追加

				$now_date = mktime(0,0,0,$date['work_month'],$date['work_day'],$date['work_year']);
				// 変更前終了案件判定
				$this->monhour_list["{$date["work_day"]}"]['befor_end_flg']	= false;	// 終了案件フラグ追加
				if (!empty($befor_project_end_date) && ($now_date > $befor_project_end_date))
				{
					$this->monhour_list["{$date["work_day"]}"]['befor_end_flg'] = true;
				}
				// 変更後終了案件判定
				$this->monhour_list["{$date["work_day"]}"]['after_end_flg']	= false;	// 終了案件フラグ追加
				if (!empty($after_project_end_date) && ($now_date > $after_project_end_date))
				{
					$this->monhour_list["{$date["work_day"]}"]['after_end_flg'] = true;
				}
			}

			// 変更対象日情報追加
			foreach ($this->_change_day as $day)
			{
				$this->monhour_list[$day]['change_flg'] = true;
			}
		}
	}
}

?>