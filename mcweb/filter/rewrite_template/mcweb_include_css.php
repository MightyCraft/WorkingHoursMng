<?php

/**
 * CSSをHTML内に展開する。
 * また、3キャリア共通処理にするためにCSSの一部をSmartyタグに置き換える。
 */
class mcweb_include_css implements MCWEB_filter_rewrite_template
{
	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		$arr = $dom->find('link');
		if (!empty($arr))
		{
			foreach($arr as $tag)
			{
				if (HDOM_TYPE_ELEMENT == $tag->nodetype && 'stylesheet' === $tag->rel && !empty($tag->href))
				{
					$dirname = dirname($template_path);
					if (!empty($dirname) && 1 === strlen($dirname) && '\\' === $dirname[0])
					{
						$dirname = '';
					}
					//	自作smartyプラグインの、include_cssプラグインを呼び出します
					$str = $LD . 'mcweb_include_css path="' . $dirname . '"' . $RD . $tag->href . $LD . '/mcweb_include_css' . $RD;

					$insert = new simple_html_dom_node($dom);
					$insert->outertext = $str;
					$parent = $tag->parent;
					foreach($parent->nodes as &$child)
					{
						if ($child === $tag)
						{
							$child = $insert;
							break;
						}
					}
					foreach($parent->children as &$child)
					{
						if ($child === $tag)
						{
							$child = $insert;
							break;
						}
					}
				}
			}
		}

		foreach($dom->nodes as &$value)
		{
			if (isset($value->class))
			{
				$value->style = '%' . preg_replace('/\s+/', ' ', trim($value->class)) . '%;' . $value->style;
				$value->class = null;
				$value->style = $LD . 'mcweb_normalize_style tag="' . $value->tag . '"' . $RD . $value->style . $LD . '/mcweb_normalize_style' . $RD;
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
	}
}

?>