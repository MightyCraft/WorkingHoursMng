<?php
/**
 * 社員別の残業時間リスト
 *
 * 1日8時間以上の工数がついている分を残業時間は超過時間としてカウント
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Manhour.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
require_once(DIR_APP . '/class/common/FileArchive.php');		// ファイルのダウンロード用
require_once(DIR_APP . '/class/common/ManhourList.php');
class _list_member_manhour_download extends PostScene
{
	// パラメータ
	var $_searched_Year;
	var $_searched_Month;
	var $_column;
	var $_order;
	
	function check()
	{
		// 指定年月の正当性チェック
		if (!checkdate($this->_searched_Month,1,$this->_searched_Year))
		{
			MCWEB_Util::redirectAction('/list/member/manhour/index');
		}
		// ソート順の正当性チェック
		if (!($this->_order == 'ASC' || $this->_order = 'DESC'))
		{
			$this->_order = 'ASC';
		}
		// ソート対象カラムの正当性チェック
		if(!preg_match('/^(id|name|total|over)$/', $this->_column))
		{
			$this->_column	= null;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$manhour_list_obj = new ManhourList();
		
		// 社員毎月工数、残業工数取得
		$tmp_download_data = $manhour_list_obj->monthManHourTotal($this->_searched_Year, $this->_searched_Month);

		// 指定行でソート
		$header = array_shift($tmp_download_data);
		$tmp_download_data = usortArray($tmp_download_data, $this->_column, $this->_order);
		array_unshift($tmp_download_data, $header);
		
		// 集計結果をcsv形式に変更
		$dounload_data = implodeTwoDimensionsArray($tmp_download_data);

		// ファイル名
		$file_name = '社員別工数集計_'."{$this->_searched_Year}年{$this->_searched_Month}月";
		// ダウンロード実行
		$dl_flg = FileArchive::downloadDataToFile(convertUtf8ToSjiswin($dounload_data), $file_name.'.csv');
		if ($dl_flg)
		{
			// ダウンロード処理が行えた場合はexit
			exit;
		}
		MCWEB_Util::redirectAction('/list/member/manhour/index?error_msg=download_error');
	}
}

?>