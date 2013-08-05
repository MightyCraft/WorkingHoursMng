<?php
/**
 *	プロジェクト　編集　実行
 */
require_once(DIR_APP . '/class/common/dbaccess/Project.php');
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . '/class/common/dbaccess/MemberCost.php');
require_once(DIR_APP . '/class/common/dbaccess/Member.php');
class _project_edit_do extends PostScene
{
	var $_type;							// プロジェクト一覧に「戻る」ボタン用

	var	$_id;

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

	// 処理用
	var $obj_project;
	var $member_cost_data;

	function check()
	{
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
			'project_code'			=> $mm->sprintfMessage(MessageDefine::USER_ERR_MESSAGE_PROJECT_CODE),
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
				$error_msg[]	= '開始日付には存在する日付を入力して下さい。';
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
				$error_msg[]	= '終了日付には存在する日付を入力して下さい。';
			}
		}
		if($start_time > $end_time)
		{
			$error_msg[]	= '開始日付は終了日付より以前の日に設定して下さい。';
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

		$this->obj_project = new Project;

		//名前重複チェック
		$project_by_name = $this->obj_project->getProjectByName($this->_name, false, true);		//プロジェクトリスト取得
		if(!empty($project_by_name))
		{
			foreach($project_by_name as $project_data)
			{
				if ($project_data['id'] != $this->_id)
				{
					//重複且つ自身のデータ以外が存在したらエラー
					$error_msg[]	= '同じプロジェクト名が既に登録されています。';
					break;
				}
			}
		}

		//PJコード重複チェック
		$project_by_code = $this->obj_project->getProjectByCode($this->_project_code, false);	//プロジェクトリスト取得
		$flg_checkcode = false;
		if (!empty($project_by_code))
		{
			foreach($project_by_code as $project_data)
			{
				if ($project_data['id'] != $this->_id)
				{
					//重複且つ自身のデータ以外が存在したらエラー
					$flg_checkcode = true;
				}
			}
		}
		if($flg_checkcode)
		{
			$error_msg[]	= '同じPJコードが既に登録されています。';
		}

		// PJコードタイプチェック
		// 後発作業用コードのデータが登録済みかどうか
		if ($this->_project_type == PROJECT_TYPE_BACK && $this->obj_project->getDataByType(PROJECT_TYPE_BACK))
		{
			//登録済み
			$error_msg[] = 'PJコードタイプが後発作業用コードのデータが既に存在するため登録できません。';
		}
		// 設定されている値を使用しているか
		$array_project_type = returnArrayPJtype();
		if (!isset($array_project_type[$this->_project_type]))
		{
			$error_msg[] = 'PJコードタイプの値が不正です。';
		}

		// クライアントマスタ存在チェック
		if (!isset($errors['client_id']))
		{
			$obj_client = new Client;
			$client_data = $obj_client->getClientById($this->_client_id);
			if (empty($client_data))
			{
				$error_msg[] = '指定された顧客は存在しません。';
			}
		}

		// 担当営業チェック
		if (!isset($errors['member_id']) && $this->_member_id > 0)
		{
			$obj_member	= new Member();
			$member_data = $obj_member->getMemberById($this->_member_id,true);
			if (empty($member_data))
			{
				$error_msg[] = '指定された担当営業は社員データに存在しません。';
			}
		}

		// 予算タイプ
		$budget_type_list = returnArrayBudgetType();
		if (!isset($budget_type_list[$this->_budget_type]))
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
			$this->_return_flg = 1;
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/project/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// 登録値の調整

		// 総予算及び関連項目の設定
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
			$this->_total_budget = NULL;			// 総予算もNULLセット
		}
		// 開発開始日、開発終了日
		$project_start_time		= NULL;
		$project_end_time		= NULL;
		if(empty($this->_project_pending_start_date))
		{
			$project_start_time	= date('Y-m-d',mktime(0,0,0,$this->_project_start_date_month,$this->_project_start_date_day,$this->_project_start_date_year));
		}
		if(empty($this->_project_pending_end_date))
		{
			$project_end_time	= date('Y-m-d',mktime(0,0,0,$this->_project_end_date_month,$this->_project_end_date_day,$this->_project_end_date_year));
		}
		// 案件終了月
		$end_time		= NULL;
		if(empty($this->_pending_end_date))
		{
			$end_time	= date('Y-m-d',mktime(0,0,0,$this->_end_date_month,$this->_end_date_day,$this->_end_date_year));
		}
		// 納期
		$nouki			= NULL;
		if(!empty($this->_nouki))
		{
			$nouki = $this->_nouki;
		}
		// 備考必須フラグ
		$memo_flg		= 0;
		if (!empty($this->_memo_flg))
		{
			$memo_flg	= 1;
		}
		// 備考
		$memo 			= NULL;
		if (!empty($this->_memo))
		{
			$memo = $this->_memo;
		}

		// 更新実行
		$now_time	= date('Y-m-d H:i:s');
		$update_param	= array(
			'project_code'			=> $this->_project_code,
			'name'					=> $this->_name,
			'client_id'				=> $this->_client_id,
			'member_id'				=> $this->_member_id,
			'project_type'			=> $this->_project_type,
			'budget_type'			=> $this->_budget_type,
			'total_cost_manhour'	=> $this->_total_cost_manhour,
			'total_budget'			=> $this->_total_budget,
			'exclusion_budget'		=> $this->_exclusion_budget,
			'cost_budget'			=> $this->_cost_budget,
			'cost_rate'				=> $this->_cost_rate,
			'mst_member_cost_id'	=> $this->_mst_member_cost_id,
			'project_start_date'	=> $project_start_time,
			'project_end_date'		=> $project_end_time,
			'end_date'				=> $end_time,
			'nouki'					=> $nouki,
			'memo_flg'				=> $memo_flg,
			'memo'					=> $memo,
		);
		$res	= $this->obj_project->updateProject($this->_id,$update_param);

		if($res)
		{
			MCWEB_Util::redirectAction("/project/edit/complete?id={$this->_id}&type={$this->_type}");
		}
		else
		{
			MCWEB_InternalServerErrorException();
		}

	}
}

?>