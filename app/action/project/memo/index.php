<?php
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
require_once(DIR_APP . "/class/common/ManhourList.php");
require_once(DIR_APP . "/class/common/PagePager.php");
class _project_memo_index extends PostAndGetScene
{
	// ページ毎件数
	const PER_PAGE = 50;

	var $_page = 1;
	var $_member_id;
	var $_key_word;
	var $_column;
	var $_order;
	var $_date_Year;
	var $_date_Month;
	var $_project_id;
	var $_project_id_keyword;
	
	function check()
	{
		// パラメータのバリデート
		$errors = MCWEB_ValidationManager::validate(
			$this
			, 'member_id',				ValidatorInt::createInstance()->nullable()->min(0)
			, 'project_id',				ValidatorInt::createInstance()->nullable()->min(0)
			, 'key_word',				ValidatorString::createInstance()->nullable()
			, 'project_id_keyword',		ValidatorString::createInstance()->nullable()
			, 'page',					ValidatorInt::createInstance()->nullable()->min(1)
		);
		// エラーの時
		if (!empty($errors))
		{
			MCWEB_Util::redirectAction("/");
		}
		
		// 表示年月
		if ((empty($this->_date_Year) || empty($this->_date_Month)) || !checkdate($this->_date_Month, 1, $this->_date_Year))
		{
			$this->_date_Year	= date('Y');
			$this->_date_Month	= date('m');
		}
		// ソート順の正当性チェック
		if (!($this->_order == 'ASC' || $this->_order == 'DESC'))
		{
			$this->_order = 'ASC';
		}
		// ソート対象カラムの正当性チェック
		if(!preg_match('/^(client_name|project_name|memo|member_name|input_datetime)$/', $this->_column))
		{
			$this->_column	= 'input_datetime';
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_maintenance = new MasterMaintenance();
		$obj_member = new Member();
		
		// クライアントマスタをキャッシュしておく
		$obj_client = new Client();
		$client_list = $obj_client->getDataAll();
		
		// ----------------------------
		// 検索条件
		// ----------------------------
		// 社員名リスト
		$member_list = $obj_member->getMemberAll();

		// 備考必須のプロジェクトを取得
		$conditions = array(
				"delete_flg" => 0,
				"memo_flg"   => 1,
		);
		$obj_project = new Project();
		$search_project_list = $obj_project->getDataByConditions($conditions);
		
		// クライアント名を付加
		foreach($search_project_list as &$search_project) 
		{
			$search_project['client_name'] = $client_list[$search_project['client_id']]['name'];
		}
		
		// ----------------------------
		// データ抽出
		// ----------------------------
		$project_list = $search_project_list;

		// プロジェクトの指定があるときは指定プロジェクト関連情報のみ取得
		// 無い場合は備考必須で削除以外のプロジェクト全てが対象
		if ($this->_project_id)
		{
			$project_ids = array($this->_project_id);
		} 
		else 
		{
			$project_ids = array_keys($project_list);
		}
		
		// 工数情報抽出
		$date = strtotime($this->_date_Year.'-'.$this->_date_Month.'-'.'1'.' 00:00:00');
		$start_date = date("Y-m-01", $date);
		$end_date = date("Y-m-t", $date);
		
		$obj_manhour = new Manhour();
		$manhour_list = $obj_manhour->getRengeDataByProjectIds($project_ids, $start_date, $end_date, $this->_key_word);
		foreach ($manhour_list as $key => &$manhour)
		{
			// 検索条件 社員名指定時は該当社員の情報以外は除外
			if ($this->_member_id && $manhour['member_id'] != $this->_member_id)
			{
				unset($manhour_list[$key]);
				continue;
			}
			// 削除済み社員の工数は除く
			if (!isset($member_list[$manhour['member_id']]))
			{
				unset($manhour_list[$key]);
				continue;
			}
			$manhour['member_name'] = $member_list[$manhour['member_id']]['name'];
			$manhour['client_name'] = $search_project_list[$manhour['project_id']]['client_name'];
			$manhour['project_name'] = $search_project_list[$manhour['project_id']]['name'];
		}
		
		// 指定行でソート
		$manhour_list = usortArray($manhour_list, $this->_column, $this->_order);
		
		// ----------------------------
		// ページャー
		// ----------------------------
		$extra_vars = array(
				'key_word'           => $this->_key_word,
				'column'             => $this->_column,
				'order'              => $this->_order,
				'member_id'          => $this->_member_id,
				'date_Year'          => $this->_date_Year,
				'date_Month'         => $this->_date_Month,
				'project_id'         => $this->_project_id,
				'project_id_keyword' => $this->_project_id_keyword,
		);
		$total = count($manhour_list);
		$pager = PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $total, '/project/memo', $extra_vars);
		
		// カレントページデータを取得
		$start_index = self::PER_PAGE * ($this->_page - 1);
		$manhour_list = array_slice($manhour_list, $start_index, self::PER_PAGE);
		
		// ----------------------------
		// 画面項目値設定
		// ----------------------------
		$this->set_time		= $this->_date_Year . '-' . $this->_date_Month . '-01';
		$access->htmltag('pager',	$pager->getLinks());
		$access->text('total',		$total);
		$access->text('last_page',	$pager->numPages());
		$access->text('now_page',	$this->_page);
		
		$access->text('project_id',	$this->_project_id);
		$access->text('project_id_keyword',	$this->_project_id_keyword);
		$access->text('member_list', 	$member_list);
		$access->text('manhour_list', 	$manhour_list);
		$access->text('search_project_list', 	$search_project_list);
		
		$add_parameters = '&key_word='.$this->_key_word;
		$add_parameters .= '&member_id='.$this->_member_id;
		$add_parameters .= '&date_Year='.$this->_date_Year;
		$add_parameters .= '&date_Month='.$this->_date_Month;
		$add_parameters .= '&project_id='.$this->_project_id;
		$add_parameters .= '&project_id_keyword='.$this->_project_id_keyword;
		
		$access->text('add_parameters', $add_parameters);
	}
}

?>