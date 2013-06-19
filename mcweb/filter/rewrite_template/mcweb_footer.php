<?php

/**
 * body終了タグの直前に、includeを追加する
 * タグにmcweb_footer='false'属性が付与されていなかった場合に動作します。
 *
 * デフォルト動作はtrueです。
 */
class mcweb_footer implements MCWEB_filter_rewrite_template
{
	protected $html_path;
	function __construct($html_path)
	{
		$this->html_path = $html_path;
	}

	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		$list = $dom->find('body');
		if (!empty($list))
		{
			foreach($list as $tag)
			{
				if (empty($tag->mcweb_footer) || 'true' === $tag->mcweb_footer)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype)
					{
						$insert = new simple_html_dom_node($dom);
						$insert->outertext = $LD . 'include file="file:$FOOTER_HTML_PATH"' . $RD;
						array_push($tag->nodes, $insert);
					}
				}

				//	属性を削除
				if (!empty($tag->mcweb_footer))
				{
					$tag->bodytag_fontsize_to_div = null;
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		$output->text('FOOTER_HTML_PATH', $this->html_path);
	}
}

?>