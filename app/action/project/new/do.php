<?php
/**
 *	プロジェクト　新規作成　実行
 */
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _project_new_do extends PostScene
{
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

	function check()
	{
		$obj_project = new Project;

		//バリデートチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'name', ValidatorString::createInstance()->min(1)->max(USER_PROJECT_NAME_MAX)
			, 'client_id', ValidatorInt::createInstance()->min(1)
			, 'total_budget_manhour', ValidatorInt::createInstance()->min(0)->max(2147483647)
			, 'total_budget', ValidatorInt::createInstance()->min(0)->max(2147483647)
			, 'nouki', ValidatorString::createInstance()->nullable()->max(USER_PROJECT_NOUKI_MAX)
			, 'memo', ValidatorString::createInstance()->nullable()->max(USER_PROJECT_MEMO_MAX)
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
			'total_budget_manhour'	=> '総予算工数は(0～2147483647)の範囲内で入力して下さい。',
			'total_budget'			=> '総予算は(0～2147483647)の範囲内で入力して下さい。',
			'nouki'					=> '納期は'.USER_PROJECT_NOUKI_MAX.'文字以内で入力して下さい。',
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

		//時間チェック
		$start_time	=	0;
		$end_time	=	9999999999;
		if(empty($this->_project_pending_start_date))
		{
			$start_time	= mktime(0,0,0,$this->_project_start_date_month,$this->_project_start_date_day,$this->_project_start_date_year);
		}
		if(empty($this->_project_pending_end_date))
		{
			$end_time	= mktime(0,0,0,$this->_project_end_date_month,$this->_project_end_date_day,$this->_project_end_date_year);
		}
		if($start_time > $end_time)
		{
			$error_msg[]	= '開始日付は終了日付より以前の日に設定して下さい。';
		}

		//名前重複チェック
		$project_by_name = $obj_project->getProjectByName($this->_name, false, true);		//プロジェクトリスト取得
		if(!empty($project_by_name))
		{
			$error_msg[]	= '同じプロジェクト名が既に登録されています。';
		}

		//PJコード重複チェック
		$project_by_code = $obj_project->getProjectByCode($this->_project_code, false);	//プロジェクトリスト取得
		if(!empty($project_by_code))
		{
			$error_msg[]	= '同じPJコードが既に登録されています。';
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
			$obj_client = new Client;
			$client_data = $obj_client->getClientById($this->_client_id);
			if (empty($client_data))
			{
				$error_msg[] = '指定された顧客は存在しません。';
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
		$obj_project	= new Project;

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
		$end_time		= NULL;
		if(empty($this->_pending_end_date))
		{
			$end_time	= date('Y-m-d',mktime(0,0,0,$this->_end_date_month,$this->_end_date_day,$this->_end_date_year));
		}
		$nouki			= NULL;
		if(!empty($this->_nouki))
		{
			$nouki = $this->_nouki;
		}
		$memo_flg		= 0;
		if (!empty($this->_memo_flg))
		{
			$memo_flg	= 1;
		}
		$memo 			= NULL;
		if (!empty($this->_memo))
		{
			$memo = $this->_memo;
		}

		//登録実行
		$now_time	= date('Y-m-d H:i:s');
		$insert_param	= array(
			$this->_project_code,
			$this->_name,
			$this->_client_id,
			$this->_project_type,
			$this->_total_budget_manhour,
			$this->_total_budget,
			$project_start_time,
			$project_end_time,
			$end_time,
			$this->_member_id,
			$nouki,
			$memo_flg,
			$memo,
			$now_time,
			$now_time,
		);
		list($res,$insert_id)	= $obj_project->insertProject($insert_param);
		if($res)
		{
			MCWEB_Util::redirectAction("/project/new/complete?id={$insert_id}");
		}
		else
		{
			MCWEB_InternalServerErrorException();
		}
	}
}

?>