<?php
/**
 *	プロジェクト　新規作成　確認
 */
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/MemberCost.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _project_new_confirm extends PostScene
{
	// パラメータ
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	var	$_project_code;					// プロジェクトコード
	var	$_name;							// プロジェクト名
	var	$_client_id;					// クライアントID
	var	$_project_type;					// プロジェクトタイプ
	var	$_budget_type;					// 予算タイプ
	var	$_total_budget;					// 総予算
	var	$_exclusion_budget;				// コスト管理外予算
	var	$_cost_rate;					// 原価率
	var	$_mst_member_cost_id;			// 基準社員コストID
	var	$_project_start_date_year;		// 開発開始日付
	var	$_project_start_date_month;		//
	var	$_project_start_date_day;		//
	var	$_project_pending_start_date;	//
	var	$_project_end_date_year;		// 開発終了日付
	var	$_project_end_date_month;		//
	var	$_project_end_date_day;			//
	var	$_project_pending_end_date;		//
	var	$_end_date_year;				// 終了案件切り替え日
	var	$_end_date_month;				//
	var	$_pending_end_date;				//
	var $_nouki;						// 納期
	var $_memo_flg;						// 工数入力時備考必須フラグ
	var $_member_id;					// 担当営業
	var $_memo;							// 備考

	// 自動セット項目
	var	$_total_cost_manhour;	// 総割当工数
	var $_use_cost_manhour;		// 使用済コスト工数
	var $_cost_budget;			// コスト予算
	var	$_end_date_day;			// 案件終了月の末日

	// 画面表示用
	var $project_type_list;
	var $client_data;
	var $member_data;
	var $budget_type_list;
	var $member_cost_data;

	function check()
	{
		$obj_project = new Project;

		//プロジェクトコードの自動採番
		$auto_error_flg = false;
		if(($this->_project_type == PROJECT_TYPE_INFORMAL) && (empty($this->_project_code)))
		{
			// 自動採番で失敗した場合：重複チェックでエラーになったコードが$this->_project_codeにセットされます
			list($auto_error_flg,$this->_project_code) =  $obj_project->createRandomPJCode();
		}

		//バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'name',				ValidatorString::createInstance()->min(1)->max(USER_PROJECT_NAME_MAX)
			, 'client_id',			ValidatorInt::createInstance()->min(1)
			, 'total_budget',		ValidatorInt::createInstance()->nullable()->min(0)->max(2147483647)
			, 'exclusion_budget',	ValidatorInt::createInstance()->min(0)->max(2147483647)
			, 'cost_rate',			ValidatorInt::createInstance()->min(0)->max(100)
			, 'mst_member_cost_id',	ValidatorInt::createInstance()->min(1)
			, 'nouki',			ValidatorString::createInstance()->nullable()->max(USER_PROJECT_NOUKI_MAX)
			, 'member_id',			ValidatorInt::createInstance()->min(0)
			, 'memo',			ValidatorString::createInstance()->nullable()->max(USER_PROJECT_MEMO_MAX)
		);
		if (USER_PROJECT_CODE_FORMAT != '')
		{
			$project_code_error = ValidatorString::createInstance()->preg(USER_PROJECT_CODE_FORMAT)->min(USER_PROJECT_CODE_MIN)->max(USER_PROJECT_CODE_MAX)->validate($this->_project_code);
		}
		else
		{
			$project_code_error = ValidatorString::createInstance()->min(USER_PROJECT_CODE_MIN)->max(USER_PROJECT_CODE_MAX)->validate($this->_project_code);
		}
		if (!empty($project_code_error))
		{
			$errors['project_code'] = $project_code_error;
		}

		//エラー文言配列
		$mm = MessageManager::getInstance();
		$error_format_array = array(
			'project_code'	=> $mm->sprintfMessage(MessageDefine::USER_ERR_MESSAGE_PROJECT_CODE),
			'name'					=> 'プロジェクト名は'.USER_PROJECT_NAME_MAX.'文字以内で入力して下さい。',
			'client_id'				=> '顧客名を選択してください。',
			'total_budget'			=> '総予算は(0～2147483647)の範囲内で入力して下さい。',
			'exclusion_budget'		=> 'コスト管理外予算は(0～2147483647)の範囲内で入力して下さい。',
			'cost_rate'				=> '原価率は(0～100)の範囲内で入力して下さい。',
			'mst_member_cost_id'	=> '基準社員コストIDが選択されていません。',
			'nouki'					=> '納期は'.USER_PROJECT_NOUKI_MAX.'文字以内で入力して下さい。',
			'member_id'				=> '担当営業の指定が不正です。',
			'memo'					=> '備考は'.USER_PROJECT_MEMO_MAX.'文字以内（改行含む）で入力して下さい。',
		);
		//エラーチェック
		$error_msg	= array();
		foreach($errors as $error_key => $error_value)
		{
			if(isset($error_format_array[$error_key]))
			{
				$error_msg[]	= $error_format_array[$error_key];
			}
		}

		// 開発開始日付、開発終了日付チェック
		$start_time	=	0;
		$end_time	=	9999999999;
		if(empty($this->_project_pending_start_date))
		{
			$start_time	= mktime(0,0,0,$this->_project_start_date_month,$this->_project_start_date_day,$this->_project_start_date_year);
			$yyyy = sprintf("%04d", $this->_project_start_date_year);
			$mm = sprintf("%02d", $this->_project_start_date_month);
			$dd = sprintf("%02d", $this->_project_start_date_day);
			if(ValidatorDate::createInstance()->validate($yyyy.$mm.$dd))
			{
				$error_msg[]	= '開発開始日付には存在する日付を入力して下さい。';
			}
		}
		if(empty($this->_project_pending_end_date))
		{
			$end_time	= mktime(0,0,0,$this->_project_end_date_month,$this->_project_end_date_day,$this->_project_end_date_year);
			$yyyy = sprintf("%04d", $this->_project_end_date_year);
			$mm = sprintf("%02d", $this->_project_end_date_month);
			$dd = sprintf("%02d", $this->_project_end_date_day);
			if(ValidatorDate::createInstance()->validate($yyyy.$mm.$dd))
			{
				$error_msg[]	= '開発終了日付には存在する日付を入力して下さい。';
			}
		}
		if($start_time > $end_time)
		{
			$error_msg[]	= '開発開始日付は開発終了日付より以前の日に設定して下さい。';
		}

		// 案件終了月チェック
		if(empty($this->_pending_end_date))
		{
			$date = $this->_end_date_year.'-'.$this->_end_date_month.'-01';
			$this->_end_date_day = getMonthEnd($date);							// 案件終了月に末日をセットして表示

			$yyyy	= sprintf("%04d", $this->_end_date_year);
			$mm		= sprintf("%02d", $this->_end_date_month);
			$dd		= sprintf("%02d", $this->_end_date_day);
			if(ValidatorDate::createInstance()->validate($yyyy.$mm.$dd))
			{
				$error_msg[]	= '案件終了日付には存在する日付を入力して下さい。';
			}
		}

		//名前重複チェック
		$project_by_name = $obj_project->getProjectByName($this->_name, false, true);		//プロジェクトリスト取得
		if(!empty($project_by_name))
		{
			$error_msg[]	= '同じプロジェクト名が既に登録されています。';
		}

		//PJコード重複チェック
		if ($auto_error_flg)
		{
			// 自動採番で重複しないコードが生成できなかった
			$error_msg[]	= 'PJコードのランダム生成に失敗しました。生成したPJコードと同じコードが存在します。';
		}
		else
		{
			$project_by_code = $obj_project->getProjectByCode($this->_project_code, false);	//プロジェクトリスト取得
			if(!empty($project_by_code))
			{
				$error_msg[]	= '同じPJコードが既に登録されています。';
			}
		}

		// PJコードタイプチェック
		// 後発作業用コードのデータが登録済みかどうか
		if ($this->_project_type == PROJECT_TYPE_BACK && $obj_project->getDataByType(PROJECT_TYPE_BACK))
		{
			//使用されている
			$error_msg[] = 'PJコードタイプが後発作業用コードのデータが既に存在するため登録できません。';
		}
		// 設定されている値を使用しているか
		$this->project_type_list = returnArrayPJtype();
		if (!isset($this->project_type_list[$this->_project_type]))
		{
			$error_msg[] = 'PJコードタイプの値が不正です。';
		}

		// クライアントマスタ存在チェック
		if (!isset($errors['client_id']))
		{
			$obj_client = new Client;
			$this->client_data = $obj_client->getClientById($this->_client_id);
			if (empty($this->client_data))
			{
				$error_msg[] = '指定された顧客は存在しません。';
			}
		}

		// 担当営業チェック
		if (!isset($errors['member_id']) && $this->_member_id > 0)
		{
			$obj_member	= new Member();
			$this->member_data = $obj_member->getMemberById($this->_member_id,true);
			if (empty($this->member_data))
			{
				$error_msg[] = '指定された担当営業は社員データに存在しません。';
			}
		}

		// 予算タイプ
		$this->budget_type_list = returnArrayBudgetType();
		if (!isset($this->budget_type_list[$this->_budget_type]))
		{
			$error_msg[] = '予算タイプの値が不正です。';
		}

		// 基準社員コストID
		if (!isset($errors['mst_member_cost_id']))
		{
			$obj_member_cost = new MemberCost;
			$this->member_cost_data = $obj_member_cost->getDataById($this->_mst_member_cost_id);
			if (empty($this->member_cost_data))
			{
				$error_msg[] = '指定された基準社員コストデータは存在しません。';
			}
		}

		//エラー
		if(!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/project/new/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 総予算が設定されていたら関連項目を計算
		if (!empty($this->_total_budget))
		{
			// コスト予算
			$this->_cost_budget = calculateCostBudget($this->_total_budget,$this->_exclusion_budget,$this->_cost_rate);
			// 総割当コスト工数計算
			$this->_total_cost_manhour = calculateTotalCostManhour($this->_cost_budget, $this->member_cost_data['cost']);
		}
		else
		{
			// 総予算未設定時はNULLセット
			$this->_cost_budget = NULL;				// コスト予算
			$this->_total_cost_manhour = NULL;		// 総割当コスト工数計算

			// 総予算もNULLセット
			$this->_total_budget = NULL;
		}

		// 使用済コスト工数（日次集計項目）
		$this->_use_cost_manhour = NULL; // 初期値はNULL
	}
}

?>