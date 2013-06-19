<?php
/**
 * 休日管理マスタ　ＤＢクラス
 *
 */
class Holiday
{
	var $instance_db;
	var $table="mst_holiday";

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();
	}

	/**
	 * 休日管理マスタの指定した年月日のデータを取得
	 *
	 * @param	string	$year	年（未指定は当年）
	 * 			string	$month	月
	 * 			string	$day
	 * @return	array	休日データ
	 */
	function getData($year=NULL,$month=NULL,$day=NULL)
	{
		if (empty($year))
		{
			$year = date(Y);

		}
		$sql  = 'SELECT holiday_year,holiday_month,holiday_day ';
		$sql .= '  FROM '.$this->table;
		$sql .= ' WHERE holiday_year = '.(int)$year;
		if (!empty($month))
		{
			$sql .= ' AND holiday_month = '.(int)$month;
		}
		if (!empty($day))
		{
			$sql .= ' AND holiday_day = '.(int)$day;
		}
		$sql .= ' ORDER BY holiday_year,holiday_month,holiday_day ASC';


		$result	= $this->instance_db->select($sql,array());
		return $result;
	}
	/**
	 * 休日登録
	 *
	 * @param	int		$year
	 * 			int		$month
	 * 			int		$day
	 * @return		$res
	 */
	function insertHoliday($year,$month,$day)
	{
		$column	= array(
			'holiday_year',
			'holiday_month',
			'holiday_day',
			'regist_date',
		);
		$data = array(
			(int)$year,
			(int)$month,
			(int)$day,
			date("Y-m-d H:i:s"),
		);

		$sql="INSERT INTO {$this->table} (".implode(',', $column).') VALUE (?,?,?,?)';
		$result		= $this->instance_db->insert($sql,$data);
		return $result;
	}

	/**
	 * 休日解除
	 *
	 */
	function deleteHoliday($year,$month,$day)
	{
		$sql="DELETE FROM {$this->table} WHERE holiday_year = ? AND holiday_month = ? AND holiday_day = ? ";

		$result = $this->instance_db->delete($sql,array((int)$year,(int)$month,(int)$day));
		return $result;
	}
}

?>