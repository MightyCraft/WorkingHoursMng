<?php

	/**
	 * パスワードを*表示にする
	 *
	 * @param string password
	 * @return string
	 */
	function changePassWord($password)
	{
		$len = strlen($password);
		$password_tmp = '';
		for($i=0;$i<$len;$i++)
		{
			$password_tmp .= '*';
		}

		return $password_tmp;
	}

	/**
	 * パスワードを暗号化する
	 *
	 * @param string password
	 * @return string
	 */
	function hashPassWord($password)
	{
		return hash('sha256', $password);
	}

	/**
	 * 多次元配列を昇順キーソートする
	 */
	function mulksort(&$a)
	{
		ksort($a);
		foreach($a as &$value)
		if(is_array($value)) {
			mulksort($value);
		}
	}

	/**
	 * 多次元配列を降順キーソートする
	 */
	function mulkrsort(&$a)
	{
		ksort($a);
		foreach($a as &$value)
		if(is_array($value)) {
			mulkrsort($value);
		}
	}

	/**
	 * コスト予算
	 *
	 * @param	int	$total_buget		総予算
	 * @param	int	$exclusion_budget	コスト管理外予算
	 * @param	int	$cost_rate			原価率
	 * @return	int	小数は切捨て
	 */
	function calculateCostBudget($total_budget,$exclusion_budget,$cost_rate)
	{
		// ( 総予算 - コスト管理除外単価 ) * 原価率
 		$tmp_cost_rate = (int)$cost_rate / (int)COST_RATE_BREAK;
 		$cost_budget = (string)(((int)$total_budget * $tmp_cost_rate) - (int)$exclusion_budget);
		
 		return (int)$cost_budget;
	}
	/**
	 * 総割当コスト工数計算（時間）
	 *
	 * @param	int	$cost_budget
	 * @param	int	$member_cost
	 * @return	int	小数は切捨て
	 */
	function calculateTotalCostManhour($cost_budget, $member_cost)
	{
		// コスト予算 / 基準社員コスト
		$total_cost_manhour = (string)($cost_budget / $member_cost);

		return (int)$total_cost_manhour;
	}


	/**
	 * WHERE句カラムからWHEREクエリ文を展開します
	 *
	 * @param	array	$where_columns	WHERE句カラム カラム名=>値で一致比較、カラム名=>array(値1,値2...)でIN句
	 * @param	array	$params			DBアクセサに渡すパラメータ ※参照引数
	 *
	 * @return	string	where句
	 */
	function _makeWhereQuery($where_columns, &$params)
	{
		if(!is_array($where_columns))
		{
			throw new MCWEB_InternalServerErrorException('_makeWhereQuery error bad where_columns');
		}
		if(!is_array($params))
		{
			throw new MCWEB_InternalServerErrorException('_makeWhereQuery error bad params');
		}

		if(0 == count($where_columns))
		{
			// 条件なしのため全てが対象
			return "WHERE 1=1";
		}

		$where = array();
		foreach($where_columns as $key => $value)
		{
			if(!isset($value))
			{
				// カラム名に対して値がNULL
				throw new MCWEB_InternalServerErrorException("_makeWhereQuery error bad value : {$key} => NULL");
			}

			if(is_array($value))
			{
				if(0 < count($value))
				{
					$where_in = array();
					foreach($value as $v)
					{
						$where_in[] = '?';
						$params[] = $v;
					}
					$where[] = "`{$key}` IN (" . implode(', ', $where_in) . ")";
				}
				else
				{
					// 空配列
					$where[] = "1=0";
				}
			}
			else
			{
				$where[] = "`{$key}` = ?";
				$params[] = $value;
			}
		}
		$where = "WHERE " . implode(' AND ', $where);

		return $where;
	}

	/**
	 * update文のSETを生成します
	 *
	 * @param	array	$set_columns	更新するカラムと値の配列
	 * @param	array	$params			DBアクセサに渡すパラメータ ※参照引数
	 *
	 * @return	string	生成したSET
	 */
	function _makeUpdateSetQuery($set_columns,&$params)
	{
		$params = array();
		$set    = array();
		foreach ($set_columns as $key => $value)
		{
			$set[] = "`{$key}` = ?";
			$params[] = $value;
		}
		$set = implode(',', $set);

		return $set;
	}

	/**
	 * 文字列をUTF-8からSJIS-winに変更
	 *
	 * @param 	string	$string
	 * @return	コンバート後文字列
	 */
	function convertUtf8ToSjiswin($string)
	{
		return mb_convert_encoding($string, 'SJIS-win', 'UTF-8');
	}

	/**
	 * 後発作業用コード環境判別
	 *
	 * @return	boolean	true:有効、false：無効
	 */
	function checkUseProjectTypeBack()
	{
		if (defined('PROJECT_TYPE_BACK_FLG') && PROJECT_TYPE_BACK_FLG===true)
		{
			return true;
		}

		return false;
	}

	/**
	 * 指定の2次元配列を結合します。
	 *
	 * @param $two_dimensions_array 2次元配列
	 * @param $delimiters 指定区切文字
	 *
	 * @return 配列要素を結合した文字列
	 */
	function implodeTwoDimensionsArray($two_dimensions_array, $delimiters=',')
	{
		$result = '';
		foreach ($two_dimensions_array as $one_dimensions_array)
		{
			$result .= implode (',', $one_dimensions_array)."\n";
		}
		return $result;
	}

	/**
	 * 指定配列のソートを行います。
	 *
	 * @param	$array	指定配列
	 * @param	$column ソート対象列
	 * @param	$sort 昇順・降順指定 ASC or DESC
	 *
	 * @return	ソートされた配列
	 */
	function usortArray($array, $column, $sort='ASC')
	{
		if (empty($column))
		{
			return $array;
		}

		$sort_value = 1;
		if ($sort == 'DESC')
		{
			$sort_value = -1;
		}
		usort($array, buildSortFunc(array($column => $sort_value)));
		return $array;
	}

	/**
	 * 配列をソートする関数を返す
	 *  ex. $keys = array('create_date', 'id'); => create_date, idの順でソートする
	 *      $keys = array('create_date' => -1, 'id' => 1); => create_dateを逆順, idを正順でソートする
	 * 返り値は、usort()などのソート関数で使用できる
	 *
	 * @param  array  ソートするカラム名の配列
	 * @return function
	 */
	function buildSortFunc($keys)
	{
		$key = key($keys);
		if ($key === 0) {
			// 0の値が取れるようなら、ソート用のキーだけ与えられたと見て、それぞれの値でASCソート
			$func = create_function('$a, $b',
					'foreach ('. var_export($keys, 1). ' as $v) {'.
					'	if ($a[$v] > $b[$v]) {'.
					'		return 1;'.
					'	} elseif ($a[$v] < $b[$v]) {'.
					'		return -1;'.
					'	}'.
					'}'.
					'return 0;'
			);
		} else {
			// それぞれの値によって、キー値でASC/DESCでソートする
			$func = create_function('$a, $b',
					'foreach ('. var_export($keys, 1). ' as $k => $v) {'.
					'	if ($v == -1) {'.
					'		if ($a[$k] < $b[$k]) {'.
					'			return 1;'.
					'		} elseif ($a[$k] > $b[$k]) {'.
					'			return -1;'.
					'		}'.
					'	} else {'.
					'		if ($a[$k] > $b[$k]) {'.
					'			return 1;'.
					'		} elseif ($a[$k] < $b[$k]) {'.
					'			return -1;'.
					'		}'.
					'	}'.
					'}'.
					'return 0;'
			);
		}
		return $func;
	}
	
	/**
	 * 数値の少数桁数を検証
	 *
	 * @param  array  $val 検証値
	 * @param  array  $deci 少数桁の桁数指定
	 * @return 結果 true:正常 false:異常
	 */
	function isNumWithDecimal($val, $deci)
	{
		return preg_match('/^[0-9]+(.[0-9]{1,'.$deci.'})?$/', $val) > 0;
	}
?>