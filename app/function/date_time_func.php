<?php
/**
 * 日付時間関連　関数
 *
 */

	/**
	 * 指定日の曜日を求める
	 *
	 * @param	int		$year
	 * 			int		$month
	 * 			int		$day
	 * @return string
	 */
	function getYoubi($year,$month,$day)
	{
		// 日本語曜日
		$youbi = array("日", "月", "火", "水", "木", "金", "土");
		$res = date("w", mktime( 0, 0, 0, (int)$month, (int)$day, (int)$year));

		return $youbi[$res];
	}

	/**
	 * 月末日付を求める
	 *
	 * @param	// 書式：YYYY-MM-DD
	 * @return
	 */
	function getMonthEnd($date=NULL)
	{
		if ($date === NULL)
		{
			// 未指定の時は当日を取得
			$date = date(Y-m-d);
		}
		$dates		= getdate(strtotime($date));
		$last_day	= date('d',mktime(0,0,0,$dates['mon']+1,0,$dates['year']));

		return $last_day;
	}

	/**
	 * 指定日された日付が有効かチェックして無効の場合は当日日付を返す
	 *
	 * @param	integer	$year
	 * @param	integer	$month
	 * @param	integer	$day
	 * @return	string	$now_date	Y-m-d
	 */
	function getCheckDate($year=null,$month=null,$day=null)
	{
		if ((empty($year) || empty($month) || empty($day)) ||
			!checkdate($month,$day,$year))
		{
			$now_date = date("Y-m-d");
		}
		else
		{
			$now_date = date('Y-m-d',mktime(0,0,0,$month,$day,$year));
		}
		return $now_date;
	}

	/**
	 * 月のカレンダーを取得
	 *
	 * @param	string	$year	年4桁
	 * 			string	$month	月2桁
	 * @return	array	指定された月の日付/曜日の配列
	 */
	function getMonthCalendar($year,$month)
	{
		// 1日の曜日を求める
		$youbi = date("w", mktime(0,0,0,$month, 1, $year));
		// 末日を求める
		$last_day = getMonthEnd($year.'-'.$month.'-01');

		// 指定月の日付一覧を取得する
		for ($i=1;$i<=$last_day;$i++)
		{
			// 日付
			$day_array['day'][$i]	= $i;

			// 曜日(0:日,1:月,2:火,3:水,4:木,5:金,6:土)
			$day_array['youbi'][$i]	= $youbi;
			$youbi = ($youbi >= 6 ? 0 : ($youbi + 1));
		}

		return $day_array;
	}

	/**
	 * 土日祝日の色判定配列の作成
	 *
	 * @param int	$year	指定西暦
	 * @param int	$month	指定月
	 * @return array(月の最終日付と同じ要素数)
	 *           1 = 土曜
	 *           2 = 日曜
	 *           3 = 祝日
	 */
	function getWeekendsHolidays($year, $month)
	{
		$week		= date("w", mktime(0,0,0,$month, 1, $year));
		$last_day	= date("d", mktime(0,0,0,$month+1, 0, $year));
		$str_date	= sprintf('%d-%d-%d', $year, $month, 0);

		// 固定休日の取得
		$holiday_array	= getStaticHolidays((int)$year, (int)$month);

		//祝日・土日を埋め込んだ配列を作成
		$ret	= array();
		for($i=1;$i<=$last_day;$i++)
		{
			//土日判定
			switch($week)
			{
				case	0:	$ret[$i]	= 2;	break;	//日曜
				case	6:	$ret[$i]	= 1;	break;	//土曜
				default:	$ret[$i]	= 0;	break;	//平日
			}
			//祝日判定
			$ret[$i]	= (isset($holiday_array[$i]) ? 3 : $ret[$i]);
			$week		= ($week >= 6 ? 0 : ($week + 1));
		}
		return $ret;
	}



	/**
	 * 固定休日の管理＆取得
	 *
	 * @param 	int	$year	指定西暦
	 * @param 	int	$month	指定月
	 * @return	array
	 */
	function getStaticHolidays($year, $month)
	{
		$holiday_array	= array();

		switch($month)
		{
			case 1:
				$holiday_array	= array(
					1	=>	'元旦',
					date('d', strtotime("{$year}-1-0 second Monday")) =>	'成人の日',
				);
				break;
			case 2:
				$holiday_array	= array(
					11	=>	'建国記念日',
				);
				break;
			case 3:
				$holiday_array	= array(0);
				break;
			case 4:
				$holiday_array	= array(
					29	=>	'昭和の日',
				);
				break;
			case 5:
				$holiday_array	= array(
					3	=>	'憲法記念日',
					4	=>	'みどりの日',
					5	=>	'こどもの日',
				);
				break;
			case 6:
				$holiday_array	= array(0);
				break;
			case 7:
				$holiday_array	= array(
					date('d', strtotime("{$year}-7-0 Third Monday")) =>	'海の日',
				);
				break;
			case 8:
				$holiday_array	= array(0);
				break;
			case 9:
				$holiday_array	= array(
					date('d', strtotime("{$year}-9-0 Third Monday")) =>	'敬老の日',
				);
				break;
			case 10:
				$holiday_array	= array(
					date('d', strtotime("{$year}-10-0 Second Monday")) =>	'体育の日',
				);
				break;
			case 11:
				$holiday_array	= array(
					3	=>	'文化の日',
					23	=>	'勤労感謝の日',
				);
				break;
			case 12:
				$holiday_array	= array(
					23	=>	'天皇誕生日',
				);
				break;
			default:
				break;
		}

		return $holiday_array;
	}

	/**
	 * 日付項目の選択可能年度の最早年度を返却します。
	 *
	 * @return number 最早年度
	 */
	function getSelectYearRangeStart()
	{
		$now_year	= date('Y');
		$start_year	= $now_year - 5;
		return $start_year;
	}

	/**
	 * 日付項目の選択可能年度の最遅年度を返却します。
	 *
	 * @return number 最遅年度
	 */
	function getSelectYearRangeEnd()
	{
		$now_year	= date('Y');
		$end_year	= $now_year + 5;
		return $end_year;
	}

?>