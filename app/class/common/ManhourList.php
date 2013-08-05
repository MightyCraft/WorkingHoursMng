<?php
/**
 * 工数照会画面用クラス群
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
require_once(DIR_APP . '/class/common/dbaccess/Holiday.php');
require_once(DIR_APP . '/class/common/dbaccess/MemberCost.php');
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
	 * @return	array	プロジェクト毎の日別工数情報、合計情報
	 */
	function getProjectListByUseridDate($date_Year,$date_Month,$member_id,&$daily_total=array())
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
			$total_data['total_cost_manhour']		= $search_project_data['total_cost_manhour'];	// 総割当工数
			$total_data['use_cost_manhour']		= $search_project_data['use_cost_manhour'];	// 総割当工数
			// 指定PJの全工数データを取得（end_project_idに指定されているものも含む）
			$tmp_monhour_list = $obj_manhour->getDataByProjectId($search_project_data['id']);
		}
		else
		{
			// 後発作業用PJコードの場合

			// 抽出対象となるプロジェクトID及びマスタ情報の作成
			$project_id_list			= $obj_project->getProjectIdBySettingEndDate(true);		// 案件終了月が設定してあるプロジェクトを取得
			$tmp_select_project_data	= $obj_project->getDataByIds($project_id_list);			// プロジェクトマスタ情報
			$total_data['total_cost_manhour']	= false;										// 総割当工数
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
		if ($total_data['total_cost_manhour'] !== false)
		{
			$total_data['total_remains_manhour']		= (float)$total_data['total_cost_manhour'] - (float)$total_data['use_cost_manhour'];			// 残工数
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

	/**
	 * 社員毎の工数の月合計を算出する
	 *
	 * @param	string	$date_Year						指定年
	 * @param	string	$date_Month						指定月
	 *
	 * @return	array	社員毎の月工数、月残業工数合計
	 */
	function monthManHourTotal($date_Year, $date_Month)
	{
		$obj_member		= new Member;
		$obj_manhour	= new Manhour;

		// 社員情報取得（削除済みも含む）
		$member_list = $obj_member->getMemberAll(true);

		// 工数情報取得（残業時間取得のためにまずは日毎に集計）
		$manhour_list = $obj_manhour->getDataByYearMonthSumDay($date_Year, $date_Month);

		// 月毎集計＆残業時間取得
		$tmp_download_data[0] = array('id',"社員名",'総作業時間','内残業時間');
		foreach ($manhour_list as $manhour_data)
		{
			// まだ未集計社員なら社員情報を取得
			if (!isset($tmp_download_data[$manhour_data['member_id']]))
			{
				$tmp_download_data[$manhour_data['member_id']]['id'] = $manhour_data['member_id'];
				$tmp_download_data[$manhour_data['member_id']]['name'] = $member_list[$manhour_data['member_id']]['name'];
				$tmp_download_data[$manhour_data['member_id']]['total'] = 0;
				$tmp_download_data[$manhour_data['member_id']]['over'] = 0;
			}
			// 月工数、残業工数
			$tmp_download_data[$manhour_data['member_id']]['total'] += (float)$manhour_data['day_manhour'];
			if ($manhour_data['day_manhour'] > USER_WORKING_HOURS_DAY)
			{
				$tmp_download_data[$manhour_data['member_id']]['over'] += (float)$manhour_data['day_manhour'] - 8;
			}
		}
		return $tmp_download_data;
	}

	/**
	 * 指摘期間内での社員毎欠勤日情報を取得
	 *
	 * @param	string	$start_ym	期間開始年月
	 * @param	string	$end_ym		期間終了年月
	 *
	 * @return	array	社員毎欠勤日情報	array(array(社員ID,社員名,回数,3/1,3/2・・・),array(・・・))
	 */
	function memberAbsenceDays($start_ym, $end_ym)
	{
		$obj_member = new Member;
		$obj_manhour = new Manhour;
		// ---------------------------------------
		// 指定期間内の工数入力のある日付を取得
		// ---------------------------------------
		// 製作部のみ抽出対象とする
		$post_obj = new Post();
		$post_list = $post_obj->getDataByType(PostTypeDefine::GENE_PRODUCT);
		$condition_post = array();
		foreach($post_list as $post)
		{
			$condition_post[] = $post['id'];
		}
		
		// 社員情報取得
		$member_list_work = $obj_member->getMemberByPost($condition_post);
		$member_list = array();
		foreach ($member_list_work as $member)
		{
			$member_list[$member['id']] = $member;
		}
		$member_ids = array_keys($member_list);

		// 指定社員の作業のあった日付を取得（期間指定可能）
		$start_date = date("Y-m-d", strtotime($start_ym.'-1'));
		$end_date = date("Y-m-t", strtotime($end_ym.'-1'));
		$work_date_list = $obj_manhour->getRengeWorkDateByMemberIds($member_ids, $start_date, $end_date);

		// 社員ID・日付をキーに配列を調整
		$member_work_date_list = array();
		foreach ($work_date_list as $work_date)
		{
			$member_work_date_list[$work_date['member_id']][$work_date['input_date']] = $work_date['input_date'];
		}
		// データが1件も取得できなかった社員IDを配列にセットしておく
		foreach ($member_ids as $member_id)
		{
			if (!isset($member_work_date_list[$member_id]))
			{
				$member_work_date_list[$member_id] = array();
			}
		}
		ksort($member_work_date_list);

		// ----------------------------------------
		// 出勤日となる日付を取得
		// ----------------------------------------
		$crrent_date = $start_date;
		$is_over_renge = false;
		$system_date_status_list = array();
		$system_date_status_all_list = array();
		while ($crrent_date <= $end_date)
		{
			$work_year = date("Y", strtotime($crrent_date));
			$work_month = date("m", strtotime($crrent_date));

			// 該当月の休日情報取得
			//土日祝日埋め込み（0:平日、1:土曜、2:日曜）
			$system_date_status_list = getWeekendsHolidays($work_year, $work_month);

			// マスタ設定休日取得（3:指定休日で上書き）
			$obj_holiday = new Holiday;
			$mst_holiday_list = $obj_holiday->getData($work_year, $work_month);
			if (!empty($mst_holiday_list))
			{
				foreach ($mst_holiday_list as $value)
				{
					if ((int) ($value['holiday_year']) == $work_year && (int) ($value['holiday_month']) == $work_month)
					{
						$system_date_status_list[$value['holiday_day']] = 3;
					}
				}
			}
			// 出勤日の配列を作成
			foreach ($system_date_status_list as $key => $system_date_status)
			{
				if ($system_date_status != 0)
				{
					unset($system_date_status_list[$key]);
				}
				else
				{
					$system_date_status_all_list[$work_year.'-'.$work_month.'-'.sprintf("%02d", $key)] = $system_date_status;
				}
			}
			$crrent_date = date("Y-m-1", strtotime('+1 month', strtotime($crrent_date)));
		}
		
		// ----------------------------------------
		// 社員毎の欠勤日を判定し配列に格納
		// ----------------------------------------
		$member_absence_days = array();
		foreach ($member_work_date_list as $member_id => $member_work_date)
		{
			// ID
			$member_absence_days[$member_id]['id'] = $member_list[$member_id]['id'];
			// 社員名
			$member_absence_days[$member_id]['name'] = $member_list[$member_id]['name'];

			// 欠勤日を算出する
			$absence_date_list = array();
			$regist_date = date('Y-m-d', strtotime($member_list[$member_id]['regist_date']));
			foreach ($system_date_status_all_list as $target_date => $system_date_status)
			{
				// 入社日以前は無視する
				if ($regist_date > $target_date) 
				{
					continue;
				}
				// 出勤日に作業が無い場合は欠勤とカウント
				if (!isset($member_work_date[$target_date]))
				{
					$absence_date_list[] = $target_date;
				}
			}
			// 欠勤回数
			$member_absence_days[$member_id]['count'] = count($absence_date_list);
			// 欠勤日
			$i = 1;
			foreach ($absence_date_list as $absence_date)
			{
				$member_absence_days[$member_id]['absence_date_'.$i] = str_replace("2013-", "", $absence_date);
				$i++;
			}
		}
		return $member_absence_days;
	}
	
	
	/**
	 * プロジェクト毎の工数の月合計を算出する
	 *
	 * @param	string	$date_Year						指定年
	 * @param	string	$date_Month						指定月
	 *
	 * @return	array	社員毎の月工数、月残業工数合計
	 */
	function monthManHourTotalProject($date_Year, $date_Month)
	{
		$obj_project	= new Project;
		$obj_manhour	= new Manhour;

		// プロジェクト情報取得
		$project_list	= $obj_project->getDataAll();

		// 工数情報取得
		$manhour_list = $obj_manhour->getDataByYearMonth($date_Year, $date_Month);
		$tmp_count_data = array();
		// 月毎集計＆残業時間取得
		foreach ($manhour_list as $manhour_data)
		{
			// まだ未集計のプロジェクトならプロジェクト情報を取得
			if (!isset($tmp_count_data[$manhour_data['project_id']]))
			{
				$tmp_count_data[$manhour_data['project_id']]['project_id'] = $manhour_data['project_id'];
				$tmp_count_data[$manhour_data['project_id']]['name'] = $project_list[$manhour_data['project_id']]['name'];
				$tmp_count_data[$manhour_data['project_id']]['total'] = 0;
				$tmp_count_data[$manhour_data['project_id']]['over'] = 0;
			}
			// 月工数、残業工数
			$tmp_count_data[$manhour_data['project_id']]['total'] += (float)$manhour_data['man_hour'];
			if ($manhour_data['man_hour'] > USER_WORKING_HOURS_DAY)
			{
				$tmp_count_data[$manhour_data['project_id']]['over'] += (float)$manhour_data['man_hour'] - 8;
			}
		}
		return $tmp_count_data;
	}

	
	
	/**
	 * 危険プロジェクトであるかの判定基準となる情報を集計した内容が付加されたプロジェクト情報を返却
	 *
	 * @param 	datetime	$date	基準日時
	 * @param 	string	$keyword	基準日時 検索条件キーワード
	 * @param 	string	$order	ソート順 
	 * @param 	string	$type	抽出タイプ all:全て over:総割当コスト工数、開発終了期間未設定は対象外 used:総割当コスト工数未設定は対象外
	 * @param 	string	$used_rate 検索条件 使用済コスト工数
	 * @return	array	プロジェクトの工数集計データ
	 */
	
	function getRiscProjectList($date, $member_id = null, $keyword = null, $order=array(), $type='all', $used_rate=0)
	{
		$conditions = array(
				'delete_flg'   => 0,
				'project_type' => array(
						PROJECT_TYPE_NORMAL,
						PROJECT_TYPE_INFORMAL
				),
				'budget_type' => BUDGET_TYPE_CONTENTS,
		);
		// 担当営業
		if ($member_id)
		{
			$conditions['member_id'] = $member_id;
		}
		// その他条件
		$date_ymd = date("Y-m-d", strtotime($date));
		$custom_conditions = array(
				"(end_date IS NULL OR end_date >= '".$date_ymd."')",
		);
		
		// キーワード		if ($keyword)
		{
			$custom_conditions[] = " (project_code like '%{$keyword}%' OR name like '%{$keyword}%')";
		}
		
		// プロジェクト取得
		$obj_project = new Project();
		$project_list = $obj_project->getDataByConditions($conditions, $custom_conditions, $order);
		
		// コスト工数集計
		$project_total_list = $this->createRiscProjectList($date, $project_list);

		// 抽出タイプ毎の条件でフィルタ
		$filter_project_total_list = array();
		foreach ($project_total_list as $project_total)
		{
			switch ((string) $type)
			{
				// 使用済コスト工数率：総割当コスト工数未設定は対象外
				case 'used':
					if ($project_total['total_cost_manhour'] != null)
					{
						if ((float) $used_rate <= (float) $project_total['used_manhour_rate'])
						{
							$filter_project_total_list[] = $project_total;
						}
					}
					break;
				// 超過予想コスト工数：総割当コスト工数、開発終了期間未設定は対象外
				case 'over':
					if ($project_total['project_end_date'] != null && $project_total['total_cost_manhour'] != null)
					{
						$filter_project_total_list[] = $project_total;
					}
					break;
				// 全て
				case 'all':
					$filter_project_total_list[] = $project_total;
					break;
				// その他 起こりえない
				default:
					break;
			}
			
		}
		return $filter_project_total_list;
	}

	/**
	 * コスト工数を集計して返却
	 *
	 * @param 	datetime	$date	基準日
	 * @param	array	$project_list	プロジェクト一覧
	 * @return	array	プロジェクトマスタデータ
	 */
	function createRiscProjectList($date, $project_list)
	{
		// 該当プロジェクトの対象期間の工数取得
		$project_ids = array_keys($project_list);
		$start_date = date("Y-m-d 00:00:00", strtotime('-28 day', strtotime($date)));
		$end_date = date("Y-m-d 00:00:00", strtotime('-1 day', strtotime($date)));
		
		$project_total_list = $this->manhourTotalRenge($project_list, $start_date, $end_date);
		
		// 超過予測コスト工数・使用済コスト工数率の算出
		//
		// ・使用済コスト工数率:総割当コスト工数が設定されている場合のみ算出
		// ・超過予測コスト工数:総割当コスト工数・開発終了日が設定済みの場合算出
		//
		foreach ($project_total_list as $key => &$project_total)
		{
			// 残工数算出
			$project_total['total_remains_manhour'] = $project_total['total_cost_manhour'] - $project_total['use_cost_manhour'];
			
			// 総割当コスト工数に設定がある場合は超過予測工数・使用済工数率の算出へ
			if ($project_total['total_cost_manhour'] != NULL)
			{
				// 総割当コスト工数が0だった場合
				if ($project_total['total_cost_manhour'] == 0)
				{
					$project_total['used_manhour_rate'] = null;
				}
				// 使用済コスト工数が0だった場合
				else if ($project_total['use_cost_manhour'] == 0)
				{
					$project_total['used_manhour_rate'] = 0;
				}
				else
				{
					// 使用済コスト工数率=総コスト工数/総割当コスト工数
					$project_total['used_manhour_rate'] = ceil($project_total['use_cost_manhour'] / $project_total['total_cost_manhour'] * 100 * 10) / 10;
				}

				// 開発終了日が設定されている場合は超過予想コスト工数を算出
				if ($project_list[$key]['project_end_date'] && $project_list[$key]['project_end_date'] >= date("Y-m-d", strtotime($date)))
				{
					$project_total['expected_over_manhour'] = $this->getExpectedOverManhour($date, $project_total);
				}
			} 
		}
		return $project_total_list;
	}

	/**
	 * 使用済みコスト工数を集計しプロジェクトマスタに反映
	 *
	 * @param 	datetime	$date	基準日
	 * @return	結果 
	 */
	public function usedCostTotal($date)
	{
		// --------------------------------------
		// 基準日時点で有効なプロジェクトを取得
		// --------------------------------------
		$conditions = array(
				'delete_flg'   => 0,
				'project_type' => array(
						PROJECT_TYPE_NORMAL,
						PROJECT_TYPE_INFORMAL
				),
		);
		// プロジェクト取得
		$obj_project = new Project();
		$project_list = $obj_project->getDataByConditions($conditions);
		
		$end_date = date("Y-m-d", strtotime('-1 day', strtotime($date)));
		$project_total_list = $this->manhourTotalRenge($project_list, null, $end_date);
		
		// --------------------------------------
		// 集計値をプロジェクトマスタに反映
		// --------------------------------------
		$obj_project->instance_db->beginTransaction();
		foreach ($project_total_list as &$project_total)
		{
			$update_columns = array(
					'use_cost_manhour' => ceil($project_total['total_man_hour_target'] / $project_total['mst_member_cost']),
					'update_date'      => $date,
			);
			$obj_project->updateProject($project_total['id'], $update_columns);
		}
		$obj_project->instance_db->commit();
	}
	
	/**
	 * 指定期間でのプロジェクトのコスト工数を集計
	 *
	 * @param 	array	$date	基準日
	 * @param	array	$project_list	プロジェクト一覧
	 * @return	array	プロジェクトマスタデータ
	 */
	public function manhourTotalRenge($project_list, $start_date=null, $end_date=null) 
	{
		$obj_manhour = new Manhour();
		
		// 該当プロジェクトの工数情報を全取得
		$project_ids = array_keys($project_list);
		if ($start_date)
		{
			$start_date = date("Y-m-d", strtotime($start_date));
		}
		if ($end_date)
		{
			$end_date = date("Y-m-d", strtotime($end_date));
		}
		$manhour_list = $obj_manhour->getRengeDataByProjectIds($project_ids, $start_date, $end_date);
		
		// 社員コストマスタを取得しキャッシュ
		$cost_obj = new MemberCost();
		$cost_list = $cost_obj->getDataAll();
		$cost_cache = array();
		foreach($cost_list as $cost)
		{
			$cost_cache[$cost['id']] = $cost['cost'];
		}
		
		// ユーザ毎作業コストをキャッシュ
		$member_obj = new Member();
		$member_list = $member_obj->getMemberAll(true);
		$member_cost_cache = array();
		foreach($member_list as $member)
		{
			$member_cost_cache[$member['id']] = $cost_cache[$member['mst_member_cost_id']];
		}
		
		// ----------------------
		// 集計
		// ----------------------
		// 各初期値設定
		$project_total_list = array();
		foreach ($project_list as $project)
		{
			$project_total = array(
					'total_man_hour_target' => 0,
					'used_manhour_rate'     => 0,
					'expected_over_manhour' => 0,
					'mst_member_cost_id'    => $project['mst_member_cost_id'],
					'mst_member_cost'       => $cost_cache[$project['mst_member_cost_id']],
					'project_code'          => $project['project_code'],
					'name'                  => $project['name'],
					'total_cost_manhour'    => $project['total_cost_manhour'],
					'use_cost_manhour'      => $project['use_cost_manhour'],
					'project_end_date'      => $project['project_end_date'],
					'member_id'             => $project['member_id'],
					'name'                  => $project['name'],
					'id'                    => $project['id'],
			);
			$project_total_list[$project['id']] = $project_total;
		}
		
		// 工数データを集計
		foreach ($manhour_list as $manhour)
		{
			// end_project_id に値が格納されている場合はそちらをプロジェクトIDとする
			if (!empty($manhour['end_project_id']))
			{
				$manhour['project_id'] = $manhour['end_project_id'];
			}
			// 有効でないプロジェクトの作業時間は無視
			if (!isset($project_total_list[$manhour['project_id']]))
			{
				continue;
			}
			// 使用コストを算出し加算
			$project_total = $project_total_list[$manhour['project_id']];
			$project_total['total_man_hour_target'] += $manhour['man_hour'] * $member_cost_cache[$manhour['member_id']];
			$project_total_list[$manhour['project_id']] = $project_total;
		}
		return $project_total_list;
	}
	
	/**
	 * 超過予測コスト工数を算出し返却
	 *
	 * @param 	string	$date	基準日
	 * @param	array	$project_list	プロジェクト情報
	 * 
	 * @return	array	超過予測コスト工数
	 */
	public function getExpectedOverManhour($date, $project_total)
	{
		// 週平均稼働コスト工数
		// 直近１ヶ月の「週平均稼働コスト工数」を出す（正確には過去7日*4週分から単純に4日割りして算出）
		// そしてその値をプロジェクトマスタの「基準コスト」で割る
		$manhour_avg_week = $project_total['total_man_hour_target'] / 4 / $project_total['mst_member_cost'];
		
		// 現時点から開発終了期間までの「残週数」を算出（小数第一位より以下は切り上げ）
		$remaining_weeks = $this->getRemainingWeeks($date, $project_total['project_end_date']);

		// 「週平均稼動コスト工数 * 残週数」－「残コスト工数(=総割当コスト工数-総コスト工数)」＝「超過予想コスト工数」
		$expected_over_manhour = $manhour_avg_week * $remaining_weeks - ($project_total['total_cost_manhour'] - $project_total['use_cost_manhour']);
		
		// 切り上げ
		$expected_over_manhour = $expected_over_manhour * 100;
		$expected_over_manhour = ceil($expected_over_manhour);
		
		return $expected_over_manhour / 100;
	}

	/**
	 * 残週数を返却 四捨五入し少数点1位までの値を返却
	 *
	 * @param 	datetime	$date	基準日
	 * @param	string	$project_end_date プロジェクト終了日時
	 * @return	array	プロジェクトマスタデータ
	 */
	public function getRemainingWeeks($today, $end_date)
	{
		$today = date("Y-m-d 00:00:00", strtotime($today));
		$end_date = date("Y-m-d 23:59:59", strtotime($end_date));
		$day_diff = (strtotime($end_date) - strtotime($today));
		return ceil(ceil($day_diff / (3600 * 24)) / 7 * 10) / 10;
	}
}