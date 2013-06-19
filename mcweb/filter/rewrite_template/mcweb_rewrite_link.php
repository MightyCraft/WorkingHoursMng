<?php

require_once(DIR_MCWEB . '/HTML/Emoji.php');
/**
 * カスタムSmartyタグである、block.rewrite_link.php と連携し、以下を実現します。
 * パスが相対だった場合で、かつタグにrewrite_link='false'属性が付与されていなかった場合に動作します。
 * a: hrefを調整する。自動で任意のパラメータを自動埋め込みする。
 * form: actionを調整する。自動で任意のパラメータを自動埋め込みする。
 * link: hrefを調整する。
 * ".xhtml", ".html", ".htm"拡張子をはずす。
 *
 * デフォルト動作はtrueです。
 */
class mcweb_rewrite_link implements MCWEB_filter_rewrite_template
{
	var $smarty_tag;
	var $trans_params;

	/**
	 * コンストラクタ
	 * リンクやフォームに自動付与するパラメータを指定できる
	 * パラメータは、相対パスの場合のみ追加される
	 * @param	$trans_params	連想配列で、自動付与パラメータを指定する
	 * 							array('link' => array('guid' => 'ON', session_name() => session_id()), 'form' => array('guid' => 'ON'), 'hidden' => array(session_name() => session_id()))
	 */
	function __construct($trans_params)
	{
		$this->trans_params = $trans_params;
		$this->smarty_tag = 'mcweb_rewrite_link';
	}

	function rewrite($template_path, &$dom)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		//	相対パスの基点がずらされていた場合に、自動で<base>タグを挿入
		if (MCWEB_Framework::getInstance()->entry_path !== MCWEB_Framework::getInstance()->base_path)
		{
			$head = $dom->find('head');
			if (!empty($head))
			{
				$path = URL_FRAMEWORK_PHP . MCWEB_Framework::getInstance()->base_path;
				foreach($head as $tag)
				{
					$insert = new simple_html_dom_node($dom);
					$insert->outertext = '<base href="' . $path . '" />';
					array_unshift($tag->nodes, $insert);
				}
			}
		}

		$a = $dom->find('a');
		if (!empty($a))
		{
			foreach($a as $tag)
			{
				if (empty($tag->rewrite_link) || 'true' === $tag->rewrite_link)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && !empty($tag->href))
					{
						$content = $tag->href;
						$content = $LD . $this->smarty_tag . ' type="href" template_path="'. $template_path . '" params="$TRANS_LINK_PARAM"' . $RD . $content . $LD . '/' . $this->smarty_tag . $RD;
						$tag->href = $content;
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_link))
				{
					$tag->rewrite_link = null;
				}
			}
		}

		$link = $dom->find('link');
		if (!empty($link))
		{
			foreach($link as $tag)
			{
				if (empty($tag->rewrite_link) || 'true' === $tag->rewrite_link)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && 'stylesheet' === $tag->rel && !empty($tag->href))
					{
						$content = $tag->href;
						$content = $LD . $this->smarty_tag . ' type="href" template_path="'. $template_path . '"' . $RD . $content . $LD . '/' . $this->smarty_tag . $RD;
						$tag->href = $content;
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_link))
				{
					$tag->rewrite_link = null;
				}
			}
		}

		$form = $dom->find('form');
		if (!empty($form))
		{
			foreach($form as $tag)
			{
				if (empty($tag->rewrite_link) || 'true' === $tag->rewrite_link)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype)
					{
						$method = 'GET';
						if (!empty($tag->method) && 0 == strcasecmp(trim($tag->method), 'post'))
						{
							$method = 'POST';
						}

						$content = $tag->action;
						if ('POST' === $method)
						{
							$content = $LD . $this->smarty_tag . ' type="action" template_path="'. $template_path . '" params="$TRANS_FORM_PARAM"' . $RD . $content . $LD . '/' . $this->smarty_tag . $RD;

							//	TRANS_HIDDEN_POST_PARAMを埋め込む
							$insert = new simple_html_dom_node($dom);
							$insert->outertext = $LD . '$TRANS_HIDDEN_POST_PARAM' . $RD;
							array_unshift($tag->nodes, $insert);
						}
						else
						{
							$content = $LD . $this->smarty_tag . ' type="action" template_path="'. $template_path . '"' . $RD . $content . $LD . '/' . $this->smarty_tag . $RD;

							//	TRANS_HIDDEN_POST_PARAMを埋め込む
							$insert = new simple_html_dom_node($dom);
							$insert->outertext = $LD . '$TRANS_HIDDEN_GET_PARAM' . $RD;
							array_unshift($tag->nodes, $insert);
						}
						$tag->action = $content;
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_link))
				{
					$tag->rewrite_link = null;
				}
			}
		}

		$frame = $dom->find('frame');
		if (!empty($frame))
		{
			foreach($frame as $tag)
			{
				if (empty($tag->rewrite_link) || 'true' === $tag->rewrite_link)
				{
					if (HDOM_TYPE_ELEMENT == $tag->nodetype && !empty($tag->src))
					{
						$tag->src = $LD . $this->smarty_tag . ' type="src" template_path="'. $template_path . '"' . $RD . $tag->src . $LD . '/' . $this->smarty_tag . $RD;
					}
				}

				//	属性を削除
				if (!empty($tag->rewrite_link))
				{
					$tag->rewrite_link = null;
				}
			}
		}
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{

		if (!defined('CLIENT_ENCODING'))	throw new MCWEB_LogicErrorException('CLIENT_ENCODINGが定義されていません');
		if (!defined('SERVER_ENCODING'))	throw new MCWEB_LogicErrorException('SERVER_ENCODINGが定義されていません');

		//	リンクパラメータ関連の変数を、Smartyに登録する
		$TRANS_LINK_PARAM = array();
		$TRANS_FORM_PARAM = array();
		$TRANS_HIDDEN_POST_PARAM = array();
		$TRANS_HIDDEN_GET_PARAM = array();

		if (isset($this->trans_params['link']))		$TRANS_LINK_PARAM = $this->trans_params['link'];
		if (isset($this->trans_params['form']))		$TRANS_FORM_PARAM = $this->trans_params['form'];
		if (isset($this->trans_params['hidden']))	$TRANS_HIDDEN_POST_PARAM = $this->trans_params['hidden'];
		$TRANS_HIDDEN_GET_PARAM = array_merge($TRANS_FORM_PARAM, $TRANS_HIDDEN_POST_PARAM);

		$link = '';
		foreach($TRANS_LINK_PARAM as $key => $value)
		{
			if ('' == $link)	$link = $key . '=' . $value;
			else				$link .= '&amp;' . $key . '=' . $value;
		}

		$form = '';
		foreach($TRANS_FORM_PARAM as $key => $value)
		{
			if ('' == $form)	$form = $key . '=' . $value;
			else				$form .= '&amp;' . $key . '=' . $value;
		}

		$hidden = '';
		foreach($TRANS_HIDDEN_POST_PARAM as $key => $value)
		{
			$hidden .= "\n" . '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}

		$hidden_in_get_form = '';
		foreach($TRANS_HIDDEN_GET_PARAM as $key => $value)
		{
			$hidden_in_get_form .= "\n" . '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
		}

		//	リンク自動パラメータ系
		$output->htmltag('TRANS_LINK_PARAM', $link);
		if ('' == $link)
		{
			$output->htmltag('TRANS_LINK_PARAM_WITH_PARAMS', '');
			$output->htmltag('TRANS_LINK_PARAM_WITHOUT_PARAMS', '');
		}
		else
		{
			$output->htmltag('TRANS_LINK_PARAM_WITH_PARAMS', '&amp;' . $link);
			$output->htmltag('TRANS_LINK_PARAM_WITHOUT_PARAMS', '?' . $link);
		}

		//	フォーム自動パラメータ系
		$output->htmltag('TRANS_FORM_PARAM', $form);
		if ('' == $form)
		{
			$output->htmltag('TRANS_FORM_PARAM_WITH_PARAMS', '');
			$output->htmltag('TRANS_FORM_PARAM_WITHOUT_PARAMS', '');
		}
		else
		{
			$output->htmltag('TRANS_FORM_PARAM_WITH_PARAMS', '&amp;' . $form);
			$output->htmltag('TRANS_FORM_PARAM_WITHOUT_PARAMS', '?' . $form);
		}

		//	フォームの自動INPUTタグ
		$output->htmltag('TRANS_HIDDEN_POST_PARAM', $hidden);
		$output->htmltag('TRANS_HIDDEN_POST_PARAM_COPY', $hidden);

		$output->htmltag('TRANS_HIDDEN_GET_PARAM', $hidden_in_get_form);
		$output->htmltag('TRANS_HIDDEN_GET_PARAM_COPY', $hidden_in_get_form);
	}

	static function rewrite_link($content, $template_path, $params)
	{
		if (
				FALSE !== strpos($content, ':')				//	絶対パス、及び'mailto:'などをはじく
		)
		{
			return $content;
		}

		//	ページ内リンクかをチェック
		$in_page_link = (!empty($content) && '#' === $content[0]);

		//	#による、ページスクロール制御をきちんとURLの末尾につけるための処理
		$tail = '';
		$n = strpos($content, '#');
		if (FALSE !== $n)
		{
			$tail = substr($content, $n);
			$content = substr($content, 0, $n);
		}

		//	URLパラメータを分割
		$n = strpos($content, '?');
		if (FALSE === $n)
		{
			$link = $content;
			$url_param = array();
		}
		else
		{
			$link = substr($content, 0, $n);
			$tmp = substr($content, $n + 1);
			$tmp = str_replace('&amp;', '&', $tmp);
			$url_param = array();
			parse_str($tmp, $url_param);
		}

		//	拡張子を排除
		$link = str_replace('.xhtml', '', $link);
		$link = str_replace('.html', '', $link);
		$link = str_replace('.htm', '', $link);

		//	基点パス調整用タグを埋め込む
		if (!$in_page_link && (empty($link) || '/' !== $link[0]))
		{
			//	相対パスの基点
			$base_path = MCWEB_Framework::getInstance()->base_path;

			$base_path_args = explode('/', $base_path);
			$template_path_args = explode('/', $template_path);
			array_pop($base_path_args);
			array_pop($template_path_args);
			while(0 < COUNT($base_path_args) && 0 < COUNT($template_path_args))
			{
				if ($base_path_args[0] == $template_path_args[0])
				{
					array_shift($base_path_args);
					array_shift($template_path_args);
				}
				else
				{
					break;
				}
			}
			$adjust_path = '';
			foreach($base_path_args as $value)
			{
				$adjust_path .= '../';
			}
			foreach($template_path_args as $value)
			{
				$adjust_path .= $value . '/';
			}
			$link = MCWEB_Framework::normalize_relative_path($adjust_path . $link);
		}

		//	URLパラメータを追加
		if (!empty($params))
		{
			$url_param = array_merge($url_param, $params);
		}

		//	URLパラメータの文字エンコードを考慮
		if (CLIENT_ENCODING != SERVER_ENCODING)
		{
			$url_param = self::reencode($url_param, HTML_Emoji::getInstance());
		}

		//	要素結合
		if (0 == count($url_param))	$content = $link . $tail;
		else						$content = $link . '?' . http_build_query($url_param, '', '&amp;') . $tail;

	    return $content;
	}

	private static function reencode($arr, $emoji)
	{
		foreach($arr as &$value)
		{
			if (is_array($value))	$value = self::reencode($value, $emoji);
			else					$value = $emoji->convertEncoding($value, CLIENT_ENCODING, SERVER_ENCODING);
		}
		return $arr;
	}


}

?>