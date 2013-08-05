<?php
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
require_once(DIR_APP . "/class/common/ManhourList.php");
require_once(DIR_APP . "/class/common/PagePager.php");
class _project_risk_index extends PostAndGetScene
{
	// ページ毎件数
	const PER_PAGE = 50;

	// 抽出タイプ
	const CONDITIONS_TYPE_ALL = 'all'; // 全て
	const CONDITIONS_TYPE_USED = 'used'; // 使用済コスト工数率
	const CONDITIONS_TYPE_OVER = 'over'; // 超過予想コスト工数
	
	var $_page = 1;
	var $_member_id;
	var $_key_word;
	var $_conditions_type = self::CONDITIONS_TYPE_ALL;
	var $_conditions_type_list;
	var $_member_by_post_sales;
	var $_used_rate;
	var $_column;
	var $_order;

	function check()
	{
		// 抽出タイプの正当性チェック
		if(!preg_match('/^(all|over|used)$/', $this->_conditions_type))
		{
			$this->_conditions_type = 'all';
		}
		// 抽出タイプ 使用済コスト工数率の正当性チェック
		if ($this->_conditions_type == 'used')
		{
			if (!($this->_used_rate >= 0 && $this->_used_rate <= 1000))
			{
				$this->_used_rate = 0;
			}
		}
		// ソート順の正当性チェック
		if (!($this->_order == 'ASC' || $this->_order = 'DESC'))
		{
			$this->_order = 'DESC';
		}
		// ソート対象カラムの正当性チェック
		if(!preg_match('/^(id|project_code|name|use_cost_manhour|total_cost_manhour|total_remains_manhour|used_manhour_rate|expected_over_manhour|project_end_date|member_id)$/', $this->_column))
		{
			$this->_column	= 'id';
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_maintenance = new MasterMaintenance();
		// ----------------------------
		// 検索条件
		// ----------------------------
		// 営業部リスト
		$member_by_post_sales = $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES);

		// ----------------------------
		// データ抽出
		// ----------------------------
		// 危険案件一覧取得
		$obj_manhour_list = new ManhourList();
		$today = date('Y-m-d h:m:i');
		$risc_project_list = $obj_manhour_list->getRiscProjectList($today, $this->_member_id, $this->_key_word, array('id' => 'DESC'), $this->_conditions_type, $this->_used_rate);
		
		// ソートが上手く出来るように規定項目がnullの場合は-1に置換
		// 画面上では値は表示されない
		foreach ($risc_project_list as &$risc_project)
		{
			// 総割当コスト工数未設定の場合
			if ($risc_project['total_cost_manhour'] == NULL)
			{
				$risc_project['used_manhour_rate'] = -99999999999;
				$risc_project['total_remains_manhour'] = -99999999999;
			}
			// 開発終了日が未設定の場合
			if (!$risc_project['project_end_date'])
			{
				$risc_project['expected_over_manhour'] = -99999999999;
			}
		}
		
		// 指定行でソート
		$risc_project_list = usortArray($risc_project_list, $this->_column, $this->_order);

		// ----------------------------
		// ページャー
		// ----------------------------
		$extra_vars = array(
				'key_word'        => $this->_key_word,
				'member_id'       => $this->_member_id,
				'conditions_type' => $this->_conditions_type,
				'used_rate'       => $this->_used_rate,
				'column' 		  => $this->_column,
				'order'       	  => $this->_order,
		);
		$total = count($risc_project_list);
		$pager = PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $total, '/project/risk', $extra_vars);

		// カレントページデータを取得
		$start_index = self::PER_PAGE * ($this->_page - 1);
		$risc_project_list = array_slice($risc_project_list, $start_index, self::PER_PAGE);
		
		// ----------------------------
		// 画面項目値設定
		// ----------------------------
		
		$access->htmltag('pager',	$pager->getLinks());
		$access->text('total',		$total);
		$access->text('last_page',	$pager->numPages());
		$access->text('now_page',	$this->_page);
		$access->text('today',	date("Y-m-d", strtotime($today)));
		
		$access->text('member_by_post_sales', $member_by_post_sales);
		$access->text('conditions_type', $this->_conditions_type);
		$access->text('conditions_type_list', $this->_conditions_type_list);
		$access->text('risc_project_list', $risc_project_list);
		
		$add_parameters = '&conditions_type='.$this->_conditions_type;
		$add_parameters .= '&key_word='.$this->_key_word;
		$add_parameters .= '&used_rate='.$this->_used_rate;
		$add_parameters .= '&member_id='.$this->_member_id;
		
		$access->text('add_parameters', $add_parameters);
		
	}
}

?>