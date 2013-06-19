<?php
/**
 *	プロジェクト　編集　確認
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
class _project_edit_confirm extends PostScene
{
	var	$_id;
	// パラメータ
	var	$_project_code;					// プロジェクトコード
	var	$_name;							// プロジェクト名
	var	$_client_id;					// クライアントID
	var	$_project_type;					// プロジェクトタイプ
	var	$_total_budget;					// 総予算
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
	// 自動セット
	var	$_total_budget_manhour;	// 総割当工数
	var	$_end_date_day;			// 案件終了月の末日

	// クライアント情報
	var $obj_client;
	var $client_data;

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
			, 'name',			ValidatorString::createInstance()->min(1)->max(USER_PROJECT_NAME_MAX)
			, 'client_id',		ValidatorInt::createInstance()->min(1)
			, 'total_budget',	ValidatorInt::createInstance()->min(0)->max(2147483647)
			, 'nouki',			ValidatorString::createInstance()->nullable()->max(USER_PROJECT_NOUKI_MAX)
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
			'name'			=> 'プロジェクト名は'.USER_PROJECT_NAME_MAX.'文字以内で入力して下さい。',
			'client_id'		=> '顧客名を選択してください。',
			'total_budget'	=> '総予算は(0～2147483647)の範囲内で入力して下さい。',
			'nouki'			=> '納期は'.USER_PROJECT_NOUKI_MAX.'文字以内で入力して下さい。',
			'memo'			=> '備考は'.USER_PROJECT_MEMO_MAX.'文字以内（改行含む）で入力して下さい。',
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

		//時間チェック
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
			$error_msg[]	= '開始日付は終了日付より以前の日に設定して下さい';
		}

		//名前重複チェック
		$project_by_name = $obj_project->getProjectByName($this->_name, false, true);		//プロジェクトリスト取得
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
		if ($auto_error_flg)
		{
			// 新規で自動採番を行ったが重複しないコードが生成できなかった
			$error_msg[]	= 'PJコードのランダム生成に失敗しました。生成したPJコードと同じコードが存在します。';
		}
		else
		{
			$project_by_code = $obj_project->getProjectByCode($this->_project_code, false);	//プロジェクトリスト取得
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
				$error_msg[]	= '同じPJコードが既に登録されています';
			}
		}

		// PJコードタイプチェック
		// 後発作業用コードのデータが登録済みかどうか
		if ($this->_project_type == PROJECT_TYPE_BACK && $obj_project->getDataByType(PROJECT_TYPE_BACK))
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
			$this->obj_client = new Client;
			$this->client_data = $this->obj_client->getClientById($this->_client_id);
			if (empty($this->client_data))
			{
				$error_msg[] = '指定された顧客は存在しません。';
			}
		}

		//エラー
		if(!empty($error_msg))
		{
			$this->_error = $error_msg;
			$f = new MCWEB_SceneForward('/project/edit/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_maintenance	= new MasterMaintenance;

		//クライアントリスト取得
		$access->text('client_data', $this->client_data);

		//PJコードタイプリスト
		$array_project_type = returnArrayPJtype();
		$access->text('array_project_type', $array_project_type);

		// 総割当工数計算
		$this->_total_budget_manhour = getTotal_budget_manhour($this->_total_budget);

		// 案件終了月に末日をセットして表示
		$date = $this->_end_date_year.'-'.$this->_end_date_month.'-01';
		$this->_end_date_day = getMonthEnd($date);

		// アカウントリスト取得(営業のみ)
		$access->text('member_list', $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES));

	}
}

?>