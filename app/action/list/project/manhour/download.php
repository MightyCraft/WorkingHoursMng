<?php
/**
 * プロジェクト別作業時間集計情報ダウンロード処理
 *
 *
 */
require_once(DIR_APP . '/class/common/ManhourList.php');
require_once(DIR_APP . '/class/common/FileArchive.php');		// ファイルのダウンロード用
class _list_project_manhour_download extends PostScene
{
	// パラメータ
	var $_searched_Year;
	var $_searched_Month;
	var $_sort_Column;
	var $_sort_Order;

	function check()
	{
		// 指定年月の正当性チェック
		if (!checkdate($this->_searched_Month,1,$this->_searched_Year))
		{
			MCWEB_Util::redirectAction('/list/project/manhour/index');
		}

		// ソート未指定時は総作業時間の降順
		// ソート順の正当性チェック
		if (!($this->_sort_Order == 'ASC' || $this->_sort_Order == 'DESC'))
		{
			$this->_sort_Order = 'DESC';
		}
		// ソート対象カラムの正当性チェック
		if(!preg_match('/^(project_id|name|total|over)$/', $this->_sort_Column))
		{
			$this->_sort_Column	= 'total';
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// プロジェクト毎の作業時間の月合計を取得
		$manhour_list_obj = new ManhourList();
		$tmp_manhour_list = $manhour_list_obj->monthManHourTotalProject($this->_searched_Year, $this->_searched_Month);

		// 指定行でソート
		$tmp_manhour_list = usortArray($tmp_manhour_list, $this->_sort_Column, $this->_sort_Order);

		// タイトル行セット
		$tmp_title[0] = array('id','プロジェクト名','総作業時間','内残業時間');
		$tmp_download_data = array_merge($tmp_title,$tmp_manhour_list);

		// 集計結果をcsv形式に変更
		$dounload_data = implodeTwoDimensionsArray($tmp_download_data);

		// ファイル名
		$file_name = 'プロジェクト別作業時間_'."{$this->_searched_Year}年{$this->_searched_Month}月";
		// ダウンロード実行
		$dl_flg = FileArchive::downloadDataToFile(convertUtf8ToSjiswin($dounload_data), $file_name.'.csv');
		if ($dl_flg)
		{
			// ダウンロード処理が行えた場合はexit
			exit;
		}
		else
		{
			MCWEB_Util::redirectAction('/list/project/manhour/index?error_msg=download_error');
		}
	}
}

?>