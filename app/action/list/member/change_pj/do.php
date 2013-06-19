<?php
/**
 * 年月/PJコード指定のPJコード一括変換－実行
 *
 * ログインしているユーザの情報のみ修正可としています
 *
 */
require_once(DIR_APP . '/class/common/ManhourList.php');
class _list_member_change_pj_do extends PostScene
{
	// パラメータ
	var $_befor_project_id;		// 変更前プロジェクトID
	var $_after_project_id;		// 変更後プロジェクトID
	var $_date_Year;			// 変更対象年月
	var $_date_Month;
	var $_change_day=array();	// 変更対象日付

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
			MCWEB_Util::redirectAction('/list/member');
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_monhour	= new Manhour();
		$obj_project	= new Project();
		$obj_member		= new Member();

		// 社員情報取得（削除以外）
		$member_id = $_SESSION["member_id"];
		$this->member_data = $obj_member->getMemberById($member_id);
		// プロジェクト/クライアント情報取得
		$this->befor_project = $obj_project->getDataById($this->_befor_project_id);
		$this->after_project = $obj_project->getDataById($this->_after_project_id);
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
				MCWEB_Util::redirectAction('/list/member');
			}
		}

		// 変更処理
		$obj_monhour->updateManhourProjectByMenberAndDay(
												$member_id,
												$this->_date_Year,
												$this->_date_Month,
												$this->_change_day,
												$this->_befor_project_id,
												$this->_after_project_id
		);

		// 修正した社員/月を表示した社員別プロジェクト照会に戻る
		MCWEB_Util::redirectAction("/list/member?member_id={$member_id}&date_Year={$this->_date_Year}&date_Month={$this->_date_Month}");
	}
}

?>