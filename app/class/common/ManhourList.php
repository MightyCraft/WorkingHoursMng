<?php
/**
 * 工数照会画面用クラス群
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
require_once(DIR_APP . '/class/common/dbaccess/Manhour.php');
class ManhourList
{
	/**
	 * プロジェクト別工数照会用変数
	 */
	private $total_weekly_monhour;	// 週計をセット
	private $total_weekly_from;		// 週計開始月
	private $total_weekly_to;		// 週計終了月

	/**
	 * 社員別工数照会用のデータ抽出＆表示用に成形
	 * 日計情報の生成
	 *
	 * @param	string	$date_Year						指定年
	 * @param	string	$date_Month						指定月
	 * @param	integer	$member_id						指定社員
	 * @param	array	&$daily_total（参照変数）		各日合計工数情報
	 * @param	boolean	$leftover_budget_manhour_flg	残工数算出フラグ
	 * @return	array	プロジェクト毎の日別工数情報、合計情報
	 */
	function getProjectListByUseridDate($date_Year,$date_Month,$member_id,&$daily_total=array(),$leftover_budget_manhour_flg=false)
	{
		// 指定社員の指定年月のプロジェクト毎の工数情報＆月計を取得
		$tmp_manhour_list = $this->getManhourListByUseridYm($date_Year,$date_Month,$member_id,$tmp_daily_total);

		$manhour_list = array();
		if (!empty($tmp_manhour_list))
		{
			$obj_db_client	= new Client();
			$obj_db_project	= new Project();
			$obj_db_manhour	= new Manhour();

			$client_list		= $obj_db_client->getDataAll();									// クライアントマスタ情報取得
			$end_project_data	= $obj_db_project->getDataByType(array(PROJECT_TYPE_BACK));		// 後発作業用PJコード取得

			foreach ($tmp_manhour_list as $tmp_project_key => $tmp_project_manhour_list)
			{
				// クライアントマスタ情報を追加
				if (!empty($client_list["{$tmp_project_manhour_list['client_id']}"]))
				{
					$tmp_project_manhour_list['client_name'] = $client_list["{$tmp_project_manhour_list['client_id']}"]['name'];
				}
				else
				{
					if (!empty($tmp_project_manhour_list['project_id']))
					{
						// プロジェクトマスタが存在している時
						$tmp_project_manhour_list['client_name']	= "不明なクライアント({$tmp_project_manhour_list['client_id']})";
					}
					else
					{
						// プロジェクトマスタが存在していない時
						$tmp_project_manhour_list['client_name']	= '不明なクライアント';
					}
				}
				// 後発作業用環境 且つ 終了案件の時は「PJコード」「プロジェクト名」「クライアント名」を後発作業用情報で上書き
				if (checkUseProjectTypeBack() && $tmp_project_manhour_list['end_flg'])
				{
					// TODO: ブラッシュアップ
					$tmp_project_manhour_list['client_name']	= $client_list["{$end_project_data[0]['client_id']}"]['name'];
				}

				// 残工数の算出が必要な時は残工数を追加
				if ($leftover_budget_manhour_flg)
				{
					if (empty($tmp_project_manhour_list['total_budget_manhour']))
					{
						// 総工数の設定がされていない
						$tmp_project_manhour_list['leftover_budget_manhour_now'] = $tmp_project_manhour_list['total_budget_manhour'];
					}
					else
					{
						$tmp_project_manhour_list['leftover_budget_manhour_now'] = (int)$tmp_project_manhour_list['total_budget_manhour'] - (float)$obj_db_manhour->getProjectTimeAll($tmp_project_manhour_list['project_id']);
					}
				}

				$manhour_list[$tmp_project_key] = $tmp_project_manhour_list;
			}

			// 日別の工数合計にグラフ用パーセント情報をセット
			$daily_total['total_man_hour'] = 0;
			foreach ($tmp_daily_total as $day => $daily_total_data)
			{
				// グラフ用にパーセント化する（最大16時間で超過した場合は16時間で表示）
				$max_time	= 16;
				if ((int)$daily_total_data >= $max_time)
				{
					$time = $max_time;
				}
				else
				{
					$time = (int)$daily_total_data;
				}
				$percent	= floor((100 / $max_time) * $time);
				$daily_total[$day]['man_hour']	= $daily_total_data;
				$daily_total[$day]['percent']	= $percent;

				$daily_total['total_man_hour'] 	+= $daily_total_data;
			}

		}

		return $manhour_list;
	}

	/**
	 * 指定プロジェクトの工数リストを取得
	 *
	 * @param	stirng	$date_Year					指定年
	 * @param	strong	$date_Month					指定月
	 * @param	integer	$search_project_data		指定プロジェクトIDのマスタデータ
	 * @param	array	&$total_data（参照変数）	指定年月の各日合計工数情報
	 * @return	array	指定年月の社員/日毎の工数データ
	 */
	function getProjectListByProjectidDate($date_Year,$date_Month,$search_project_data,&$total_data=array())
	{
		$obj_project	= new Project();
		$obj_manhour	= new Manhour();
		$obj_member		= new Member();

		// １．抽出対象となるプロジェクト情報と抽出対象プロジェクトを使用している全工数データを取得
		if ($search_project_data['project_type'] != PROJECT_TYPE_BACK)
		{
			// 通常のPJコードの場合

			// プロジェクトマスタ情報
			$tmp_select_project_data[]				= $search_project_data;							// 指定したプロジェクト
			$total_data['total_budget_manhour']		= $search_project_data['total_budget_manhour'];	// 総割当工数
			// 指定PJの全工数データを取得（end_project_idに指定されているものも含む）
			$tmp_monhour_list = $obj_manhour->getDataByProjectId($search_project_data['id']);
		}
		else
		{
			// 後発作業用PJコードの場合

			// 抽出対象となるプロジェクトID及びマスタ情報の作成
			$project_id_list			= $obj_project->getProjectIdBySettingEndDate(true);		// 案件終了月が設定してあるプロジェクトを取得
			$tmp_select_project_data	= $obj_project->getDataByIds($project_id_list);			// プロジェクトマスタ情報
			$total_data['total_budget_manhour']	= false;										// 総割当工数
			// 上記で抽出したPJの全工数データを取得
			$tmp_monhour_list = $obj_manhour->getDataByProjectIds($project_id_list);
		}
		// プロジェクトマスタ情報の整形（KEYにidセット＆終了案件比較用カラム追加）
		$select_project_data = array();
		foreach ($tmp_select_project_data as $key => $value)
		{
			$select_project_data[$value['id']]	= $value;
			$end_unixtime	= null;
			if (!empty($value['end_date']))
			{
				$end_unixtime	= strtotime($value['end_date']);
			}
			$select_project_data[$value['id']]['end_unixtime'] = $end_unixtime;
		}


		// ２．抽出した工数データより画面表示用配列を生成
		$manhour_list					= array();	// 社員毎工数
		$total_data['total_daily']		= array();	// 日毎縦合計
		$total_data['total_monthly']	= 0;		// 指定月総合計
		$total_data['total_befor']		= 0;		// 指定月以前総合計
		$total_data['total']			= 0;		// 総合計
		$this->total_weekly_monhour		= array();	// 週計をセット
		$this->total_weekly_from		= mktime(0,0,0,date('m'),1,date('Y'));	// 週計開始月をセット(unixtime)
		$this->total_weekly_to			= 0;									// 週計終了月をセット(unixtime)
		foreach ($tmp_monhour_list as $tmp_monhour_day)
		{
			// 抽出対象が後発作業用PJコードの時は終了案件扱いの工数データかチェック
			if ($search_project_data['project_type'] == PROJECT_TYPE_BACK)
			{
				// 終了案件扱いで無いデータは対象外
				if (!empty($tmp_monhour_day['end_project_id']))
				{
					$end_unixtime	= $select_project_data["{$tmp_monhour_day['end_project_id']}"]['end_unixtime'];
				}
				else
				{
					$end_unixtime	= $select_project_data["{$tmp_monhour_day['project_id']}"]['end_unixtime'];
				}
				$check_unixtime	= mktime(0,0,0,$tmp_monhour_day['work_month'],$tmp_monhour_day['work_day'],$tmp_monhour_day['work_year']);

				if (empty($end_unixtime) || ($check_unixtime <= $end_unixtime))
				{

					continue;
				}
			}
			// 表示用配列の生成
			$monhour_ym	= mktime(0,0,0,$tmp_monhour_day['work_month'],1,$tmp_monhour_day['work_year']);
			$search_ym	= mktime(0,0,0,$date_Month,1,$date_Year);

			// ２-１．指定した年月の場合　⇒　社員別の配列に投入
			if ($search_ym == $monhour_ym)
			{
				$tmp_member = $tmp_monhour_day['member_id'];	// KEYは社員コード
				$tmp_day = $tmp_monhour_day['work_day'];	// 日付

				// 新規行(社員)の場合は社員情報取得
				if (empty($manhour_list[$tmp_member]))
				{
					$member_data = $obj_member->getMemberById($tmp_monhour_day['member_id'],true);
					if (!empty($member_data))
					{
						$manhour_list[$tmp_member]['member_id']		= $member_data['id'];
						$manhour_list[$tmp_member]['name']			= $member_data['name'];
						$manhour_list[$tmp_member]['delete_flg']	= $member_data['delete_flg'];
					}
					else
					{
						$manhour_list[$tmp_member]['member_id']		= '';
						$manhour_list[$tmp_member]['name']			= "不明なアカウント({$tmp_monhour_day['member_id']})";
						$manhour_list[$tmp_member]['delete_flg']	= 1;
					}
					// 社員毎合計行の初期化
					$manhour_list[$tmp_member]['total_manhour'] = 0;
				}

				// 社員/日毎の工数情報セット
				if (!isset($manhour_list[$tmp_member][$tmp_day]))
				{
					$manhour_list[$tmp_member][$tmp_day]['manhour'] = $tmp_monhour_day['man_hour'];
				}
				else
				{
					$manhour_list[$tmp_member][$tmp_day]['manhour'] += $tmp_monhour_day['man_hour'];
				}
				// 社員毎の工数合計
				$manhour_list[$tmp_member]['total_manhour'] += $tmp_monhour_day['man_hour'];

				// 日毎の縦工数合計
				if (!isset($total_data['total_daily'][$tmp_day]))
				{
					$total_data['total_daily'][$tmp_day] = $tmp_monhour_day['man_hour'];
				}
				else
				{
					$total_data['total_daily'][$tmp_day] += $tmp_monhour_day['man_hour'];
				}
				// 指定月総合計
				$total_data['total_monthly'] += $tmp_monhour_day['man_hour'];
			}

			// ２－２．指定した年月以前の場合　⇒　指定年月以前総合計カウント
			if  ($search_ym >= $monhour_ym)
			{

				$total_data['total_befor'] += $tmp_monhour_day['man_hour'];
			}

			// ２－３．全データ　⇒　総合計カウント
			$total_data['total'] += $tmp_monhour_day['man_hour'];

			// ２－４．全データ　⇒　週計用作業データ作成
			// 日別工数合計値
			if (empty($this->total_weekly_monhour["{$tmp_monhour_day['work_year']}"]["{$tmp_monhour_day['work_month']}"]["{$tmp_monhour_day['work_day']}"]))
			{
				$this->total_weekly_monhour["{$tmp_monhour_day['work_year']}"]["{$tmp_monhour_day['work_month']}"]["{$tmp_monhour_day['work_day']}"] = $tmp_monhour_day['man_hour'];
			}
			else
			{
				$this->total_weekly_monhour["{$tmp_monhour_day['work_year']}"]["{$tmp_monhour_day['work_month']}"]["{$tmp_monhour_day['work_day']}"] += $tmp_monhour_day['man_hour'];
			}
			// 週計用工数発生開始月と終了月を保持
			if ($this->total_weekly_from > $monhour_ym)
			{
				$this->total_weekly_from = $monhour_ym;	// 工数発生開始月
			}
			if ($this->total_weekly_to < $monhour_ym)
			{
				$this->total_weekly_to = $monhour_ym;	// 工数発生終了月
			}

		}	// 処理対象工数データforeach

		// 各残工数情報セット（総割当工数が設定されている必要あり）
		if ($total_data['total_budget_manhour'] !== false)
		{
			$total_data['leftover_budget_total_befor']	= (float)$total_data['total_budget_manhour'] - (float)$total_data['total_befor'];	// 指定年月以前残工数
			$total_data['leftover_budget_total']		= (float)$total_data['total_budget_manhour'] - (float)$total_data['total'];			// 残工数
		}
		else
		{
			$total_data['leftover_budget_total_befor']	= false;	// 指定年月以前残工数
			$total_data['leftover_budget_total']		= false;	// 残工数
		}

		// 指定月社員/日毎工数データを社員ID順にソート
		ksort($manhour_list);

		return $manhour_list;
	}
	/**
	 * プロジェクトの週単位の工数を算出
	 *
	 * @return	array	週単位に集計された工数情報
	 */
	function getTotalWeeklyManhour()
	{
		// 週計カレンダー開始年月と終了年月を取得
		$year_from	= (int)date('Y',$this->total_weekly_from);
		$month_from	= (int)date('m',$this->total_weekly_from);
		$year_to	= (int)date('Y',$this->total_weekly_to);
		$month_to	= (int)date('m',$this->total_weekly_to);

		// 週計用数カレンダーの作成＆工数反映
		$calendar	=	array();
		$month		=	$month_from;

		// 年別
		for ($year=$year_from; $year<=$year_to; $year++)
		{
			// 月別
			for( ; ($month<=12) && !($year==$year_to && $month>$month_to); $month++)
			{
				//その月の最初の曜日を取得
				$first_day_week	= date('w',mktime(0,0,0,$month,1,$year));
				//その月の最終日を取得
				$last_day		= date('d',mktime(0,0,0,$month+1,0,$year));
				// 週のカウントの初期化
				$cnt_week		= 0;
				// 月計の初期化
				$calendar[$year][$month]['monthly'] = 0;

				// 日計から週計を生成
				for($day=1; $day<=$last_day; $day++)
				{
					// 週計の配列が無ければ初期化
					if(!isset($calendar[$year][$month]['weekly'][$cnt_week]))
					{
						$calendar[$year][$month]['weekly'][$cnt_week]['manhour']	= 0;		// 週計
						$calendar[$year][$month]['weekly'][$cnt_week]['day']		= $day;		// 週開始日の保持
					}

					// 工数の加算
					if (isset($this->total_weekly_monhour[$year][$month][$day]))
					{
						// 週計
						$calendar[$year][$month]['weekly'][$cnt_week]['manhour'] += (float)$this->total_weekly_monhour[$year][$month][$day];
						// 月計
						$calendar[$year][$month]['monthly'] += (float)$this->total_weekly_monhour[$year][$month][$day];
					}

					// 曜日のカウントアップ＆週のカウントアップ
					if($first_day_week < 6)
					{
						// 日曜日～金曜日までは曜日のカウントアップ
						$first_day_week++;
					}
					else
					{
						// 土曜日の時は曜日のリセット＆週のカウントアップ
						$first_day_week = 0;
						$cnt_week++;
					}
				}
			}
			$month = 1;
		}

		// 週計を年降順にソート
		krsort($calendar);

		return $calendar;
	}

	/**
	 * 社員・年月指定の工数情報を抽出、プロジェクト別工数計
	 *
	 * @param	string	$date_Year						指定年
	 * @param	string	$date_Month						指定月
	 * @param	integer	$member_id						指定社員
	 * @param	array	$daily_total_data（参照変数）	日計
	 * @return	array	プロジェクト毎の日別工数情報/月計情報
	 */
	function getManhourListByUseridYm($date_Year,$date_Month,$member_id,&$daily_total_data=array())
	{
		// 指定社員、指定年月の工数情報取得
		$obj_db_manhour		= new Manhour();
		$tmp_manhour_list	= $obj_db_manhour->getDataByIdYearMonth($member_id,$date_Year,$date_Month);
		// 工数データが無い場合は処理終了
		if (empty($tmp_manhour_list))
		{
			return array();
		}

		// プロジェクトマスタ情報取得
		$obj_db_project		= new Project();
		$project_list		= $obj_db_project->getDataAll(false,true);						// 削除フラグONは含まない&endunixtime付加
		$end_project_data	= $obj_db_project->getDataByType(array(PROJECT_TYPE_BACK));		// 後発作業用PJコード

		//---------
		// 取得した工数情報から表示用にプロジェクト別に成形
		//---------
		foreach ($tmp_manhour_list as $tmp_monhour_day)
		{
			// １．プロジェクトマスタの存在チェック
			$project_error_flg = false;
			if (empty($project_list["{$tmp_monhour_day['project_id']}"]))
			{
				$project_error_flg = true;		// project_idがマスタに存在しない
			}
			if ((!empty($tmp_monhour_day['end_project_id'])) && (empty($project_list["{$tmp_monhour_day['end_project_id']}"])))
			{
				$project_error_flg = true;		// end_project_idにセットされているANDマスタに存在しない
			}

			// ２．プロジェクトID及びプロジェクトマスタ情報の取得（後発作業の時は実際に作業した案件のPJコードを使用）
			$project_data = array();
			if (!$project_error_flg)
			{
				if ($project_list["{$tmp_monhour_day['project_id']}"]['project_type'] == PROJECT_TYPE_BACK)
				{
					// 「project_id」が「project_type=2(後発作業用PJコード)」の場合「end_project_id」が実際に使用したプロジェクトID
					// 後発作業用PJコードを使用していたのに「end_project_id」がセットされていない場合はエラー扱いとする
					if (!empty($project_list["{$tmp_monhour_day['end_project_id']}"]))
					{
						$project_data = $project_list["{$tmp_monhour_day['end_project_id']}"];
					}
				}
				else
				{
					// 後発作業用PJコードを使用していないのに「end_project_id」がセットされている場合はエラー扱いとする
					if (empty($project_list["{$tmp_monhour_day['end_project_id']}"]))
					{
						$project_data = $project_list["{$tmp_monhour_day['project_id']}"];
					}
				}
			}

			// ３．プロジェクト別の配列に構成変更（同プロジェクトでも同月内途中で終了案件に切り替わる場合は別行で作成）
			if ((!$project_error_flg) && (!empty($project_data)) && (!$project_data['delete_flg']))
			{
				// 有効なプロジェクトマスタ情報あり

				$tmp_key = $project_data['id'];			// プロジェクト別配列用のKEY
				$tmp_monhour_day['end_flg'] = false;	// 終了案件判別フラグ
				// 終了案件判別
				$chack_unixtime = mktime(0,0,0,$tmp_monhour_day['work_month'],$tmp_monhour_day['work_day'],$tmp_monhour_day['work_year']);
				if (!empty($project_data['end_unixtime']) && ($chack_unixtime > $project_data['end_unixtime']))
				{
					// 終了案件
					$tmp_monhour_day['end_flg'] = true;
					if (checkUseProjectTypeBack())
					{
						// 後発作業用コード環境
						// 同月内＆同プロジェクトで通常/終了が混在した場合は２行に分かれて表示する必要があるのでKEY値を変更
						$tmp_key = 'END'.$project_data['id'];
					}
				}

				// 新たに配列にセットするプロジェクトの場合はプロジェクト関連情報セット
				if (empty($manhour_list[$tmp_key]))
				{
					// マスタ情報
					$manhour_list[$tmp_key]['project_id']	= $project_data['id'];
					$manhour_list[$tmp_key]['project_code']	= $project_data['project_code'];
					$manhour_list[$tmp_key]['project_name']	= $project_data['name'];
					$manhour_list[$tmp_key]['project_type']	= $project_data['project_type'];
					$manhour_list[$tmp_key]['client_id']	= $project_data['client_id'];
					if (!empty($project_data['total_budget_manhour']))
					{
						$manhour_list[$tmp_key]['total_budget_manhour'] = $project_data['total_budget_manhour'];
					}
					else
					{
						$manhour_list[$tmp_key]['total_budget_manhour'] = 0;
					}
					$manhour_list[$tmp_key]['end_flg']		= $tmp_monhour_day['end_flg'];

					// 後発作業用コード環境 且つ 終了案件の時は「PJコード」「プロジェクト名」「クライアント名」を後発作業用の書式で上書き
					if (checkUseProjectTypeBack() && $tmp_monhour_day['end_flg'])
					{
						// TODO: ブラッシュアップ
						$manhour_list[$tmp_key]['project_code']	= $end_project_data[0]['project_code'];
						$manhour_list[$tmp_key]['project_name']	= "後発作業 {$project_data['project_code']}";
					}

					// プロジェクト別工数合計初期化
					$manhour_list[$tmp_key]['total_manhour'] = 0;
				}
			}
			else
			{
				// 有効なプロジェクトマスタ情報なし(削除フラグON/マスタ削除) or 工数データが不正(後発作業用PJコードでend_project_id未セット/通常案件IDでend_project_idがセット)

				// プロジェクト別配列用のKEY設定
				$tmp_monhour_day['end_flg'] = false;				// 終了案件判別フラグ
				$tmp_key = 'DEL'.$tmp_monhour_day['project_id'];	// プロジェクト別配列用のKEY
				if (checkUseProjectTypeBack())
				{
					// 後発作業用コード環境の時だけ終了案件判別
					if ($end_project_data[0]['id'] == $tmp_monhour_day['project_id'])
					{
						// TODO: ブラッシュアップ
						$tmp_monhour_day['end_flg'] = true;						// 終了案件判別フラグ
						$tmp_key = 'DEL'.$tmp_monhour_day['end_project_id'];	// プロジェクト別配列用のKEY
					}
				}

				// 新たに配列にセットするプロジェクトの場合はプロジェクト関連情報セット
				if (empty($manhour_list[$tmp_key]))
				{
					// マスタ情報
					$manhour_list[$tmp_key]['project_id']	= '';
					$manhour_list[$tmp_key]['project_code']	= '-';
					$manhour_list[$tmp_key]['project_name']	= "不明なプロジェクト({$tmp_monhour_day['project_id']}/{$tmp_monhour_day['end_project_id']})";
					$manhour_list[$tmp_key]['project_type']	= '';
					$manhour_list[$tmp_key]['client_id']	= '';
					$manhour_list[$tmp_key]['total_budget_manhour']	= false;
					$manhour_list[$tmp_key]['end_flg']		= $tmp_monhour_day['end_flg'];

					// 後発作業用コード環境で終了案件の時のみ後発作業用コード情報を上書きセット
					if (checkUseProjectTypeBack() && $tmp_monhour_day['end_flg'])
					{
						$manhour_list[$tmp_key]['project_code']	= $end_project_data[0]['project_code'];
						$manhour_list[$tmp_key]['project_name']	= "後発作業[不明なプロジェクト({$tmp_monhour_day['project_id']}/{$tmp_monhour_day['end_project_id']})]";
					}

					// プロジェクト別工数合計初期化
					$manhour_list[$tmp_key]['total_manhour'] = 0;
				}
			}

			// ４．日別の工数情報セット
			$tmp_day = $tmp_monhour_day['work_day'];
			$manhour_list[$tmp_key][$tmp_day] = $tmp_monhour_day;

			// ５．プロジェクト別の工数合計
			$manhour_list[$tmp_key]['total_manhour'] += $tmp_monhour_day['man_hour'];

			// ６．日別の工数合計
			if (empty($daily_total_data[$tmp_day]))
			{
				$daily_total_data[$tmp_day] = $tmp_monhour_day['man_hour'];
			}
			else
			{
				$daily_total_data[$tmp_day] += $tmp_monhour_day['man_hour'];
			}
		}	// 工数データのForeachEnd

		// プロジェクトID順にソート（後発作業扱いは上1桁に「END」/不正なデータは上桁1に「DEL」）
		krsort($manhour_list);

		return $manhour_list;
	}




}