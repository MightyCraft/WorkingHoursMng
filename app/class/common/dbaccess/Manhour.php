<?php

class Manhour
{
	var $instance_db;
	var $table="trn_manhour";

	var $old_year = 2005;
	var $s_month = 1;
	var $e_month = 12;

	function __construct()
	{
		$this->instance_db = DatabaseSetting::getAccessor();
	}
	/**
	 * 社員の作業データ取得
	 *
	 * TODO: ブラッシュアップ
	 *
	 * @param integer $id 社員ID
	 * @param integer $year 抽出年
	 * @param integer $month 抽出月
	 */
	function getDataByIdYearMonth($id,$year,$month)
	{
		$res	= $this->instance_db->select('SELECT * FROM ' . $this->table . ' WHERE member_id = ? AND work_year = ? AND work_month = ? ORDER BY project_id ASC, work_day ASC',array($id,$year,$month));
		return $res;
	}

	/**
	 * 指定の社員が指定日に入力した工数を取得
	 *
	 * @author hirano
	 * @param int member_id 社員ID
	 * @param int year 指定年
	 * @param int month 指定月
	 * @param int day 指定日
	 */
	function getProjectTargetDate($member_id, $year, $month, $day)
	{
		$sql	="SELECT * FROM {$this->table} WHERE member_id = ? AND work_year = ? AND work_month = ? AND work_day = ?";
		$res	= $this->instance_db->select($sql,array($member_id,$year,$month,$day));
		return $res;
	}

	/**
	 * 指定プロジェクトの指定範囲の月に入力された工数データを取得
	 *
	 * @author hirano
	 * @param integer $project_id プロジェクトID
	 * @param integer $start_year	開始年
	 * @param integer $start_month	開始月
	 * @param integer $end_year		開始年
	 * @param integer $end_month	開始月
	 */
	function getProjectBetweenDate($project_id, $start_year, $start_month, $end_year, $end_month)
	{
		// where句生成
		$where			= array();
		$where_param	= array();
		//プロジェクトID指定確認
		if(!empty($project_id))
		{
			//配列に対応
			if(is_array($project_id))
			{
				$where_set		= array();
				$where_set		= array_pad($where_set, count($project_id), '?');
				$where_set		= implode(',', $where_set);
				if (checkUseProjectTypeBack())
				{
					// 後発作業用コード環境
					$where[]		= '(project_id IN ('.$where_set.') OR end_project_id IN ('.$where_set.'))';
					$where_param	= array_merge($where_param, $project_id);
					$where_param	= array_merge($where_param, $project_id);
				}
				else
				{
					$where[]		= ' project_id IN ('.$where_set.') ';
					$where_param	= array_merge($where_param, $project_id);
				}
			}
			else
			{
				if (checkUseProjectTypeBack())
				{
					// 後発作業用コード環境
					$where[]		= '(project_id = ? OR end_project_id = ?)';
					$where_param[]	= $project_id;
					$where_param[]	= $project_id;
				}
				else
				{
					$where[]		= ' project_id = ? ';
					$where_param[]	= $project_id;
				}
			}
		}

		$where[]	= '((work_year = ? AND work_month >= ?) OR ( ? < work_year AND work_year < ? ) OR (work_year = ? AND work_month <= ?))';
		$where_param[]	= $start_year;	// 開始年の対象データの取得
		$where_param[]	= $start_month;	//
		$where_param[]	= $start_year;	// 開始年と終了年の間の年のデータの取得
		$where_param[]	= $end_year;	//
		$where_param[]	= $end_year;	// 終了年の対象データの取得
		$where_param[]	= $end_month;	//

		$sql	="SELECT * FROM {$this->table} WHERE ".implode(' AND ', $where);
		$res	= $this->instance_db->select($sql, $where_param);
		return $res;
	}

	/**
	 * 指定プロジェクトの工数データ取得
	 *
	 * @param integer $project_id プロジェクトID
	 */
	function getDataByProjectId($project_id)
	{
		if (empty($project_id))
		{
			return array();
		}

		$where		= ' WHERE project_id = ? ';
		$param[]	= $project_id;
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			$where		.= ' OR end_project_id = ? ';
			$param[]	= $project_id;
		}

		$sql	= "SELECT * FROM {$this->table} {$where}";
		$res	= $this->instance_db->select($sql,$param);

		return $res;
	}

	/**
	 * 指定プロジェクトの工数データ取得（複数指定対応）
	 *
	 * @param array	$project_id プロジェクトID
	 */
	function getDataByProjectIds($project_ids)
	{
		if (empty($project_ids))
		{
			return array();
		}

		$params		= array();
		$where_in	= array();
		foreach($project_ids as $v)
		{
			$where_in[]	= '?';
			$params[]	= $v;
		}
		$where_ids = implode(', ', $where_in);

		$where	= ' WHERE `project_id` IN ('.$where_ids.') ';
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			$where	.= ' OR `end_project_id` IN ('.$where_ids.') ';
			$params	= array_merge($params,$params);
		}

		$sql = 'SELECT * FROM '. $this->table. $where;
		$result	= $this->instance_db->select($sql, $params);

		return $result;
	}

	/**
	 * メンバーIDとプロジェクトIDにより、作業データ取得
	 *  指定が不正な条件は外す
	 *
	 * TODO: ブラッシュアップ
	 *
	 * @param integer $project_id	プロジェクトID
	 * @param integer $member_id	メンバーID
	 * @param integer $year			抽出年
	 * @param integer $month		抽出月
	 */
	function getDataByProjectIdAndMemberIdYearMonth($project_id,$member_id,$year,$month)
	{
		$where_project = '';
		$project_param_array = array();
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			$where_project = '(project_id = ? OR end_project_id = ?)';
			$project_param_array	= array(
				$project_id, $project_id,
			);

		}
		else
		{
			$where_project = 'project_id = ?';
			$project_param_array	= array(
				$project_id,
			);

		}

		$where_array	= array(
			$where_project,
			'member_id = ?',
			'work_year = ?',
			'work_month = ?',
		);

		$param_array	= array_merge(
			$project_param_array,
			array(
				$member_id,
				$year,
				$month,
			)
		);

		$where	= implode(' AND ', $where_array);
		$res	= $this->instance_db->select(
					"SELECT * FROM {$this->table} WHERE {$where} ORDER BY work_day ASC", $param_array);
		return $res;
	}

	/**
	 * 工数登録
	 *
	 * @author hirano
	 * @param int member_id 社員番号
	 * @param int project_id プロジェクトID
	 * @param int end_project_id 終了プロジェクトID
	 * @param int man_hour 工数
	 * @param text memo 備考
	 */
	function writeManhour($member_id,$project_id,$end_project_id,$man_hour,$work_year,$work_month,$work_day,$memo)
	{
		if(!$end_project_id) $end_project_id=0;
		$now_date=date("Y-m-d H:i:s");
		$this->instance_db->beginTransaction();
		$sql="INSERT INTO {$this->table} (member_id,project_id,end_project_id,man_hour,memo,work_year,work_month,work_day,regist_date) VALUES(?,?,?,?,?,?,?,?,?)";
		$insert_array=array($member_id,$project_id,$end_project_id,$man_hour,$memo,$work_year,$work_month,$work_day,$now_date);
		$res = $this->instance_db->insert($sql,$insert_array);
		if(!$res)	$this->instance_db->rollback();
		else		$this->instance_db->commit();
		return $res;
	}

	/**
	 * 工数更新
	 * 年/月/日/社員/プロジェクト指定でproject_idを一括変更する
	 *
	 * @param	integer	$member_id
	 * @param	integer	$year
	 * @param	integer	$month
	 * @param	array	$days
	 * @param	integer	$befor_project_id 変更前プロジェクト
	 * @param	integer	$after_project_id 変更後プロジェクト
	 */
	function updateManhourProjectByMenberAndDay($member_id,$year,$month,$days,$befor_project_id,$after_project_id)
	{
		// project_idの更新
		// パラメータ生成
		$update_columns = array(
			'project_id'	=> $after_project_id,
		);
		$where_columns = array(
			'member_id'		=> (int)$member_id,
			'work_year'		=> (int)$year,
			'work_month'	=> (int)$month,
			'work_day'		=> $days,
			'project_id'	=> (int)$befor_project_id,
		);
		// set句生成
		$update_params = array();
		$set = _makeUpdateSetQuery($update_columns,$update_params);
		// where句生成
		$where_params = array();
		$where = _makeWhereQuery($where_columns, $where_params);
		// update実行
		$sql="UPDATE {$this->table} SET {$set} {$where}";
		$params = array_merge($update_params,$where_params);
		$res_project = $this->instance_db->update($sql,$params);

		// end_project_idの更新　TODO: ブラッシュアップ
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			// パラメータ生成
			$update_columns = array(
				'end_project_id'	=> $after_project_id,
			);
			$where_columns = array(
				'member_id'		=> (int)$member_id,
				'work_year'		=> (int)$year,
				'work_month'	=> (int)$month,
				'work_day'		=> $days,
				'end_project_id'=> (int)$befor_project_id,
			);
			// set句生成
			$update_params = array();
			$set = _makeUpdateSetQuery($update_columns,$update_params);
			// where句生成
			$where_params = array();
			$where = _makeWhereQuery($where_columns, $where_params);
			// update実行
			$sql="UPDATE {$this->table} SET {$set} {$where}";
			$params = array_merge($update_params,$where_params);
			$res_endproject = $this->instance_db->update($sql,$params);
		}

		return;
	}

	/**
	 * 既にその日の工数を入力したかどうかチェック
	 */
	function checkInputManhour($member_id,$year,$month,$day)
	{
		$sql="SELECT * FROM {$this->table} WHERE member_id=? AND work_year=? AND work_month=? AND work_day =? ";
		$res = $this->instance_db->select($sql,array($member_id,$year,$month,$day));

		return $res;
	}

	/**
	 * 既にその日の工数を入力したかどうかチェック
	 */
	function deleteManhour($member_id,$year,$month,$day)
	{
		$sql="DELETE FROM {$this->table} WHERE member_id=? AND work_year=? AND work_month=? AND work_day =? ";
		$res = $this->instance_db->delete($sql,array($member_id,$year,$month,$day));

		return $res;
	}

	/**
	 * 既に該当プロジェクトのその日の工数を入力したかどうかチェック
	 * TODO: ブラッシュアップ
	 */
	function checkInputManhour2($member_id,$work_year,$work_month)
	{
		$sql="SELECT * FROM {$this->table} WHERE member_id=? AND work_year=? AND work_month=? ";
		$res = $this->instance_db->select($sql,array($member_id,$work_year,$work_month));

		return $res;
	}

	/**
	 * 既に該当プロジェクトのその日の工数を入力していれば削除
	 * TODO: ブラッシュアップ
	 */
	function deleteManhour2($member_id,$work_year,$work_month)
	{
		$sql="DELETE FROM {$this->table} WHERE member_id=? AND work_year=? AND work_month=? ";
		$res = $this->instance_db->delete($sql,array($member_id,$work_year,$work_month));

		return $res;
	}

	/**
	 * 指定のプロジェクトの総工数を取得
	 */
	function getProjectTimeAll($project_id)
	{
		if (empty($project_id))
		{
			return null;
		}

		$where		= ' WHERE project_id = ? ';
		$param[]	= $project_id;
		if (checkUseProjectTypeBack())
		{
			// 後発作業用コード環境
			$where		.= ' OR end_project_id = ? ';
			$param[]	= $project_id;
		}

		$sql	= "SELECT SUM(man_hour) as sumtime FROM {$this->table} {$where}";
		$res	= $this->instance_db->select($sql,$param);

		return $res[0]['sumtime'];
	}

	/**
	 * 指定社員の指定月の工数合計を取得
	 *
	 * @param integer $id 社員ID
	 * @param integer $year 抽出年
	 * @param integer $month 抽出月
	 */
	function getSumDataByIdYearMonth($id,$year,$month)
	{
		$sql  = 'SELECT work_day, SUM(man_hour) man_hour';
		$sql .= '  FROM ' . $this->table;
		$sql .= ' WHERE member_id = ? AND work_year = ? AND work_month = ? ';
		$sql .= ' GROUP BY work_day ';

		$res	= $this->instance_db->select($sql,array($id,$year,$month));
		return $res;
	}
}

?>