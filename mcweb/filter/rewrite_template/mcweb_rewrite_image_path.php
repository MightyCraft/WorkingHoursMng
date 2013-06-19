<?php

/**
 * imgタグの画像パスと、背景画像の画像パスを書き換えます
 * タグにrewrite_image_path='false'属性が付与されていなかった場合に動作します。
 *
 * デフォルト動作はtrueです。
 */
class mcweb_rewrite_image_path implements MCWEB_filter_rewrite_template
{
	var $smarty_tag;
	var $adjust_path;

	function __construct($adjust_path = '')
	{
		$this->adjust_path = $adjust_path;
		$this->smarty_tag = 'mcweb_rewrite_resource_path';
	}

	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		$list = $dom->find('img');
		if (!empty($list))
		{
			foreach($list as $tag)
			{
				if (empty($tag->rewrite_image_path) || 'true' === $tag->rewrite_image_path)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && !empty($tag->src))
					{
						$tag->src = $LD . $this->smarty_tag . ' type="src" template_path="'. $template_path . '" adjust_path="$REWRITE_IMAGE_PATH_ADJUST"' . $RD . $tag->src . $LD . '/' . $this->smarty_tag . $RD;
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_image_path))
				{
					$tag->rewrite_image_path = null;
				}
			}
		}

		$list = $dom->find('body');
		if (!empty($list))
		{
			foreach($list as $tag)
			{
				if (empty($tag->rewrite_image_path) || 'true' === $tag->rewrite_image_path)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && !empty($tag->style))
					{
						//	'background-image'を探し、そこで定義されている背景画像のパスを調整する
						$start = strpos($tag->style, 'background-image');
						if (FALSE !== $start)
						{
							$start = strpos($tag->style, 'url(', $start);
							if (FALSE !== $start)
							{
								$start += strlen('url(');
								$end = strpos($tag->style, ')', $start);
								if (FALSE !== $end)
								{
									$content = trim(substr($tag->style, $start, $end - $start));

									//	パスが ' で囲まれているかチェック
									$flg = ('\'' === $content[0]);
									if ($flg)
									{
										$content = trim(substr($content, 1, -1));
									}

									$content = $LD . $this->smarty_tag . ' type="src" template_path="'. $template_path . '" adjust_path="$REWRITE_IMAGE_PATH_ADJUST"' . $RD . $content . $LD . '/' . $this->smarty_tag . $RD;

									if ($flg)
									{
										$content = '\'' . $content . '\'';
									}
									$tag->style = substr($tag->style, 0, $start) . $content . substr($tag->style, $end);
								}
							}
						}
					}

					//	background要素に指定されている、背景画像のパスを調整する
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && !empty($tag->background))
					{
						$tag->background = $LD . $this->smarty_tag . ' type="src" template_path="'. $template_path . '" adjust_path="$REWRITE_IMAGE_PATH_ADJUST"' . $RD . $tag->background . $LD . '/' . $this->smarty_tag . $RD;
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_image_path))
				{
					$tag->rewrite_image_path = null;
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		$output->text('REWRITE_IMAGE_PATH_ADJUST', $this->adjust_path);
	}
}

?>