<?php
/**
 * 工数入力　登録処理
 *
 * 処理後は入力画面へ戻る
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Manhour.php");
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");

class _input_confirm extends PostScene
{

	// パラメータ
	// 登録工数リスト
	var $_man_hour;			// 工数
	var $_project_id;		// 登録プロジェクトコード
	var $_end_project_id;	// 登録プロジェクトが後発作業時の終了案件コード
	var $_memo;				// 備考
	// 編集年月日
	var $_work_year;		// 編集年
	var $_work_month;		// 編集月
	var $_work_day;			// 編集日

	// 引用情報（エラー時の入力画面戻り用）
	var $_ref_type;	// 引用タイプ
	var	$_ref_year;	// 引用年
	var	$_ref_month;// 引用月
	var	$_ref_day;	// 引用日

	//出力用
	var $errmess;

	function check()
	{
		// エラーチェック
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'work_year',		ValidatorString::createInstance()->min(1)->max(4)
			, 'work_month',		ValidatorString::createInstance()->min(1)->max(2)
			, 'work_day',		ValidatorString::createInstance()->min(1)->max(2)
		);
		// 修正年月の正当性チェック
		if (!checkdate($this->_work_month,$this->_work_day,$this->_work_year))
		{
			$errors["date"] = 'error';
		}

		//登録指定日時更新
		if(	!empty($this->_work_year)	&&
			!empty($this->_work_month)	&&
			!empty($this->_work_day)	&&
			(checkdate($this->_work_month,$this->_work_day,$this->_work_year))
		  )
		{
			unset($_SESSION['manhour']['input']['manhour_view_list_date']);
			$_SESSION['manhour']['input']['manhour_view_list_date']	= date('Y-m-d',mktime(0,0,0,$this->_work_month,$this->_work_day,$this->_work_year));
			//登録指定日時の正規化
			$dates	= getdate(strtotime($_SESSION['manhour']['input']['manhour_view_list_date']));
			$this->_work_year	= $dates['year'];
			$this->_work_month	= $dates['mon'];
			$this->_work_day	= $dates['mday'];
		}
		else
		{
			// 登録指定日が未設定or無効日
			$errors['date'] = 'error';
		}

		// 全角→半角変換＆数値チェック
		if (!empty($this->_man_hour))
		{
			foreach ($this->_man_hour as $key => $hour_data)
			{
				$tmp_conv_hour = trim(mb_convert_kana($hour_data,'as','UTF-8'));
				if (empty($tmp_conv_hour))
				{
					$tmp_conv_hour = 0;
				}
				if (!is_numeric($tmp_conv_hour))
				{
					$errors['manhour'] = 'error';
				}
				$arr_conv_hour[$key] = $tmp_conv_hour;
			}
			$this->_man_hour = $arr_conv_hour;
		}

		// 画面から入力された内容をSESSIONに保持（入力画面に戻す用）
		unset($_SESSION['manhour']['input']['manhour_view_list']);
		if(is_array($this->_project_id))
		{
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
			if (!empty($errors['date']))
			{
				$this->_error[] = '登録指定日が不正です。';
			}
			if (!empty($errors['manhour']))
			{
				$this->_error[] = '工数が不正です。';
			}
			$f = new MCWEB_SceneForward('/input/index');
			$f->regist('FORWARD', $this);
			return $f;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

		$obj_manhour 	= new Manhour;
		$obj_client  	= new Client;
		$obj_project	= new Project;

		$errors = array();
		$member_id = $_SESSION["member_id"];

		//-----------------------
		// 入力データがある場合は入力データチェック
		//-----------------------
		if(is_array($this->_project_id))
		{
			// 編集年月日
			$now_date			= mktime(0,0,0,$this->_work_month,$this->_work_day,$this->_work_year);
			// 判定用その他案件の取得
			$array_memo_flg = $obj_project->getDataByMemoflg();
			$array_other[] = array();
			foreach ($array_memo_flg as $memo_project_id)
			{
				$array_other[] = (int)$memo_project_id['id'];
			}

			// エラーチェック
			// ※エラー内容は全行分は保持しない。
			foreach($this->_project_id as $key => $value)
			{
				// 工数範囲チェック（0時間～24時間）
				if((int)$this->_man_hour[$key] < 0 || $this->_man_hour[$key] > 24)
				{
					$errors['hour'] = 'over';
				}
				// 工数範囲チェック 少数桁数2桁を超えていないか？
				if(!isNumWithDecimal($this->_man_hour[$key],2))
				{
					$errors['hour'] = 'decimal';
				}

				// 備考チェック（入力があった場合は最大文字数以内がチェック）
				if ($this->_memo[$key] != '')
				{
					if (mb_strlen($this->_memo[$key],'UTF-8') > USER_MANHOUR_MEMO_MAX)
					{
						$errors['memo'] = 'over';
					}
				}

				// 後発、廃止、その他案件のチェック
				// 但し工数0時間は登録対象外になる為、エラーチェック対象外
				if($this->_man_hour[$key] != 0)
				{
					$detail_project = $obj_project->getDataById($this->_project_id[$key]);

					// マスタ存在チェック＆詳細チェック用プロジェクト情報の取得
					if (empty($detail_project))
					{
						$errors['project'] = 'null';
					}
					elseif ($detail_project['project_type']==PROJECT_TYPE_BACK)
					{
						// 後発作業用PJコードの時は$_end_project_idで取得
						$check_project		= $obj_project->getDataById($this->_end_project_id[$key]);
						if (empty($check_project))
						{
							$errors['project'] = 'null';
						}
					}
					else
					{
						// 後発作業以外の時は$_project_idで取得
						$check_project = $detail_project;
					}

					// マスタ存在時は後発、廃止、その他案件のチェック
					if (!empty($check_project))
					{
						// 案件終了月の取得
						$project_end_date	= '';
						if (!empty($check_project['end_date']))
						{
							$project_end_date	= strtotime($check_project['end_date']);
						}

						if ($detail_project['project_type']==PROJECT_TYPE_BACK)
						{
							// 登録するプロジェクトが後発作業用PJコードの時
							if ($check_project['project_type'] == PROJECT_TYPE_BACK)
							{
								$errors['project'] = 'error';	// end_project_idも後発用PJコードだったら不正
							}
							if ((empty($project_end_date)) || ($now_date <= $project_end_date))
							{
								$errors['project'] = 'active';	// 現時点のマスタ情報で終了案件扱いでは無くなっていた
							}
							if(empty($this->_memo[$key]))
							{
								$errors['memo'] = 'null_after';	// 備考が無かった
							}
						}
						else
						{
							// 登録するプロジェクトが後発作業以外の時
							if (!empty($this->_end_project_id[$key]))
							{
								$errors['project'] = 'error';	// end_project_idが設定されていたら不正
							}

							if (checkUseProjectTypeBack())
							{
								// 後発作業コード環境
								if (!empty($project_end_date) && ($now_date > $project_end_date) && empty($this->_memo[$key]))
								{
									$errors['memo'] = 'null_after';	// 終了した案件の時は備考必須
								}
							}
						}

						// 廃止案件の時はエラー
						if ((isset($check_project['project_type'])) && ($check_project['project_type']==PROJECT_TYPE_REMOVAL))
						{
							$errors['project_type'] = PROJECT_TYPE_REMOVAL;
						}

						// その他案件(備考必須案件)で備考が無ければエラー
						if (in_array($value,$array_other) &&  empty($this->_memo[$key]))
						{
							$errors['memo'] = 'null_other';
						}

						// 重複チェック
						if (checkUseProjectTypeBack())
						{
							// 後発作業コード環境
							$back_project = $obj_project->getDataByType(PROJECT_TYPE_BACK);	// 後発用PJコード情報取得
							$self_project_id		= $this->_project_id[$key];
							$self_end_project_id	= $this->_end_project_id[$key];
							foreach($this->_project_id as $key2 => $value2)
							{
								// 自身の行とはチェックを行わない
								if ($key != $key2)
								{
									$check_project_id		= $this->_project_id[$key2];
									$check_end_project_id	= $this->_end_project_id[$key2];
									// 「project_id」に後発作業用コード以外が登録されている時だけ「project_id」とチェックを行う
									if (!empty($self_project_id) &&  $self_project_id != $back_project[0]['id'])
									{
										if (($self_project_id == $check_project_id) || ($self_project_id == $check_end_project_id))
										{
											$errors['project'] = 'dabble';
										}
									}
									// 後発作業用コード使用時は「end_project_id」にチェック対象値がセットされている為に「end_project_id」ともチェックを行う
									if (!empty($self_end_project_id) &&  $self_end_project_id != $back_project[0]['id'])
									{
										if (($self_end_project_id == $check_project_id) || ($self_end_project_id == $check_end_project_id))
										{
												$errors['project'] = 'dabble';
										}
									}
								}
							}
						}
						else
						{
							// 通常環境
							$self_project_id		= $this->_project_id[$key];
							foreach($this->_project_id as $key2 => $value2)
							{
								// 自身の行とはチェックを行わない
								if ($key != $key2)
								{
									$check_project_id		= $this->_project_id[$key2];
									// プロジェクトIDが未設定or後発用コード以外の時にチェック
									if (!empty($self_project_id) && ($self_project_id == $check_project_id))
									{
											$errors['project'] = 'dabble';
									}
								}
							}
						}



					}// マスタ存在endif

				}// 工数セットendif

			}// 全行endforeach
		}


		//-----------------------
		// エラー処理
		//-----------------------
		$error_msg = array();	// エラーメッセージ
		if (!empty($errors))
		{
			// PJコードチェック
			if(isset($errors['project']))
			{
				if ($errors['project'] == 'null')
				{
					$error_msg['project_null'] = 'プロジェクトマスタに存在しないプロジェクトがあります。';
				}
				elseif($errors['project'] == 'active')
				{
					$error_msg['project_active'] = '通常の案件が後発作業用PJコードで登録されています。';
				}
				elseif($errors['project'] == 'error')
				{
					$error_msg['project_error'] = '不正なデータがあります。';
				}
				elseif($errors['project'] == 'dabble')
				{
					$error_msg['project_dabble'] = '重複して登録されているプロジェクトがあります。';
				}
			}

			// 備考チェック
			if(isset($errors['memo']))
			{
				// 必須エラー
				if($errors['memo'] == 'null_after')
				{
					$error_msg['memo_after'] = '後発作業の場合、備考は必須です。';
				}
				// 必須エラー
				if($errors['memo'] == 'null_other')
				{
					$error_msg['memo_other'] = '備考入力が必須のプロジェクトがあります。';
				}
				// 文字数エラー
				if($errors['memo'] == 'over')
				{
					$error_msg['memo_other'] = '備考は'.USER_MANHOUR_MEMO_MAX.'文字以内で入力して下さい。';
				}
			}

			// 工数（時間）チェック
			if (isset($errors['hour']))
			{
				if($errors['hour'] == 'over')
				{
					$error_msg['hour'] = '時間入力値には0以上24以下の値を入力して下さい。';
				}
				if($errors['hour'] == 'decimal')
				{
					$error_msg['hour'] = '時間入力値には少数桁が2桁以内の値を入力して下さい。';
				}
			}

			// プロジェクトタイプ
			if (isset($errors['project_type']))
			{
				if($errors['project_type'] == PROJECT_TYPE_REMOVAL)
				{
					$error_msg['project_type'] = '廃止プロジェクトは登録できません。';
				}
			}
		}

		if (!empty($error_msg))
		{
			// エラー有り
			$this->_session_flg	= 1;	// SESSIONから画面表示させる
			$this->_res			= 3;	// エラー
			$this->_error		= $error_msg;
			$f = new MCWEB_SceneForward('/input/index');
			$f->regist('FORWARD', $this);
			return $f;
		}

		//-----------------------
		// データ登録処理
		//-----------------------
		if(	!empty($this->_work_year)	&&
			!empty($this->_work_month)	&&
			!empty($this->_work_day)	&&
			!empty($member_id)
		  )
		{
			// 前処理：指定日の工数が既に存在している場合は全削除
			if ($obj_manhour->checkInputManhour($member_id,$this->_work_year,$this->_work_month,$this->_work_day))
			{
				$obj_manhour->deleteManhour($member_id,$this->_work_year,$this->_work_month,$this->_work_day);
			}

			if(is_array($this->_project_id))
			{
				// 登録行がある時
				foreach($this->_project_id as $key => $project_id)
				{
					// 登録処理
					$tmp_man_hour = $this->_man_hour[$key]+0;
					if($tmp_man_hour > 0)//0はエラーにはしないが登録はしない
					{
						$obj_manhour->writeManhour($member_id,$project_id,$this->_end_project_id[$key],$this->_man_hour[$key]+0,$this->_work_year,$this->_work_month,$this->_work_day,$this->_memo[$key]);
					}
				}
				$res = 1;
			}
			else
			{
				// 登録行が無い時（全削除）
				$res = 2;
			}
		}

		MCWEB_Util::redirectAction("/input/index?res={$res}&session_flg=1");
		exit;
	}
}
?>