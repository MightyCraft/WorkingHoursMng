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
	 * プロジェクトレベル権限をチェックする
	 *
	 * @param array	$auth_array	権限チェック配列
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuth($auth_array, $auth_lv, $post)
	{
		//権限レベル
		if(isset($auth_array['auth']))
		{
			if(array_search($auth_lv, $auth_array['auth']) !== false)
			{
				return	true;
			}
		}
		//役職
		if(isset($auth_array['post']))
		{
			if(array_search($post, $auth_array['post']) !== false)
			{
				return	true;
			}
		}
		return	false;
	}

	/**
	 * アカウント管理権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthAccountManagement($auth_lv, $post)
	{
		static	$auth_account_management	= false;
		if(!$auth_account_management)
		{
			$auth_account_management	= returnArrayAuthAccountManagement();
		}

		return	checkAuth($auth_account_management, $auth_lv, $post);
	}

	/**
	 * プロジェクト管理権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthProjectManagement($auth_lv, $post)
	{
		static	$auth_project_management	= false;
		if(!$auth_project_management)
		{
			$auth_project_management	= returnArrayAuthProjectManagement();
		}

		return	checkAuth($auth_project_management, $auth_lv, $post);
	}

	/**
	 * クライアント管理権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthClientManagement($auth_lv, $post)
	{
		static	$auth_client_management	= false;
		if(!$auth_client_management)
		{
			$auth_client_management	= returnArrayAuthClientManagement();
		}

		return	checkAuth($auth_client_management, $auth_lv, $post);
	}

	/**
	 * 総予算工数周り権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthTotalManhour($auth_lv, $post)
	{
		static	$auth_total_manhour	= false;
		if(!$auth_total_manhour)
		{
			$auth_total_manhour	= returnArrayAuthTotalManhour();
		}

		return	checkAuth($auth_total_manhour, $auth_lv, $post);
	}

	/**
	 * 部署管理権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthPostManagement($auth_lv, $post)
	{
		static	$auth_holiday	= false;
		if(!$auth_holiday)
		{
			$auth_holiday	= returnArrayAuthPostManagement();
		}

		return	checkAuth($auth_holiday, $auth_lv, $post);
	}

	/**
	 * 休日管理権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthHolidayManagement($auth_lv, $post)
	{
		static	$auth_holiday	= false;
		if(!$auth_holiday)
		{
			$auth_holiday	= returnArrayAuthHolidayManagement();
		}

		return	checkAuth($auth_holiday, $auth_lv, $post);
	}

	/**
	 * 他人のエクセル出力権限をチェックする
	 *
	 * @param int	$auth_lv	権限レベル
	 * @param int	$post		役職
	 * @return boolean
	 */
	function checkAuthExcel($auth_lv, $post)
	{
		static	$auth_excel	= false;
		if(!$auth_excel)
		{
			$auth_excel	= returnArrayAuthExcel();
		}

		return	checkAuth($auth_excel, $auth_lv, $post);
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
	 * 総割当工数計算（時間）
	 *
	 * @param	int	$total_buget：総予算
	 * @return	int	四捨五入した数値を返す
	 */
	function getTotal_budget_manhour($total_budget)
	{
		$equation = sprintf(USER_TOTAL_BUDGET_MANHOUR_EQUATION, $total_budget);
		eval('$result = '. $equation. ';');

		return round($result, 0);
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

?>