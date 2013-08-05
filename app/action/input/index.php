<?php
/**
 * 工数入力　入力画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Manhour.php");
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . '/class/common/dbaccess/Holiday.php');

class _input_index extends PostAndGetScene
{
	//出力用
	var $view_list;			// 表示リスト
	var $date_unixtime;		// 編集日付のUNIXTIME（終了案件判別用）
	var $arr_youbi;			// 編集日付の曜日
	var $holidays;			// 休日設定

	var $now_project;		// 通常プロジェクトリスト
	var $end_project;		// 終了プロジェクトリスト
	var $project_list;		// プロジェクトマスタ情報

	var $arr_manhour;		// 月の工数合計(日毎)

	var $start_year;		// 年のプルダウン値設定
	var $loop_year;			// 年のプルダウン値設定

	var $new_flg;			// 新規登録フラグ

	// 内部制御用
	var $session_error_flg = false;	// SESSION取得エラー


	// パラメータ
	var	$_work_year;			// 編集日付
	var	$_work_month;			//
	var	$_work_day;				//

	var $_session_flg	= 0;	// SESSION使用フラグ

	var $_ref_flg		= 0;	// 引用使用フラグ
	var $_ref_type		= 1;	// 1:所属プロジェクト参照、2:引用日付参照
	var	$_ref_year;				// 引用日付
	var	$_ref_month;			//
	var	$_ref_day;				//

	var	$_res			= '';		// 登録処理結果（1:登録処理成功(登録/更新)、2:登録処理成功(全削除)、3:何らかのエラー発生）
	var	$_error			= '';		// エラー内容（$this->_resが3の時に画面に表示される

	function check()
	{
		// SESSIONから編集日付取得の場合
		if ($this->_session_flg)
		{
			// SESSIONから表示情報を取得する場合はSESSIONから日付情報を取得
			// 編集日付の取得
			if(isset($_SESSION['manhour']['input']['manhour_view_list_date']))
			{
				$date	= $_SESSION['manhour']['input']['manhour_view_list_date'];
			}
			else
			{
				// セッションから取得失敗時は当日日付で表示
				$date						= date('Y-m-d');
				$this->session_error_flg	= true;	// SESSIONから取得の時は取得解除
				// エラーメッセージ
				$this->_res					= 3;
				$this->_error['work_date']	= '編集日付が不正です。本日データを表示します。';
			}
			$date_array	= explode('-', $date);
			$this->_work_year	= (int)(isset($date_array[0]) ? $date_array[0] : 0);
			$this->_work_month	= (int)(isset($date_array[1]) ? $date_array[1] : 0);
			$this->_work_day	= (int)(isset($date_array[2]) ? $date_array[2] : 0);
		}
		// パラメータから編集日付取得の場合
		else
		{
			// 編集日付未指定の遷移の場合は当日日付をセット
			if (empty($this->_work_year) || empty($this->_work_month) || empty($this->_work_day))
			{
				$this->_work_year	= date('Y');
				$this->_work_month	= date('n');
				$this->_work_day	= date('j');
			}
		}

		// 編集日付チェック
		if ((empty($this->_work_year) || empty($this->_work_month) || empty($this->_work_day)) ||
			!checkdate($this->_work_month,$this->_work_day,$this->_work_year))
		{
			// 編集日付エラー時は当日日付を強制セット
			$this->_work_year	= date('Y');
			$this->_work_month	= date('n');
			$this->_work_day	= date('j');

			$this->session_error_flg	= true;		// SESSIONから取得の時は取得解除
			$this->_ref_flg				= 0;		// 引用指定があった場合は引用解除

			// エラーメッセージ
			$this->_res					= 3;
			$this->_error['work_date']	= '編集日付が不正です。本日データを表示します。';

		}

		// 引用日付チェック
		if ((empty($this->_ref_year) || empty($this->_ref_month) || empty($this->_ref_day)) ||
			!checkdate($this->_ref_month,$this->_ref_day,$this->_ref_year))
		{
			// 引用日付が未設定orエラーは当日日付を初期値セット
			$this->_ref_year	= date('Y');
			$this->_ref_month	= date('n');
			$this->_ref_day		= date('j');

			// 引用日付で引用表示を行う時はエラー処理
			if (($this->_ref_flg) && ($this->_ref_type == 2))
			{
				$this->_ref_flg				= 0;		// 引用指定があった場合は引用解除
				// エラーメッセージ
				$this->_res					= 3;
				$this->_error['ref_date']	= '引用日付が不正のため、引用に失敗しました。';
			}
		}

		if (!$this->_ref_flg && !$this->_session_flg)
		{
			// 引用遷移や編集中の遷移以外(=編集日付指定遷移の時)は引用情報をリセット
			$this->_ref_type = 1;
			$this->_ref_year	= date('Y');
			$this->_ref_month	= date('n');
			$this->_ref_day		= date('j');
		}

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_manhour		= new Manhour;
		$obj_client			= new Client;
		$obj_project		= new Project;
		$obj_project_team	= new ProjectTeam;

		// 表示社員取得
		$member_id = $_SESSION["member_id"];

		// 編集日付のUNIXTIME取得
		$this->date_unixtime	= mktime(0,0,0,$this->_work_month,$this->_work_day,$this->_work_year);
		// 表示曜日取得
		$this->arr_youbi = getYoubi($this->_work_year,$this->_work_month,$this->_work_day);

		// プルダウン選択可能年度の最小～最大
		$now_year = date('Y');
		$this->start_year = $now_year - 5;
		$this->loop_year = $now_year + 1;

		// プロジェクト一覧取得
		$this->project_list	= $obj_project->getDataAll(false,true);		// 削除以外の全プロジェクトデータ&endunixtime付加
		$this->now_project	= $obj_project->getNowProject(array(PROJECT_TYPE_NORMAL,PROJECT_TYPE_INFORMAL),$this->_work_year,$this->_work_month,$this->_work_day);	// プロジェクトタイプ：通常/仮登録のみ
		$this->end_project	= $obj_project->getEndProject(array(PROJECT_TYPE_NORMAL,PROJECT_TYPE_INFORMAL),$this->_work_year,$this->_work_month,$this->_work_day);	// プロジェクトタイプ：通常/仮登録のみ


		// 編集日付で現在のDB情報を取得
		$arr_regdata = $obj_manhour->checkInputManhour($member_id,$this->_work_year,$this->_work_month,$this->_work_day);

		// 新規登録判別フラグ
		$this->new_flg =false;
		if (empty($arr_regdata))
		{
			$this->new_flg = true;
		}

		// 表示リストデータ取得
		$tmp_manhour_list = array();
		if ($this->_session_flg && !$this->session_error_flg)
		{
			//---------------------------
			// 編集中(行追加削除、エラー戻り)、登録完了時の遷移の時
			//---------------------------
			if (isset($_SESSION['manhour']['input']['manhour_view_list']))
			{
				// $_SESSION['manhour']['input']['manhour_view_list']より表示リストを生成
				$tmp_manhour_list = $_SESSION['manhour']['input']['manhour_view_list'];
			}
		}
		else
		{
			//---------------------------
			// 日付指定or未指定遷移、引用・所属ボタン遷移、SESSIONから取得失敗の時、その他エラー発生時
			//---------------------------
			if (!$this->new_flg)
			{
				// 工数登録済の日付の時は必ず編集日付からDBの値を取得して表示（引用指定があっても無視）
				foreach($arr_regdata as $pkey => $var_default)
				{
					$work_array = array(
						'project_id'	=> $var_default['project_id'],
						'end_project_id'=> $var_default['end_project_id'],
						'memo'			=> $var_default['memo'],
						'man_hour'		=> $var_default['man_hour']+0,
					);
					array_push($tmp_manhour_list, $work_array);
				}
				// セッションに登録されているリストの引用日をセットする

				// 工数登録済の日付に対して引用指定していた場合はエラー
				if ($this->_ref_flg)
				{
					$this->_ref_flg				= 0;		// 引用指定があった場合は引用解除
					$this->_res					= 3;
					$this->_error['ref_flg']	= '既に登録されている日付のため、引用に失敗しました。';
				}
			}
			else
			{
				// 未登録の日付の時
				if (($this->_ref_flg) && $this->_ref_type == 2)
				{
					// 編集日付が未登録且つ日付引用指定の時
					$arr_refdata = $obj_manhour->checkInputManhour($member_id,$this->_ref_year,$this->_ref_month,$this->_ref_day);
					if (!empty($arr_refdata))
					{
						foreach($arr_refdata as $pkey => $var_default)
						{
							$work_array = array(
								'project_id'	=> $var_default['project_id'],
								'end_project_id'=> $var_default['end_project_id'],
								'memo'			=> '',		// 他の日付から引用時は初期値クリア
								'man_hour'		=> 0,		// 他の日付から引用時は初期値クリア
							);

							array_push($tmp_manhour_list, $work_array);
						}
					}
				}
				else
				{
					// 編集日付が未登録or編集日付が未登録且つ所属引用指定の時
					// 所属プロジェクトから生成
					$arr_project_team = $obj_project_team->getDataByMemberId($member_id);
					foreach($arr_project_team as $pkey => $var_default)
					{
						$work_array = array(
							'project_id'	=> $var_default['project_id'],
							'end_project_id'=> '',
							'memo'			=> '',
							'man_hour'		=> 0,
						);
						array_push($tmp_manhour_list, $work_array);
					}

				}
			}
		}

		// 表示リスト生成
		$manhour_list	= array();	// 表示リスト
		$row_no			= 0;		// KEY値 兼 行番号
		if(is_array($tmp_manhour_list))
		{
			foreach($tmp_manhour_list as $key => $session_data)
			{
				// プロジェクト関連情報取得
				if($session_data['project_id'])
				{
					$client_data = $obj_client->getClientByProject($session_data['project_id']);
					if ($client_data)
					{
						$manhour_list[$row_no]['client_name'] = $client_data['name'];			// クライアント名
					}
					$project_data = $obj_project->getDataById($session_data['project_id']);
					if ($project_data)
					{
						$manhour_list[$row_no]['project_name'] = $project_data['name'];			// PJ名取得
					}
				}
				$manhour_list[$row_no]['man_hour']			= $session_data['man_hour'];		// 工数
				$manhour_list[$row_no]['memo']				= $session_data['memo'];			// 備考
				$manhour_list[$row_no]['project_id']		= $session_data['project_id'];		// プロジェクトのID
				$manhour_list[$row_no]['end_project_id']	= $session_data['end_project_id'];	// 後発作業プロジェクトのID
				$manhour_list[$row_no]['line']				= $row_no;							// 行番号

				$row_no++;
			}
		}
		$this->view_list=$manhour_list;


		// 該当月の工数情報(日毎)取得
		$temp_manhour	= $obj_manhour->getSumDataByIdYearMonth($member_id,$this->_work_year,$this->_work_month);
		$this->arr_manhour	= array();
		foreach ($temp_manhour as $value)
		{
			$this->arr_manhour["{$value['work_day']}"] = $value['man_hour'];
		}

		// 該当月の休日情報取得
		//土日祝日埋め込み（0:平日、1:土曜、2:日曜）
		$this->holidays	= getWeekendsHolidays($this->_work_year, $this->_work_month);
		// マスタ設定休日取得（3:指定休日で上書き）
		$obj_holiday = new Holiday;
		$arr_mst_holiday = $obj_holiday->getData($this->_work_year,$this->_work_month);
		if (!empty($arr_mst_holiday))
		{
			foreach ($arr_mst_holiday as $value)
			{
				if ((int)($value['holiday_year'])  == $this->_work_year &&
					(int)($value['holiday_month']) == $this->_work_month)
				{
					$this->holidays[$value['holiday_day']] = 3;
				}
			}
		}

	}
}

?>