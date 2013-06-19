<?php
/**
 * ヘッダーに表示する所属部署と役職を取得します
 *
 */
require_once(DIR_APP . '/class/common/dbaccess/Post.php');
class rewrite_header implements MCWEB_filter_rewrite_template
{
	function rewrite($template_path, &$dom)
	{
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		if (!empty($_SESSION['manhour']['member']))
		{
			// 役職（=所属）
			$obj_post = new Post();
			$post_data = $obj_post->getDataById($_SESSION['manhour']['member']['post']);

			// 役職が存在しない場合
			$post_name = '';
			if (isset($post_data['name']))
			{
				$post_name = $post_data['name'];
			}
			$output->text('header_post_name', $post_name);
			$output->text('header_member_name', $_SESSION['manhour']['member']['name']);
		}
	}
}
?>