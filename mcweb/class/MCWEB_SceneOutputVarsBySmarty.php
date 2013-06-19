<?php

class MCWEB_SceneOutputVarsBySmarty implements MCWEB_InterfaceSceneOutputVars
{
	private $smarty;
	private $template_path;

	function __construct($smarty)
	{
		$this->smarty = $smarty;
		array_push($smarty->plugins_dir, DIR_MCWEB . '/smarty_plugins');
		$smarty->load_filter('pre', 'mcweb_rewrite');
	}

	function setTemplate($template_path)
	{
		$this->template_path = $template_path;
	}

	function getTemplate()
	{
		return $this->template_path;
	}

	function outputTemplate()
	{
		if (!$this->smarty->template_exists($this->getTemplate()))
		{
			return FALSE;
		}
		$this->smarty->display($this->getTemplate());
		return TRUE;
	}

	function text($tpl_var, $value = null)
	{
		if (is_array($tpl_var) || is_object($tpl_var))
		{
			if (is_object($tpl_var) && !($tpl_var instanceof MCWEB_InterfaceSceneOutputableVarsObject))
			{
				return;
			}
			$result = array();
			foreach($tpl_var as $key => $val)
			{
				if ($key != '')
				{
					$result[$key] = MCWEB_SceneOutputVarsBySmarty::array_htmlentities($val);
				}
			}
			$this->smarty->assign($result, $value);
		}
		else if ('' != $tpl_var)
		{
			if (null != $value)
			{
				$value = MCWEB_SceneOutputVarsBySmarty::array_htmlentities($value);
			}
			$this->smarty->assign($tpl_var, $value);
		}
	}

	function htmltag($tpl_var, $value = null)
	{
		$this->smarty->assign($tpl_var, $value);
	}

	function get($tpl_var)
	{
		return $this->smarty->get_template_vars($tpl_var);
	}

	function clear()
	{
		$this->smarty->clear_all_assign();
		$this->template_path = NULL;
	}

	static function array_htmlentities($value)
	{
		if (null === $value)
		{
			return null;
		}
		else if (is_array($value) || is_object($value))
		{
			if (is_object($value) && !($value instanceof MCWEB_InterfaceSceneOutputableVarsObject))
			{
				return array();
			}
			$result = array();
			foreach($value as $key => $val)
			{
				$result[$key] = MCWEB_SceneOutputVarsBySmarty::array_htmlentities($val);
			}
			return $result;
		}
		else
		{
			if (!defined('SERVER_ENCODING'))	throw new MCWEB_LogicErrorException('SERVER_ENCODINGが定義されていません');
			return htmlspecialchars($value, ENT_QUOTES, SERVER_ENCODING);
		}
	}

}
?>
