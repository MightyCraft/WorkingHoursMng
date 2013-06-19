<?php

class MCWEB_SceneForward implements MCWEB_InterfaceSceneForward
{
	private $param;
	private $path;

	function __construct($path)
	{
		$this->param = array();
		$this->path = $path;
	}

	function get_path()
	{
		return $this->path;
	}

	function get_param()
	{
		return $this->param;
	}

	function regist($type, $param)
	{
		$vars = array('GET', 'POST', 'COOKIE', 'FORWARD');

		if (is_array($param))
		{
			//	array('key' => 'value') 記法によるパラメータの追加
			//	array('key1' => 'value1', 'key2' => 'value2') にも対応
			foreach($param as $key => $value)
			{
				$this->param[$type][$key] = $value;
			}
		}
		else if (is_object($param))
		{
			if ($param instanceof MCWEB_InterfaceUrlParamAutoRegist)
			{
				//	MCWEB_InterfaceUrlParamAutoRegist を継承したクラスのインスタンスを渡した場合
				//	自動登録対象のメンバー変数に入っている値を、登録する
				$prefix_bin = $param->prefix_binary();
				$prefix_text = $param->prefix_text();

				if (strlen($prefix_text) < strlen($prefix_bin))	$arr = array($prefix_bin, $prefix_text);
				else											$arr = array($prefix_text, $prefix_bin);

				foreach($param as $key => $value)
				{
					foreach($arr as $prefix)
					{
						if (0 === strpos($key, $prefix))
						{
							$this->param[$type][substr($key, strlen($prefix))] = $value;
							break;
						}
					}
				}
			}
			else
			{
				throw new MCWEB_InternalServerErrorException();
			}
		}
		else
		{
			if (in_array($param, $vars))
			{
				//	'GET' 記法によるパラメータの追加
				//	'GET', 'POST', 'COOKIE', 'FORWARD' に対応
				foreach(MCWEB_Framework::getInstance()->url_params[$param] as $key => $value)
				{
					$this->param[$type][$key] = $value;
				}
			}
			else
			{
				throw new MCWEB_InternalServerErrorException();
			}
		}
	}

	function clear()
	{
		$this->param = array();
	}

}

?>
