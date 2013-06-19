<?php
/**
 * 休日管理　休日設定処理
 *
 */

//クラス定義
require_once(DIR_APP . '/class/common/dbaccess/Holiday.php');

class _holiday_do extends GetScene
{
	/**
	 * パラメータ取得
	 */
	var $_date_Year;	// 設定年
	var $_date_Month;	// 設定月
	var $_date_Day;		// 設定日
	var $_flg_holiday;	// 設定フラグ(0：平日にする､1：休日にする)

	function check()
	{
		$flg_error = false;
		if (!checkdate($this->_date_Month,$this->_date_Day,$this->_date_Year))
		{
			// 日付エラー
			$flg_error = true;
		}
		else
		{
			$obj_holiday = new Holiday;
			$arr_date_data = $obj_holiday->getData($this->_date_Year,$this->_date_Month,$this->_date_Day);
			if ((empty($arr_date_data)) && ((int)$this->_flg_holiday === 0))
			{
				// データ無しAND平日に変更時
				$flg_error = true;
			}
			if ((!empty($arr_date_data)) && ((int)$this->_flg_holiday === 1))
			{
				// データ有りAND休日に変更時
				$flg_error = true;
			}
		}

		if ($flg_error)
		{
			// 設定画面にリダイレクト
			MCWEB_Util::redirectAction('/holiday/index/');
			exit;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 休日設定変更
		$obj_holiday = new Holiday;

		if ((int)$this->_flg_holiday === 0)
		{
			// 平日に変更
			$result = $obj_holiday->deleteHoliday($this->_date_Year,$this->_date_Month,$this->_date_Day);
		}
		elseif ((int)$this->_flg_holiday === 1)
		{
			// 休日に変更
			$result = $obj_holiday->insertHoliday($this->_date_Year,$this->_date_Month,$this->_date_Day);
		}

		// 設定画面にリダイレクト
		MCWEB_Util::redirectAction('/holiday/index?date_Year='.$this->_date_Year.'&date_Month='.$this->_date_Month);
		exit;
	}

}
?>