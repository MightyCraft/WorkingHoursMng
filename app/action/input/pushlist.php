<?php
/**
 * 「工数入力画面」にて「新規追加」ボタンをクリック時の処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Manhour.php");
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");

class _input_pushlist extends PostScene
{
	// 新規行追加用パラメータ
	var $_now_project	= 0;	// 追加するプロジェクトID（通常案件）
	var $_end_project	= 0;	// 　　　　　　　　　　　（終了案件）
	var $_end_check;			// 後発表示フラグ

	// 現在画面表示されている工数リスト
	var $_project_id;		// プロジェクトIDリスト
	var $_end_project_id;	// 後発作業扱いのプロジェクトIDリスト
	var $_man_hour;			// 工数
	var $_memo;				// メモ

	// その他画面に表示されている情報
	var $_work_year;	// 編集日付
	var $_work_month;	//
	var $_work_day;		//
	var $_ref_type;		// 引用情報
	var	$_ref_year;		//
	var	$_ref_month;	//
	var	$_ref_day;		//

	function check()
	{
		// エラーチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'now_project',	ValidatorString::createInstance()
			, 'end_project',	ValidatorString::createInstance()
			, 'work_year',		ValidatorString::createInstance()->min(1)->max(4)
			, 'work_month',		ValidatorString::createInstance()->min(1)->max(2)
			, 'work_day',		ValidatorString::createInstance()->min(1)->max(2)
		);

		// 登録プロジェクトが選択されてない場合
		if (empty($this->_now_project) && empty($this->_end_project))
		{
			$errors['add_project'] = 'empty';
		}
		// プロジェクトIDチェック
		if (!preg_match("/^[0-9]+$/",$this->_now_project))
		{
			$errors["add_project"] = 'error_now_id';
		}
		if (!preg_match("/^[0-9]+$/",$this->_end_project))
		{
			$errors["add_project"] = 'error_end_id';
		}
		// 修正年月の正当性チェック
		if (!checkdate($this->_work_month,$this->_work_day,$this->_work_year))
		{
			$errors["date"] = 'error';
		}

		// 登録指定日時のセッション更新＆正規化
		if(	!empty($this->_work_year)	&&
			!empty($this->_work_month)	&&
			!empty($this->_work_day) )
		{
			unset($_SESSION['manhour']['input']['manhour_view_list_date']);
			$_SESSION['manhour']['input']['manhour_view_list_date']	= date('Y-m-d',mktime(0,0,0,$this->_work_month,$this->_work_day,$this->_work_year));
			//登録指定日時の正規化
			$dates	= getdate(strtotime($_SESSION['manhour']['input']['manhour_view_list_date']));
			$this->_work_year	= $dates['year'];
			$this->_work_month	= $dates['mon'];
			$this->_work_day	= $dates['mday'];
		}

		// 追加前に画面から入力された内容をSESSIONに保持（入力画面に戻す用）
		unset($_SESSION['manhour']['input']['manhour_view_list']);
		if(is_array($this->_project_id))
		{
			// 委譲前にセッションの更新
			$_SESSION['manhour']['input']['manhour_view_list']	= array();
			foreach($this->_project_id as $key => $project_id)
			{
				$work_array = array(
					'project_id'	=> $project_id,
					'end_project_id'=> $this->_end_project_id[$key],
					'memo'			=> $this->_memo[$key],
					'man_hour'		=> $this->_man_hour[$key]+0,
				);
				array_push($_SESSION['manhour']['input']['manhour_view_list'], $work_array);
			}
		}

		// エラーの時
		if (!empty($errors))
		{
			$this->_session_flg	= 1;	// SESSIONから画面表示させる
			$this->_res			= 3;	// エラー
			if (!empty($errors['work_year']) || !empty($errors['work_month']) || !empty($errors['work_day']) || !empty($errors['date']))
			{
				$this->_error[] = '登録指定日が不正です。';
			}
			if (!empty($errors['now_project']) || !empty($errors['end_project']) || !empty($errors['add_project']))
			{
				$this->_error[] = '新規追加に指定したプロジェクト情報が不正です。';
			}
			$f = new MCWEB_SceneForward('/input/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_manhour = new Manhour;
		$obj_client  = new Client;
		$obj_project = new Project;

		// エラーチェック
		$errors = array();
		// プロジェクトマスタ存在チェック
		if (empty($this->_end_check))
		{
			$add_project_data	= $obj_project->getDataById($this->_now_project);
		}
		else
		{
			$add_project_data	= $obj_project->getDataById($this->_end_project);
		}
		if (empty($add_project_data))
		{
			$errors[] = '新規追加に指定したプロジェクト情報が不正です。';
		}
		// 既に登録済行に同じPJコードがあるかチェック
		if (checkUseProjectTypeBack())
		{
			// 後発作業コード環境
			$back_project = $obj_project->getDataByType(PROJECT_TYPE_BACK);
			if (is_array($_SESSION['manhour']['input']['manhour_view_list']))
			{
				foreach($_SESSION['manhour']['input']['manhour_view_list'] as $value)
				{
					// 「project_id」に後発作業用コード以外が登録されている時だけ「project_id」とチェックを行う
					if ((!empty($value['project_id']) &&  $value['project_id'] != $back_project[0]['id']) &&
						($value['project_id'] == $add_project_data['id']))
					{
						$errors[] = '既に行に追加されているプロジェクトです。';
					}
					// 後発作業用コード使用時は「end_project_id」にチェック対象値がセットされている為に「end_project_id」とチェックを行う
					if ((!empty($value['end_project_id']) &&  $value['end_project_id'] != $back_project[0]['id']) &&
						($value['end_project_id'] == $add_project_data['id']))
					{
						$errors[] = '既に行に追加されているプロジェクトです。';
					}
				}
			}
		}
		else
		{
			// 通常環境
			if (is_array($_SESSION['manhour']['input']['manhour_view_list']))
			{
				foreach($_SESSION['manhour']['input']['manhour_view_list'] as $value)
				{
					// 登録済み行のプロジェクトIDが未設定or後発用コード以外の時にチェック
					if ((!empty($value['project_id'])) && ($value['project_id'] == $add_project_data['id']))
					{
						$errors[] = '既に行に追加されているプロジェクトです。';
					}
				}
			}
		}

		if (!empty($errors))
		{
			$this->_session_flg	= 1;	// SESSIONから画面表示させる
			$this->_res			= 3;		// エラー
			$this->_error		= $errors;
			$f = new MCWEB_SceneForward('/input/index');
			$f->regist('FORWARD', $this);
			return $f;
		}


		// 新規追加OKの時、追加されたPJコードをSESSIONの保持している表示一覧に追加
		$work_array = array(
						'project_id'	=> $add_project_data['id'],	// TODO: ブラッシュアップ
						'end_project_id'=> 0,
						'memo'			=> '',
						'man_hour'		=> 0,
					);
		if(is_array($_SESSION['manhour']['input']['manhour_view_list']) )	array_push($_SESSION['manhour']['input']['manhour_view_list'],$work_array);
		else											$_SESSION['manhour']['input']['manhour_view_list'][0]=$work_array;

		MCWEB_Util::redirectAction("/input/index?session_flg=1&ref_type={$this->_ref_type}&ref_year={$this->_ref_year}&ref_month={$this->_ref_month}&ref_day={$this->_ref_day}");
		exit;
	}
}
?>