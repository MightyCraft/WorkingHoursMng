<?php
/**
 * プロジェクト管理　既存案件一覧・終了案件一覧画面
 *
 */
require_once(DIR_APP . "/class/common/dbaccess/ProjectTeam.php");
require_once(DIR_APP . '/class/common/dbaccess/Client.php');
require_once(DIR_APP . "/class/common/dbaccess/Project.php");
require_once(DIR_APP . "/class/common/dbaccess/Member.php");
require_once(DIR_APP . "/class/common/MasterMaintenance.php");
require_once(DIR_APP . "/class/common/PagePager.php");

class _project_index extends PostAndGetScene
{
	// タイプ 
	const PROJECT_TYPE_ALL         = '1'; // 有効なプロジェクト
	const PROJECT_TYPE_PROVISIONAL = '2'; // 仮のみ
	const PROJECT_TYPE_ABOLITION   = '3'; // 廃止のみ
	
	// 表示対象
	const DISPLAY_TYPE_NOT_DELETE = '1'; // 未削除のみ
	const DISPLAY_TYPE_DELETE     = '2'; // 削除のみ
	const DISPLAY_TYPE_ALL        = '3'; // 全て表示
	
	// ページャー
	private $obj_pager;

	// 表示形式ページ
	var $_type		= '';	// now：既存案件、end：終了案件

	// 現在ページ
	var $_page		= NULL;
	// ソートするカラム
	var $_column	= NULL;
	// 昇順・降順
	var $_order		= NULL;
	// 検索条件
	var $_radio;	// 検索方法（1:所属PJ､2:ｷｰﾜｰﾄﾞ）
	var $_key_word;	// ｷｰﾜｰﾄﾞ検索時のｷｰﾜｰﾄﾞ
	var $_member_id;	//メンバーid
	var $_total_budget_chk;	//総予算
	var $_project_type = '1'; // タイプ
	var $_display_type = '1'; // 表示対象
	
	// ページ当たりの表示件数
	const PER_PAGE = 50;

	// エラーチェック
	var $_error;

	function check()
	{
		// 未セット時は初期化
		if(!isset($_SESSION['manhour']['projectlist']))
		{
			$_SESSION['manhour']['projectlist']	= array();
		}
		elseif(empty($this->_type) && empty($this->_page) && empty($this->_column))
		{
			$_SESSION['manhour']['projectlist']	= array();
			//営業部の場合自分自身をあらかじめ選択
			if($_SESSION['manhour']['member']['post'] == 2)
			{
				$this->_member_id = $_SESSION['manhour']['member']['id'];
				$_SESSION['manhour']['projectlist']['member_id'] = $this->_member_id;
			}
			else
			{
				$this->_member_id = 0;
				$_SESSION['manhour']['projectlist']['member_id'] = 0;
			}
		}
		elseif(!empty($this->_type) && empty($this->_page) && empty($this->_column))//案件一覧をクリックした場合
		{
			$_SESSION['manhour']['projectlist']	= array();
			//営業部の場合自分自身をあらかじめ選択
			if($_SESSION['manhour']['member']['post'] == 2)
			{
				$this->_member_id = $_SESSION['manhour']['member']['id'];
				$_SESSION['manhour']['projectlist']['member_id'] = $this->_member_id;
			}
			else
			{
				$this->_member_id = 0;
				$_SESSION['manhour']['projectlist']['member_id'] = 0;
			}
		}

		//表示タイプの初期値設定
		if(!preg_match('/^(now|end)$/', $this->_type))
		{
			// 画面から表示タイプの指定が無い時は初期値セット
			if(empty($_SESSION['manhour']['projectlist']['type']))
			{
				$this->_type	= 'now';
			}
			else
			{
				$this->_type	= $_SESSION['manhour']['projectlist']['type'];
			}
		}
		else
		{
			// 画面から表示タイプの指定がある時
			if(	!empty($_SESSION['manhour']['projectlist']['type']) &&
				strcmp($this->_type, $_SESSION['manhour']['projectlist']['type']) != 0)
			{
				// 終了案件表示⇔既存案件表示が切り替わった場合、保持情報はクリア
				$this->_page		= 1;
				$this->_column		= 'project_type';
				$this->_order		= 'DESC';
				$this->_radio		= 2;
				$this->_key_word	= '';
				//営業部の場合自分自身をあらかじめ選択
				if($_SESSION['manhour']['member']['post'] == 2)
				{
					$this->_member_id = $_SESSION['manhour']['member']['id'];
				}
				else
				{
					$this->_member_id = 0;
				}
				$this->_total_budget_chk[0]	= 0;
				$this->_project_type_chk[0]	= 0;
			}
		}

		//表示タイプのSESSION保存
		$_SESSION['manhour']['projectlist']['type']	= $this->_type;

		// 検索条件の初期値設定
		if ((empty($this->_radio)) || (($this->_radio != 1) && ($this->_radio != 2)))
		{
			// 画面から未入力の時（メニューから遷移 or ページLINK遷移 or ソートLINK遷移）
			if (empty($_SESSION['manhour']['projectlist']['radio']))
			{
				// SESSION未セット時
				$this->_radio = 2;
				$this->_key_word = '';
				$this->_page = 1;	// 念の為、現在ページもリセット
				//営業部の場合自分自身をあらかじめ選択
				if($_SESSION['manhour']['member']['post'] == 2)
				{
					$this->_member_id = $_SESSION['manhour']['member']['id'];
				}
				else
				{
					$this->_member_id = 0;
				}
				$this->_total_budget_chk[0]	= 0;
			}
			else
			{
				$this->_radio		= $_SESSION['manhour']['projectlist']['radio'];
				$this->_key_word	= $_SESSION['manhour']['projectlist']['key_word'];
				$this->_member_id	= $_SESSION['manhour']['projectlist']['member_id'];
				$this->_total_budget_chk[0]	= $_SESSION['manhour']['projectlist']['total_budget_chk'][0];
			}
		}

		//エラー対策
		if(!is_array($this->_total_budget_chk) || !is_numeric($this->_total_budget_chk[0]))
		{
				$this->_total_budget_chk[0]	= 0;
		}

		// 検索条件のSESSION保存
		$_SESSION['manhour']['projectlist']['radio']	= $this->_radio;
		$_SESSION['manhour']['projectlist']['key_word']	= $this->_key_word;
		$_SESSION['manhour']['projectlist']['member_id']	= $this->_member_id;
		$_SESSION['manhour']['projectlist']['total_budget_chk'] = $this->_total_budget_chk;

		// ページ指定の初期値設定、SESSION保存（ページ遷移都度更新）
		if(empty($this->_page) || !is_numeric($this->_page))
		{
			// 未指定or数字で無い場合
			if(empty($_SESSION['manhour']['projectlist']['page']))
			{
				// SESSIONを参照して無かった場合は初期値
				$this->_page	= 1;
			}
			else
			{
				$this->_page	= $_SESSION['manhour']['projectlist']['page'];
			}
		}
		$_SESSION['manhour']['projectlist']['page']	= $this->_page;
		// ソートKEYの初期値設定、SESSION保存
		if(!preg_match(	'/^(project_type|id|project_code|name|client_id|total_budget_manhour|'.
						'total_budget|project_end_date|end_date|member_id)$/', $this->_column))
		{
			if(empty($_SESSION['manhour']['projectlist']['column']))
			{
				$this->_column	= 'project_type';
			}
			else
			{
				$this->_column	= $_SESSION['manhour']['projectlist']['column'];
			}
		}
		$_SESSION['manhour']['projectlist']['column']	= $this->_column;
		// 昇順・降順の初期値設定、SESSION保存
		if(!preg_match('/^(DESC|ASC)$/', $this->_order))
		{
			if(empty($_SESSION['manhour']['projectlist']['order']))
			{
				$this->_order	= 'DESC';
			}
			else
			{
				$this->_order	= $_SESSION['manhour']['projectlist']['order'];
			}
		}
		$_SESSION['manhour']['projectlist']['order']	= $this->_order;

		// 入力文字チェック
		$errors =	MCWEB_ValidationManager::validate(
			$this
			, 'key_word', ValidatorString::createInstance()->width()->max(256)
						);
		//エラー文言配列
		$error_format_array =	array(
			'key_word'					=> 'キーワードは256文字以内で入力して下さい。',
								);
		//エラーチェック
		$error_msg	= array();
		foreach($errors as $error_key => $error_value)
		{
			if ( ($error_value[0] != 'null') && (isset($error_format_array[$error_key])) )
			{
				$error_msg[]	= $error_format_array[$error_key];
			}
		}

		//エラー
		if(!empty($error_msg))
		{
			$this->_error = $error_msg;
			return;
		}
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{
		// プロジェクトデータ取得・設定（エラー無しの時のみ）
		if(empty($this->_error))
		{
			$obj_project		= new Project;
			$obj_member			= new Member;
			$obj_client			= new Client;
			$obj_maintenance	= new MasterMaintenance;

			//クライアントリスト取得
			$access->text('client_list', $obj_client->getDataAll());

			// 検索条件セット
			$arr_search = array();
			if ($this->_radio == 1)
			{
				$obj_project_team = new ProjectTeam;
				$arr_project_team = $obj_project_team->getDataByMemberId($_SESSION['manhour']['member']['id']);
				if (!empty($arr_project_team))
				{
					foreach ($arr_project_team as $value)
					{
						$arr_search[] = $value['project_id'];
					}
				}
			}
			elseif ($this->_radio == 2)
			{
				$arr_search = array(
						'MP.project_code' => $this->_key_word,
						'MP.name' => $this->_key_word,
						'MC.name' => $this->_key_word,
								);
			}

			$search_column = '';
			$search_column['member_id'] = '';
			$search_column['total_budget_chk'] = '';
			$search_column['project_type_chk'] = '';
			//担当営業が指定されていた場合
			if($this->_member_id >= 1)
			{
				$search_column['member_id'] = $this->_member_id;
			}

			//総予算未設定が指定されていた場合
			if($this->_total_budget_chk[0] >= 1)
			{
				$search_column['total_budget_chk'] = $this->_total_budget_chk[0];
			}

			//タイプが指定されていた場合
			if(!empty($this->_project_type))
			{
				if ($this->_project_type == self::PROJECT_TYPE_PROVISIONAL)
				{
					$search_column['project_type'] = PROJECT_TYPE_INFORMAL;
				} 
				else if ($this->_project_type == self::PROJECT_TYPE_ABOLITION)
				{
					$search_column['project_type'] = PROJECT_TYPE_REMOVAL;
				} 
				else 
				{
					$this->_project_type = self::PROJECT_TYPE_ALL;
				}
			} 
			else 
			{
				$this->_project_type = self::PROJECT_TYPE_ALL;
			}
			
			//表示対象が指定されていた場合
			if(!empty($this->_display_type))
			{
				if ($this->_display_type == self::DISPLAY_TYPE_NOT_DELETE)
				{
					$search_column['delete_flg'] = '0';
				}
				else if ($this->_display_type == self::DISPLAY_TYPE_DELETE)
				{
					$search_column['delete_flg'] = '1';
				} 
				else 
				{
					$this->_display_type = self::DISPLAY_TYPE_NOT_DELETE;
				}
			} 
			
			//リストを取得
			$list	= $obj_project->getDataByLimit($this->_page, self::PER_PAGE, $total, $this->_type, $this->_column, $this->_order, $this->_radio, $arr_search, $search_column);

			// 検索用営業部社員リスト（検索用プルダウンには削除済み含まない）
			$member_by_post_sales = $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES);

			//該当件数がある場合、ページャー、ページ情報を設定
			if($total)
			{
				$pager	= PagePager::createAdminPagePager($this->_page, self::PER_PAGE, $total, '/project', array('type' => $this->_type));
				$access->htmltag('pager',	$pager->getLinks());
				$access->text('total',		$total);
				$access->text('last_page',	$pager->numPages());
				$access->text('now_page',	$this->_page);
			}
			$access->text('list', (!empty($list) ? $list : false));

			//PJコードタイプ（仮登録コード）
			$access->text('project_type_informal', PROJECT_TYPE_INFORMAL);

			// アカウントリスト取得(営業のみ)
			$access->text('member_list', $obj_maintenance->getMemberByPostType(PostTypeDefine::SALES),true);	// 一覧表示には削除済み含む

		}

		// 検索条件設定
		if (empty($this->_radio))
		{
			// 未設定の時は強制的にキーワード
			$this->_radio = 2;
		}

		$access->text('radio_id', $this->_radio);
		$access->text('radio_list', array('1'=>'所属プロジェクト','2'=>'キーワード（PJコード/プロジェクト名/クライアント名）',));
		$access->text('key_word', $this->_key_word);
		$access->text('member_id', $this->_member_id);
		$access->text('member_by_post_sales', $member_by_post_sales);
		$access->text('total_budget_chk', $this->_total_budget_chk);

		
		// タイプ
		$project_type_list = array(
				self::PROJECT_TYPE_ALL			=>	'全て表示',
				self::PROJECT_TYPE_PROVISIONAL	=>	'仮のみ',
				self::PROJECT_TYPE_ABOLITION	=>	'廃止のみ'
				);
		$access->text('project_type_list', $project_type_list);
		$access->text('project_type', $this->_project_type);
		
		// 表示対象
		$access->text('display_type', $this->_display_type);
		$access->text('display_type_list', array(self::DISPLAY_TYPE_NOT_DELETE=>'未削除のみ',self::DISPLAY_TYPE_DELETE=>'削除のみ',self::DISPLAY_TYPE_ALL=>'全て表示'));
		
		// ソートリンク用クエリパラメータ
		$this->query_parameter = '&project_type='.$this->_project_type;
		$this->query_parameter .= '&display_type='.$this->_display_type;
		
		return;
	}
}

?>