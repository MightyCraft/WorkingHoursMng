<?php
/**
 * 工数照会　プロジェクト毎画面
 *
 */

require_once(DIR_APP . '/class/common/ManhourList.php');
require_once(DIR_APP . '/class/common/dbaccess/Holiday.php');
class _list_project extends PostAndGetScene
{
	// パラメータ
	var $_date_Year;		// 選択年
	var $_date_Month;		// 選択月
	var $_project_id;				// プロジェクト選択の指定プロジェクトID
	var $_project_id_keyword;

	// 画面表示用
	var $set_time;		// プルダウン用選択月

	var $client_list;				// クライアントリスト
	var $project_list_by_client_id;	// 指定されたクライアントIDのプロジェクトリスト

	var $project_data;				// 表示するプロジェクト情報(終了案件判別情報付き)

	var $data;				// 指定年月、プロジェクトの工数データ
	var $total_data;		// 各工数集計データ
	var $weekly_data;		// 指定プロジェクトの週別の全工数データ
	var $weekends_holidays;	// カレンダー情報

	var $obj_client;	// クライアント
	var $obj_project;	// プロジェクト

	function check()
	{
		// エラーチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'project_id',				ValidatorUnsignedInt::createInstance()->min(0)
			, 'date_Year',				ValidatorString::createInstance()->min(1)->max(4)
			, 'date_Month',				ValidatorString::createInstance()->min(1)->max(2)
		);
		// 指定年月の正当性チェック
		if (!checkdate($this->_date_Month,1,$this->_date_Year))
		{
			$errors["date"] = 'error';
		}

		// 値が不正な場合は初期値を強制セット
		if(!empty($errors))
		{
			// 検索用プロジェクトID
			if (!empty($errors['project_id']))
			{
				$this->_project_id	= 0;
			}
			// 表示年月
			if (!empty($errors['date_Year']) || !empty($errors['date']))
			{
				$this->_date_Year	= date('Y');
			}
			if (!empty($errors['date_Month']) || !empty($errors['date']))
			{
				$this->_date_Month	= date('m');
			}
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// オブジェクト作成
		$this->obj_project	= new Project();

		// プロジェクトID指定時でプロジェクトマスタに存在しない(削除フラグONorデータ削除)時は未指定にします
		if (!empty($this->_project_id))
		{
			$this->project_data = $this->obj_project->getDataById($this->_project_id);
			if (empty($this->project_data))
			{
				$this->_project_id	= 0;
			}
		}

		// 検索用プルダウンリスト生成
		$this->project_list_by_client_id = $this->obj_project->getDataAllAddClient();

		// 表示年月設定　TODO: ブラッシュアップ
		$this->set_time		= $this->_date_Year . '-' . $this->_date_Month . '-01';

		// プロジェクトIDが指定されている時のみ照会情報を取得（クライアント変更時は自動的に初期時"0"で遷移してきます）
		$this->data				= array();
		$this->total_data		= array();
		$this->weekly_data	= array();
		if(!empty($this->_project_id))
		{
			$obj_common_list	= new ManhourList();
			// 指定月の社員/日別一覧、指定月以前合計、総合計の取得（$this->all_daily_totalは参照変数）
			$this->data			= $obj_common_list->getProjectListByProjectidDate($this->_date_Year,$this->_date_Month,$this->project_data,$this->total_data);
			// 年月週単位の工数リスト取得
			$this->weekly_data	= $obj_common_list->getTotalWeeklyManhour();
		}

		// 指定年月のカレンダー情報取得（土日祝日埋め込み済）
		$this->weekends_holidays	= getWeekendsHolidays($this->_date_Year, $this->_date_Month);
		// 休日マスタに設定されている休日をカレンダーに反映
		$obj_holiday = new Holiday;
		$arr_mst_holiday = $obj_holiday->getData($this->_date_Year,$this->_date_Month);
		if (!empty($arr_mst_holiday))
		{
			foreach ($arr_mst_holiday as $value)
			{
				if ((int)($value['holiday_year'])  == $this->_date_Year &&
					(int)($value['holiday_month']) == $this->_date_Month)
				{
					// 基本休日に対して、マスタ設定休日を反映　※3:固定祝日として登録
					$this->weekends_holidays[$value['holiday_day']] = 3;
				}
			}
		}

		// プロジェクトID
		$this->project_id = $this->_project_id;
		$this->project_id_keyword = $this->_project_id_keyword;
	}
}

?>