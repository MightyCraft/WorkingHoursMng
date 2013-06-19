<?php

/**
 * <!--%include "filename"-->という書式のHTMLコメントを、Smartyのincludeに置換します。
 */
class mcweb_comment_to_include implements MCWEB_filter_rewrite_template
{
	private $template_path;

	function __construct()
	{
	}

	private function replace($matches)
	{
		global $smarty;
		$LD = $smarty->left_delimiter;
		$RD = $smarty->right_delimiter;

		if (!empty($matches[1]) && '/' === $matches[1][0])
		{
			$path = DIR_HTML . $matches[1];
		}
		else
		{
			if (1 === substr_count($this->template_path, '/'))	$path = '/' . $matches[1];
			else												$path = dirname($this->template_path) . '/' . $matches[1];
			$path = MCWEB_Framework::normalize_relative_path($path);
			if (FALSE !== strpos($path, '/../'))	throw new MCWEB_InternalServerErrorException();
			$path = DIR_HTML . $path;
		}

		return $LD . 'include file="file:'. $path . '"' . $RD;
	}

	function rewrite($template_path, &$dom)
	{
		$this->template_path = $template_path;
		$tpl_source = $dom->__toString();
		$tpl_source = preg_replace_callback('/<!--%include[\s]+"(.*)"[\s]*-->/', array($this, 'replace'), $tpl_source);
		$dom = str_get_dom($tpl_source);
	}

	function run($action_path, $scene, MCWEB_InterfaceSceneOutputVars $output)
	{
	}
}

?>