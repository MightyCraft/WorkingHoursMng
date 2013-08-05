<?php

//	フレームワークに設定を定義
$c = MCWEB_Framework::getInstance();

class MyFilterFactory implements MCWEB_filter_factory
{
	function startup($entry_path)
	{
		if (MCWEB_Framework::getInstance()->commandline)
		{
			return array(
					  new mcweb_define_client_carrier_by_useragent()
					, new mcweb_define_client_encoding()
					, new mcweb_encode_input()
			);
		}
		return array(
			  new mcweb_deny_illegal_httphost()
			, new mcweb_define_client_carrier_by_ip(DIR_DEFINE . '/filter.txt')
			, new mcweb_define_client_is_simulator(DIR_DEFINE . '/filter.txt')
			, new mcweb_allow_carrier(array('DEBUG'))
			, new mcweb_sanitize_session_id()
			, new mcweb_define_client_uid('guid')
			, new mcweb_auto_session()
			, new mcweb_check_duplication('regkey')
			, new mcweb_define_client_encoding()
			, new mcweb_encode_input()
			, new mcweb_session_member_check()
			, new mcweb_check_authority()
		);
	}

	function scene_create($action_path)
	{
		return array();
	}
	function scene_input($action_path)
	{
		return array(
			  new mcweb_auto_regist_input()
		);
	}
	function scene_check($action_path)
	{
		return array();
	}
	function scene_task($action_path)
	{
		return array();
	}
	function scene_draw($action_path)
	{
		return array();
	}
	function rewrite_template($template_path)
	{
		return array(
			  new mcweb_comment_to_include()
			, new mcweb_image_tile()
			, new mcweb_add_unique_id('regkey')
			, new mcweb_rewrite_image_size()
			, new mcweb_rewrite_link(
				array()
				)
/*
uid=NULLGWDOCOMOの場合のサンプルです
フォームでは、uid=NULLGWDOCOMOをhidden側に設定する必要があります
また、startupフィルタの「mcweb_define_client_uid」の引数も、'guid'から'uid'に変えてください
また、startupフィルタの「mcweb_docomo_guid_redirect」を「mcweb_docomo_uid_redirect」に変えてください

			, new mcweb_rewrite_link(
				array(
					  'link' => array('uid' => 'NULLGWDOCOMO')
					, 'form' => array()
					, 'hidden' => array('uid' => 'NULLGWDOCOMO')
					)
				)
*/
			, new mcweb_rewrite_image_path()
			, new mcweb_rewrite_content()
			, new rewrite_header()
		);
	}
	function post_output($template_path)
	{
		return array(
			  new mcweb_encode_output()
			, new mcweb_content_type()
			, new mcweb_debug_output_execution_time()
		);
	}
}

//	フィルターファクトリーを設定
$c->setFilterFactory(new MyFilterFactory);

?>