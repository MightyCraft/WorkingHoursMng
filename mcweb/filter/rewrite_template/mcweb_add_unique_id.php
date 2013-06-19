<?php

/**
 * ユニークIDをURLパラメータに付与する。
 * タグにadd_unique_id='true'属性が付与されてた場合に動作します。
 *
 * デフォルト動作はfalseです。
 */
class mcweb_add_unique_id implements MCWEB_filter_rewrite_template
{
	var $key_name;

	/**
	 * コンストラクタ
	 * @param	$key_name	URLパラメータとしてのキー名を指定する
	 */
	function __construct($key_name)
	{
		$this->key_name = $key_name;
	}

	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		//	ページ内で何番目の搭乗か
		$index = 0;

		$a = $dom->find('a');
		if (!empty($a))
		{
			foreach($a as $tag)
			{
				if (!empty($tag->add_unique_id) && 'true' === $tag->add_unique_id)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && !empty($tag->href))
					{
						$content = $tag->href;
						if (FALSE === strpos($content, $LD))
						{
							//	Smartyタグは存在せず、固定値である
							$content = self::rewrite_link($content, $LD . '$ADD_UNIQUE_ID_KEY_NAME' . $RD . '=' . $LD . 'mcweb_add_unique_id index="' . $index . '"' . $RD);
						}
						else
						{
							//	Smartyタグが見つかったので、ブロック関数で囲う
							$content = $LD . 'mcweb_block_add_unique_id index="' . $index . '" key="$ADD_UNIQUE_ID_KEY_NAME"' . $RD . $content . $LD . '/mcweb_block_add_unique_id' . $RD;
						}
						++$index;
						$tag->href = $content;
					}
				}

				//	属性を削除
				if (!empty($tag->add_unique_id))
				{
					$tag->add_unique_id = null;
				}
			}
		}

		$form = $dom->find('form');
		if (!empty($form))
		{
			foreach($form as $tag)
			{
				if (!empty($tag->add_unique_id) && 'true' === $tag->add_unique_id)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype)
					{
						$value = $LD . 'mcweb_add_unique_id index="' . $index . '"' . $RD;
						$value = '<input type="hidden" name="' . $LD . '$ADD_UNIQUE_ID_KEY_NAME' . $RD . '" value="' . $value . '" />';

						$insert = new simple_html_dom_node($dom);
						$insert->outertext = $value . "\n";
						array_push($tag->nodes, $insert);
					}
				}

				//	属性を削除
				if (!empty($tag->add_unique_id))
				{
					$tag->add_unique_id = null;
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
		$output->text('ADD_UNIQUE_ID_KEY_NAME', $this->key_name);
	}

	static function rewrite_link($content, $param)
	{
		$in_page_link = (!empty($content) && '#' == $content[0]);
		if (!$in_page_link)
		{
			//	#による、ページスクロール制御をきちんとURLの末尾につけるための処理
			$tail = '';
			$n = strpos($content, '#');
			if (FALSE !== $n)
			{
				$tail = substr($content, $n);
				$content = substr($content, 0, $n);
			}

			if (FALSE !== ($n = strpos($content, '?')))
			{
				if ($n === (strlen($content) - 1))	$content .= $param;
				else								$content .= '&amp;' . $param;
			}
			else
			{
				$content .= '?' . $param;
			}
			$content .= $tail;
		}
		return $content;
	}
}



?>