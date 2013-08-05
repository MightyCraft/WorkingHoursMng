<?php
/**
 * プロジェクト別作業時間集計情報画面
 *
 */
require_once(DIR_APP . '/class/common/ManhourList.php');
require_once(DIR_APP . '/class/common/PagePager.php');
class _list_project_manhour_index extends PostAndGetScene
{
	// パラメータ
	var $_date_Year;
	var $_date_Month;
	var $_column;
	var $_order;
	var $_page;
	var $_searched_Year;
	var $_searched_Month;
	var $_error_msg;

	// 画面用
	var $set_time;

	// ページ当たりの表示件数
	const PER_PAGE = 30;

	function check()
	{
		// 指定年月の正当性チェック
		if (!checkdate($this->_date_Month,1,$this->_date_Year))
		{
			$this->_date_Year	= date('Y');
			$this->_date_Month	= date('m');
		}

		// ソート未指定時は総作業時間の降順
		// ソート順の正当性チェック
		if (!($this->_order == 'ASC' || $this->_order == 'DESC'))
		{
			$this->_order = 'DESC';
		}
		// ソート対象カラムの正当性チェック
		if(!preg_match('/^(project_id|name|total|over)$/', $this->_column))
		{
			$this->_column	= 'total';
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 年月プルダウンの表示設定
		$this->set_time =  $this->_date_Year.'-'.$this->_date_Month.'-01';

		// プロジェクト毎の作業時間の月合計を取得
		$manhour_list_obj = new ManhourList();
		$this->manhour_list = $manhour_list_obj->monthManHourTotalProject($this->_date_Year, $this->_date_Month);

		// ページ指定が無い場合は先頭ページ参照
		if (!$this->_page)
		{
			$this->_page = 1;
		}

		// 指定行でソート
		$this->manhour_list = usortArray($this->manhour_list, $this->_column, $this->_order);

		// 件数取得
		$total = count($this->manhour_list);

		// パラメータ
		$extra_vars = array(
				'column' => $this->_column,
				'order' => $this->_order,
				'date_Year' => $this->_date_Year,
				'date_Month' => $this->_date_Month
		);

		// ページャ作成
		$pager	= PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $total, '/list/project/manhour', $extra_vars);

		// カレントページデータを取得
		$start_index =  self::PER_PAGE * ($this->_page - 1);
		$end_index =  (self::PER_PAGE * $this->_page);
		$this->manhour_list = array_slice($this->manhour_list, $start_index, $end_index);

		$access->htmltag('pager',	$pager->getLinks());
		$access->text('total',		$total);
		$access->text('last_page',	$pager->numPages());
		$access->text('now_page',	$this->_page);
		$access->text('manhour_list',	$this->manhour_list);
		$this->searched_Year = $this->_date_Year;
		$this->searched_Month = $this->_date_Month;
		$this->add_parameter = "&date_Year=".$this->_date_Year."&date_Month=".$this->_date_Month;

	}
}
?>