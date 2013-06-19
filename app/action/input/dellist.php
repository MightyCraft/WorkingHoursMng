<?php
/**
 * 「工数入力画面」で「削除」ボタンをクリック時の処理
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Client.php");
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
class _input_dellist extends PostScene
{
	// 削除プロジェクトの行番号
	var $_del_index_no;

	// 削除ボタンクリック前の画面に表示されている工数リスト
	var $_man_hour;
	var $_project_id;
	var $_end_project_id;
	var $_memo;

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
		//登録指定日時更新
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

		// 削除前のリストでSESSIONに保持（入力画面に戻す用）
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
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

		// 指定された行をセッションから削除（指定行が無い時は何もしない）
		if(is_array($_SESSION['manhour']['input']['manhour_view_list']))
		{
			foreach($_SESSION['manhour']['input']['manhour_view_list'] as $key => $value)
			{
				if($key == $this->_del_index_no)
				{
					unset($_SESSION['manhour']['input']['manhour_view_list'][$key]);
					$_SESSION['manhour']['input']['manhour_view_list']=array_values($_SESSION['manhour']['input']['manhour_view_list']);
					break;
				}
			}
		}

		MCWEB_Util::redirectAction("/input/index?session_flg=1&ref_type={$this->_ref_type}&ref_year={$this->_ref_year}&ref_month={$this->_ref_month}&ref_day={$this->_ref_day}");
		exit;

	}
}
?>