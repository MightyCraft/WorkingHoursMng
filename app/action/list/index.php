<?php
/**
 * action未指定で遷移してきた時のリダイレクト処理
 *
 */
class _list_index extends PostAndGetScene
{
	/**
	 * @access public
	 * @var integer 選択年
	 */
	var $_date_Year;
	/**
	 * @access public
	 * @var integer 選択月
	 */
	var $_date_Month;
	/**
	 * @access public
	 * @var integer 選択社員ID
	 */
	var $_project_id;
	/**
	 * @access public
	 * @var integer 選択社員ID
	 */
	var $_member_id;

	/**
	 * @access public
	 * @var boolean 検索実行確認
	 */
	var $do_search		= true;

	function check()
	{

		if( $this->_date_Year  === false )
		{
			$this->_date_Year    = date('Y');
		}
		if( $this->_date_Month === false )
		{
			$this->_date_Month	= date('m');
		}

		if( !empty($this->_project_id) )
		{
			//プロジェクトIDのみ選ばれている場合
			if( empty($this->_member_id) )
			{
				MCWEB_Util::redirectAction("/list/project?date_Year={$this->_date_Year}&date_Month={$this->_date_Month}&project_id={$this->_project_id}");
			}
		}
		//メンバーIDのみ選ばれている場合
		else if( !empty($this->_member_id) )
		{
			MCWEB_Util::redirectAction("/list/member?date_Year={$this->_date_Year}&date_Month={$this->_date_Month}&member_id={$this->_member_id}");
		}
		//プロジェクトID、メンバーIDどちらも未選択の場合
		else
		{
			$this->do_search = false;
		}

		// 指定が無い場合は社員別工数照会へ
		MCWEB_Util::redirectAction('/list/member');
	}

	function task(MCWEB_InterfaceSceneOutputVars $access)
	{

	}
}

?>