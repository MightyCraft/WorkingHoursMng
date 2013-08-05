<?php
/**
 * アカウント一覧
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/dbaccess/Post.php");
require_once(DIR_APP . "/class/common/dbaccess/Position.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberType.php");
require_once(DIR_APP . "/class/common/dbaccess/MemberCost.php");
require_once(DIR_APP . "/class/common/PagePager.php");
class _user_index extends PostAndGetScene
{
	// 表示対象 未削除のみ
	const DISPLAY_TYPE_NOT_DELETE = '1';
	// 表示対象 削除のみ
	const DISPLAY_TYPE_DELETE = '2';
	// 表示対象 全て表示
	const DISPLAY_TYPE_ALL = '3';

	// ページャー
	private $obj_pager;
	// 現在ページ
	var $_page		= NULL;
	// ソートするカラム
	var $_column	= NULL;
	// 昇順・降順
	var $_order		= NULL;
	// ページ当たりの表示件数
	const PER_PAGE = 30;
	// 検索条件 所属
	var $_post;
	// 検索条件 社員タイプ
	var $_member_type;
	// 検索条件 名前キーワード
	var $_keyword_name;
	// 検索条件 表示対象
	var $_display_type = '1';

	// 名称表示用マスタデータ
	var $array_auth_lv;
	var $array_post;
	var $array_position;
	var $array_member_type;
	var $array_member_cost;

	function check()
	{
		if(!isset($_SESSION['manhour']['userlist']))
		{
			$_SESSION['manhour']['userlist']	= array();
		}

		//ページ指定のチェック
		if(empty($this->_page) || !is_numeric($this->_page))
		{
			if(empty($_SESSION['manhour']['userlist']['page']))
			{
				$this->_page	= 1;
			}
			else
			{
				$this->_page	= $_SESSION['manhour']['userlist']['page'];
			}
		}
		$_SESSION['manhour']['userlist']['page']	= $this->_page;

		//カラム名保存
		if(!preg_match('/^(id|member_code|name|auth_lv|post|position|mst_member_type_id|mst_member_cost_id)$/', $this->_column))
		{
			if(empty($_SESSION['manhour']['userlist']['column']))
			{
				$this->_column	= 'id';
			}
			else
			{
				$this->_column	= $_SESSION['manhour']['userlist']['column'];
			}
		}
		$_SESSION['manhour']['userlist']['column']	= $this->_column;

		//昇順・降順保存
		if(!preg_match('/^(DESC|ASC)$/', $this->_order))
		{
			if(empty($_SESSION['manhour']['userlist']['order']))
			{
				$this->_order	= 'ASC';
			}
			else
			{
				$this->_order	= $_SESSION['manhour']['userlist']['order'];
			}
		}
		$_SESSION['manhour']['userlist']['order']	= $this->_order;

		// パラメータチェック
		// 所属
		$post_obj = new Post();
		if (!empty($this->_post) && !$post_obj->getDataById($this->_post))
		{
			$this->_post = null;
		}
		// 社員タイプ
		$obj_member_type	= new MemberType();
		if (!empty($this->_member_type))
		{
			$member_type_data = $obj_member_type->getDataById($this->_member_type);
			if (empty($member_type_data))
			{
				$this->_member_type = null;
			}

		}
		// 表示対象
		if (!($this->_display_type >= self::DISPLAY_TYPE_NOT_DELETE || $this->_display_type < self::DISPLAY_TYPE_ALL))
		{
			$this->_display_type = self::DISPLAY_TYPE_DELETE;
		}

	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		$obj_member = new Member;

		$offset = ($this->_page - 1) * self::PER_PAGE;
		$limit = self::PER_PAGE;

		// 検索条件
		$where_columns = array();
		$where_columns_keyword = array();
		$url_params = array();

		// 検索条件 所属
		if ($this->_post)
		{
			$where_columns['post'] = $this->_post;
			$url_params['post'] = $this->_post;
		}
		// 検索条件 社員タイプ
		if ($this->_member_type)
		{
			$where_columns['mst_member_type_id'] = $this->_member_type;
			$url_params['member_type'] = $this->_member_type;
		}
		// 検索条件 名前
		if ($this->_keyword_name)
		{
			$where_columns_keyword['name'] = $this->_keyword_name;
			$url_params['name'] = $this->_keyword_name;
		}
		// 検索条件 表示対象
		if (empty($this->_display_type) || $this->_display_type == self::DISPLAY_TYPE_NOT_DELETE)
		{
			$where_columns['delete_flg'] = '0';
			$url_params['display_type'] = $this->_display_type;
		}
		elseif ($this->_display_type == self::DISPLAY_TYPE_DELETE)
		{
			$where_columns['delete_flg'] = '1';
		}
		$url_params['display_type'] = $this->_display_type;

		// 検索
		list($member_all,$all_num) = $obj_member->getMemberAllPagerByWhere($offset,$limit,$this->_column,$this->_order, $where_columns, $where_columns_keyword);

		// ページャーセット
		$this->obj_pager = PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $all_num, '/user', $url_params);

		$first_index = 0;
		$last_index = 0;
		//ページ毎の最初の番号
		$first_index = ($this->_page - 1) * self::PER_PAGE + 1;
		//ページ毎の最後の番号
		$last_index = $first_index + (self::PER_PAGE - 1);

		//最後のページの特殊ケースへの対応
		$is_last = $this->obj_pager->isLastPage();
		if($is_last)
		{
			$last_index = $all_num;
		}

		$last_page = $this->obj_pager->numPages();

		//該当件数0件
		if($all_num == 0)
		{
			$first_index = 0;
			$last_index = 0;
		}

		// 各マスタ名称取得
		$this->array_auth_lv	= returnArrayAuthLv();
		$obj_post			= new Post();
		$this->array_post	= $obj_post->getDataAll();
		$obj_position 			= new Position();
		$this->array_position	= $obj_position->getDataAll();
		$obj_member_type			= new MemberType();
		$this->array_member_type	= $obj_member_type->getDataAll();
		$obj_member_cost			= new MemberCost();
		$this->array_member_cost	= $obj_member_cost->getDataAll();

		//テンプレートへセット//GET値POST値等publicなメンバー変数は自動的にセット
		$access->text('member_all',		$member_all);

		$access->text('all_num', $all_num);
		$access->text('first_index', $first_index);
		$access->text('last_index', $last_index);
		$access->text('last_page', $last_page);
		$access->htmltag('pager', $this->obj_pager->getLinks());

		// 検索条件 所属
		$this->post = $this->_post;

		// 検索条件 社員タイプ
		$this->member_type = $this->_member_type;

		// 検索条件 名前キーワード
		$access->text('keyword_name',	$this->_keyword_name);

		// 検索条件 表示対象
		$access->text('display_type', $this->_display_type);
		$access->text('display_type_list', array(self::DISPLAY_TYPE_NOT_DELETE=>'未削除のみ',self::DISPLAY_TYPE_DELETE=>'削除のみ',self::DISPLAY_TYPE_ALL=>'全て表示'));

		// ソートリンク用クエリパラメータ
		$this->query_parameter = '&post='.$this->_post;
		$this->query_parameter = '&member_type='.$this->_member_type;
		$this->query_parameter .= '&keyword_name='.$this->_keyword_name;
		$this->query_parameter .= '&display_type='.$this->_display_type;
	}
}

?>