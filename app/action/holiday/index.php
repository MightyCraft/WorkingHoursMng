<?php
/**
 * 休日管理　休日設定画面
 *
 */

//クラス定義
require_once(DIR_APP . '/class/common/dbaccess/Holiday.php');

class _holiday_index extends PostAndGetScene
{
	/**
	 * パラメータ取得
	 */
	var $_date_Year;	//選択年
	var $_date_Month;	//選択月


	function check()
	{
		if( $this->_date_Year == '' )
		{
			$this->_date_Year	= date('Y');
		}
		if( $this->_date_Month == '' )
		{
			$this->_date_Month	= date('m');
		}

		// 日付エラー時は当月を強制セット
		if (!checkdate($this->_date_Month,'01',$this->_date_Year))
		{
			$this->_date_Year	= date('Y');
			$this->_date_Month	= date('m');
		}


	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 表示日付一覧取得
		$arr_view_date	= getMonthCalendar($this->_date_Year,$this->_date_Month);

		// 基本休日取得
		$arr_default_holiday = getWeekendsHolidays($this->_date_Year,$this->_date_Month);
		// マスタ設定休日取得
		$obj_holiday = new Holiday;
		$arr_mst_holiday = $obj_holiday->getData($this->_date_Year,$this->_date_Month);
		if (!empty($arr_mst_holiday))
		{
			foreach ($arr_mst_holiday as $value)
			{
				if ((int)($value['holiday_year'])  == $this->_date_Year &&
					(int)($value['holiday_month']) == $this->_date_Month)
				{
					// 基本休日に対して、マスタ設定休日を反映する
					// 0:平日(変更可)､1:土曜日(変更不可)､2:日曜日(変更不可)､3:固定祝日(変更不可)､4:マスタ設定の休日(変更可)
					$arr_default_holiday[$value['holiday_day']] = 4;		// 4:マスタ設定の休日
				}
			}
		}

		// 画面セット項目
		$access->text('set_month',		$this->_date_Year.'-'.$this->_date_Month.'-01');	// 表示年月
		$access->text('arr_view_date',	$arr_view_date);									// 表示日付
		$access->text('arr_holiday', 	$arr_default_holiday);								// 休日設定

	}

}
?>