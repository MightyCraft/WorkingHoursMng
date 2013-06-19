<?php


require(DIR_MCWEB . '/interface/interfaces.php');
class MCWEB_Framework
{
	var $request_uri;

	var $commandline;	//	コマンドライン起動ならばTRUE

	//	下記4つはどれもアクション絶対パス('/hoge/foo'という記述タイプ)
	var $entry_path;
	var $action_path;
	var $template_path;
	var $base_path;		//	相対パスの基点。通常は$template_pathと同一

	var $to_site_root_path;
	var $to_action_root_path;

	var $filter_factory;

	var $url_params;

	var $dir_autoload;

	var $scene_output_vars;

	private function __construct()
	{
		//	$this->request_uri; を決定しておく
		if (isset($_SERVER['HTTP_X_REWRITE_URL']))
		{
			// IISサーバー
			$this->request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		else if (isset($_SERVER['REQUEST_URI']))
		{
			$this->request_uri = $_SERVER['REQUEST_URI'];

			//	http://から始まる、フルURIだった場合の対策
			if ('/' !== $this->request_uri[0])
			{
				$n = strpos($this->request_uri, $_SERVER['SERVER_NAME']);
				if (FALSE !== $n)
				{
					$this->request_uri = substr($this->request_uri, $n + strlen($_SERVER['SERVER_NAME']));
				}
			}
		}
		else if (isset($_SERVER['ORIG_PATH_INFO']))
		{
			// IIS 5.0、もしくはCGI版のPHP
			$this->request_uri = $_SERVER['ORIG_PATH_INFO'];
			if (!empty($_SERVER['QUERY_STRING']))
			{
				$this->request_uri .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
	}

	/**
	 * 唯一のインスタンスを取得します。
	 * @return MCWEB_Framework 唯一のインスタンス
	 */
	static function getInstance()
	{
		static $s = NULL;
		if (NULL == $s)
		{
			$s = new MCWEB_Framework();
		}
		return $s;
	}

	/**
	 * MCWEB_filter_factoryを設定する。
	 */
	function setFilterFactory(MCWEB_filter_factory $factory)
	{
		$this->filter_factory = $factory;
	}

	/**
	 * テンプレート出力インターフェースを設定します。
	 */
	function setSceneOutputVars(MCWEB_InterfaceSceneOutputVars $interface)
	{
		$this->scene_output_vars = $interface;
	}

	/**
	 * クラスファイルをオートロードする対象のフォルダを指定します。
	 */
	function setClassAutoLoadDir()
	{
		$this->dir_autoload = func_get_args();
	}

	/**
	 * 出力実行
	 */
	function run($commandline = FALSE)
	{
		//	コマンドライン起動かどうか
		$this->commandline = $commandline;

		//	出力インターフェース
		$access = &$this->scene_output_vars;

		//	PATH_INFOを取得
		if (!$this->commandline)
		{
			//	register_globalsは絶対に許可しません
			if (FALSE != ini_get('register_globals')){throw new MCWEB_ServerConfigurationErrorException('error: register_globals');}

			//	magic_quotes_gpcは絶対に許可しません
			if (FALSE != ini_get('magic_quotes_gpc')){throw new MCWEB_ServerConfigurationErrorException('error: magic_quotes_gpc');}

			//	releaseで、かつDEVELOPMENTがfalseならば許可しません
			if (!DEVELOPMENT && 'release' === DIR_DEFINE && FALSE != ini_get('display_errors')){throw new MCWEB_ServerConfigurationErrorException('error: display_errors');}

			if (empty($_SERVER['PATH_INFO']))
			{
				throw new MCWEB_ActionNotFoundException();
			}
			$path = $_SERVER['PATH_INFO'];

			//	不正な文字を含むパスは拒否(特に '..' が含まれていると危ない)
			if (0 == preg_match("/^[a-zA-Z0-9\/_-]+$/", $path))
			{
				throw new MCWEB_ActionNotFoundException();
			}

			//	フレームワークのエントリーポイントphpが置いてあるフォルダへの、相対パスを生成
			$to_action_root_path = '';
			$n = substr_count($path, '/');
			for($i = 0; $i < ($n - 1); ++$i)
			{
				$to_action_root_path .= '../';
			}
			$this->to_action_root_path = $to_action_root_path;
			$access->text('TO_ACTION_ROOT_PATH', $this->to_action_root_path);
			$this->to_site_root_path = '../' . $to_action_root_path;
			$access->text('TO_SITE_ROOT_PATH', $this->to_site_root_path);
			unset($i);
			unset($n);
			unset($relative_path);
		}
		else
		{
			$path = $_SERVER['argv'][1];
			$this->to_site_root_path = '';
			$access->text('TO_SITE_ROOT_PATH', '');

			//	シーン名が省略されている場合は、'index'ページへ飛ばす
			if (empty($path))
			{
				$path = '/index';
			}
			else
			{
				if (!empty($path) && '/' != $path[0])
				{
					$path = '/' . $path;
				}
			}
		}

		//	初期パスを保存
		$this->entry_path = $path;
		$access->text('ENTRY_PATH', $this->entry_path);


		//	通常の$_POSTがうまくセットできなかった場合の処置
		//	3GCの初期SH端末などで再現
		if (empty($_POST))
		{
			$_POST = array();
			parse_str(file_get_contents('php://input'), $_POST);
		}


		//	$_REQUESTの利用は禁止
		//	unsetだと外部ライブラリで利用している箇所でワーニングが出るため、空配列を代入にする
		$_REQUEST = array();


		//	出力バッファリング開始
		ob_start();
		ob_start();

		//	スタートアップフィルタを実行
		$filters = MCWEB_Framework::extract_filters($this->filter_factory->startup($this->entry_path), $this->entry_path);
		foreach($filters as $filter)
		{
			$filter->run($this->entry_path);
		}
		$tmp = $this->entry_path;
		$this->entry_path = $path;	//	startupフィルタの中でentry_pathが書き換えられる時があるので（実験中）
		$path = $tmp;

		//	スーパーグローバル変数の中身を退避
		$this->url_params['GET'] = $_GET;
		$this->url_params['POST'] = $_POST;
		$this->url_params['COOKIE'] = $_COOKIE;
		$this->url_params['FORWARD'] = array();
		$_GET = array();
		$_POST = array();
		$_COOKIE = array();

		//	シーン処理開始
		$forward_cnt = 0;
		$forward = NULL;
		while(TRUE)
		{
			if (NULL != $forward)
			{
				//	委譲が行われた
				++$forward_cnt;

				//	委譲クラスの中でセットされているパラメータを、委譲先のシーンにおけるグローバルパラメータとしてセットする
				$param = $forward->get_param();
				$this->url_params = array();
				foreach($param as $key => $value)
				{
					$this->url_params[$key] = $value;
				}

				//	委譲先シーンのパスを取得
				$path = $forward->get_path();

				//	相対パス
				if ('/' !== $path[0])
				{
					if (1 === substr_count($this->action_path, '/'))	$path = '/' . $path;
					else												$path = MCWEB_Framework::normalize_path(dirname($this->action_path) . '/' . $path);
					if (FALSE === $path)
					{
						//	ルートディレクトリより上に遡ろうとした
						throw new MCWEB_InternalServerErrorException();
					}
				}

				//	不正な文字を含むパスは拒否(特に '..' が含まれていると危ない)
				if (0 == preg_match("/^[a-zA-Z0-9\/_-]+$/", $path))
				{
					throw new MCWEB_InternalServerErrorException();
				}

				//	クリア
				$forward = NULL;
			}

			//	URLパラメータ取得クラス
			$url_param = new MCWEB_UrlParam($this->url_params);

			//	シーン名（アクション名）を決定する
			$_SERVER['PATH_INFO'] = '';
			if ('/' === $path[strlen($path) - 1] && file_exists($php_name = DIR_ACTION . $path . 'index.php'))
			{
				$path .= 'index';
			}
			else
			{
				if ('/' === $path[strlen($path) - 1])
				{
					$path = substr($path, 0, -1);
					$_SERVER['PATH_INFO'] = '/';
				}
				while('' !== $path)
				{
					if (file_exists($php_name = DIR_ACTION . $path . '.php'))
					{
						break;
					}
					$n = strrpos($path, '/');
					$_SERVER['PATH_INFO'] = substr($path, $n) . $_SERVER['PATH_INFO'];
					$path = substr($path, 0, $n);
				}
				if ('' === $path)
				{
					throw new MCWEB_ActionNotFoundException();
				}
			}
			$this->action_path = $path;


			//	シーン生成前フィルタを実行
			$filters = MCWEB_Framework::extract_filters($this->filter_factory->scene_create($this->action_path), $this->action_path);
			foreach($filters as $filter)
			{
				$f = $filter->run($path, $url_param);
				if (NULL != $f)
				{
					$forward = $f;
					break;
				}
			}
			if (NULL != $forward)
			{
				continue;
			}

			//	シーンクラス生成
			require_once($php_name);
			$class_name = str_replace('/', '_', $path);
			$class_name = str_replace('-', '_', $class_name);
			if (!class_exists($class_name, FALSE))
			{
				throw new MCWEB_ActionNotFoundException();
			}
			$c = new $class_name();
			$access->setTemplate($path);
			$access->text('SCENE_PATH', $path);
			if (!($c instanceof MCWEB_InterfaceScene))
			{
				throw new MCWEB_InternalServerErrorException();
			}
			if (0 == $forward_cnt && ($c instanceof MCWEB_InterfaceScenePrivate) && !$this->commandline)
			{
				//	MCWEB_InterfaceScenePrivateをimplementsしているシーンはForwardからしか呼べない
				//	ただしコマンドライン実行の場合は除く
				throw new MCWEB_ActionNotFoundException();
			}


			//	input前フィルタを実行
			$filters = MCWEB_Framework::extract_filters($this->filter_factory->scene_input($this->action_path), $this->action_path);
			foreach($filters as $filter)
			{
				$f = $filter->run($path, $c, $url_param);
				if (NULL != $f)
				{
					$forward = $f;
					break;
				}
			}
			if (NULL != $forward)
			{
				continue;
			}

			//	input処理
			if (NULL != ($forward = $c->do_input($url_param)))
			{
				continue;
			}


			//	check前フィルタを実行
			$filters = MCWEB_Framework::extract_filters($this->filter_factory->scene_check($this->action_path), $this->action_path);
			foreach($filters as $filter)
			{
				$f = $filter->run($path, $c);
				if (NULL != $f)
				{
					$forward = $f;
					break;
				}
			}
			if (NULL != $forward)
			{
				continue;
			}

			//	check処理
			if (NULL != ($forward = $c->do_check()))
			{
				continue;
			}


			//	task前フィルタを実行
			$filters = MCWEB_Framework::extract_filters($this->filter_factory->scene_task($this->action_path), $this->action_path);
			foreach($filters as $filter)
			{
				$f = $filter->run($path, $c, $access);
				if (NULL != $f)
				{
					$forward = $f;
					break;
				}
			}

			//	task処理
			if (NULL != ($forward = $c->do_task($access)))
			{
				continue;
			}

			//	委譲処理は行われなかった
			break;
		}

		//	シーンクラスのメンバー変数を自動登録する
		//	ただし、$sceneがMCWEB_InterfaceSceneOutputableVarsObjectをimplementsしている必要あり
		$access->text($c);

		//	draw前フィルタ
		$filters = MCWEB_Framework::extract_filters($this->filter_factory->scene_draw($this->action_path), $this->action_path);
		foreach($filters as $filter)
		{
			$filter->run($path, $c, $access);
		}

		//	描画処理
		$c->do_draw($access);

		//	rewriteフィルタ
		$filters = MCWEB_Framework::extract_filters($this->filter_factory->rewrite_template($this->action_path), $this->action_path);
		foreach($filters as $filter)
		{
			$filter->run($path, $c, $access);
		}

		//	テンプレート決定
		$this->template_path = $access->getTemplate();

		//	テンプレート表示処理
		if (!is_null($this->template_path))
		{
			if (empty($this->template_path))
			{
				throw new MCWEB_TemplateNotFoundException();
			}
			if ('/' !== $this->template_path[0])
			{
				//	相対パス
				if (1 === substr_count($this->action_path, '/'))	$path = '/' . $this->template_path;
				else												$path = MCWEB_Framework::normalize_path(dirname($this->action_path) . '/' . $this->template_path);
				if (FALSE === $path)
				{
					//	ルートディレクトリより上に遡ろうとした
					throw new MCWEB_TemplateNotFoundException();
				}
				$this->template_path = $path;
			}
			$access->text('TEMPLATE_PATH', $this->template_path);

			//	相対パスの基点は、デフォルトではentry_pathである
			$this->base_path = $this->entry_path;

			//	テンプレート名の決定
			$template_filename = DIR_HTML . $this->template_path . '.html';

			//	テンプレート出力
			$access->setTemplate($template_filename);
			if (!$access->outputTemplate())
			{
				throw new MCWEB_TemplateNotFoundException();
			}
		}
		else
		{
			$access->text('TEMPLATE_PATH', '');
		}

		//	出力コンテンツを取得
		ob_end_flush();
		$contents = ob_get_contents();
		ob_end_clean();

		//	アウトプットフィルタを実行
		$filters = MCWEB_Framework::extract_filters($this->filter_factory->post_output($this->template_path), $this->template_path);
		foreach($filters as $filter)
		{
			$filter->run($path, $contents);
		}

		//	最終出力
		echo $contents;
	}

	/**
	 * MCWEB_InterfaceUrlParamAutoRegistをimplementsしているクラスのメンバー変数に、URLパラメータのデータをセットする
	 */
	static function auto_regist($c, $url_param)
	{
		if (!($c instanceof MCWEB_InterfaceUrlParamAutoRegist))
		{
			return;
		}
		$vars = $c->regist_types();
		$prefix_bin = $c->prefix_binary();
		$prefix_text = $c->prefix_text();

		if (strlen($prefix_text) < strlen($prefix_bin))	$arr = array(VAR_TYPE_BINARY, VAR_TYPE_STRING);
		else											$arr = array(VAR_TYPE_STRING, VAR_TYPE_BINARY);

		foreach($c as $key => &$ref_value)
		{
			foreach($arr as $var_type)
			{
				switch($var_type)
				{
					case VAR_TYPE_BINARY:	$prefix = $prefix_bin;	break;
					case VAR_TYPE_STRING:	$prefix = $prefix_text;	break;
					default:				continue;
				}
				if (0 === strpos($key, $prefix))
				{
					$name = substr($key, strlen($prefix));
					foreach($vars as $type)
					{
						$data = NULL;
						if		('GET' === $type)		$data = $url_param->get($var_type, $name);
						else if ('POST' === $type)		$data = $url_param->post($var_type, $name);
						else if ('COOKIE' === $type)	$data = $url_param->cookie($var_type, $name);
						else if ('FORWARD' === $type)	$data = $url_param->forward($var_type, $name);
						if (!is_null($data))
						{
							$ref_value = $data;
						}
					}
					break;
				}
			}
		}
	}

	/**
	 * パスの正規化を行う
	 * ルートより上に'..'でかけあがろうとした場合、エラーとしてFALSEを返す
	 */
	static function normalize_path($path)
	{
		$path = str_replace('\\', '/', $path);

		$pre_slash = false;
		$post_slash = false;
		if (!empty($path) && '/' == $path[0])
		{
			$pre_slash = true;
		}
		if (!empty($path) && '/' == $path[strlen($path) - 1])
		{
			$post_slash = true;
		}
		$path_args = explode('/', $path);
		while(FALSE !== ($ret = array_search("", $path_args)))
		{
			array_splice($path_args, $ret, 1);
		}
		while(FALSE !== ($ret = array_search(".", $path_args)))
		{
			array_splice($path_args, $ret, 1);
		}
		while(FALSE !== ($ret = array_search("..", $path_args)))
		{
			if (0 == $ret)
			{
				return FALSE;
			}
			array_splice($path_args, $ret - 1, 2);
		}
		$path = '';
		foreach($path_args as $value)
		{
			$path .= '/' . $value;
		}
		if ($post_slash)
		{
			$path .= '/';
		}

		if (empty($path))	return '';
		if ($pre_slash)		return $path;
		else				return substr($path, 1);
	}

	/**
	 * 相対パスの正規化を行う
	 */
	static function normalize_relative_path($path)
	{
		$path = str_replace('\\', '/', $path);

		$pre_slash = false;
		$post_slash = false;
		if (!empty($path) && '/' == $path[0])
		{
			$pre_slash = true;
			$path = substr($path, 1);
		}
		$path_args = explode('/', $path);
		while(FALSE !== ($ret = array_search(".", $path_args)))
		{
			array_splice($path_args, $ret, 1);
		}

		$n = 0;
		while(FALSE !== ($ret = array_search("..", $path_args)))
		{
			if (0 == $ret)
			{
				++$n;
				array_shift($path_args);
				continue;
			}
			array_splice($path_args, $ret - 1, 2);
		}
		$path = '';
		for($i = 0; $i < $n; ++$i)
		{
			$path .= '../';
		}
		$path .= implode('/', $path_args);

		if ($pre_slash)	$path = '/' . $path;
		if (empty($path))	return '';
		else				return $path;
	}

	/**
	 * 有効なフィルター名を抽出し、名前の配列として返す
	 */
	static function extract_filters($filters, $path)
	{
		$ret = array();
		if (NULL != $filters)
		{
			foreach($filters as $value)
			{
				if (is_null($value))
				{
					//	NULLは省きます
				}
				else if (is_array($value))
				{
					$n = count($value);
					for($i = 1; $i < $n; ++$i)
					{
						if (is_string($value[$i]))
						{
							if (mb_ereg_match($value[$i], $path))
							{
								break;
							}
						}
						else
						{
							if ($value[$i]->run($path))
							{
								break;
							}
						}
					}
					if ($n == $i)
					{
						$ret[] = $value[0];
					}
				}
				else
				{
					$ret[] = $value;
				}
			}
		}
		return $ret;
	}

}

//	自動クラスローダー
function __autoload($class)
{
	if (class_exists($class, FALSE))
	{
		return;
	}
	$dir_autoload = MCWEB_Framework::getInstance()->dir_autoload;
	if (!empty($dir_autoload))
	{
		foreach($dir_autoload as $dir)
		{
			if (file_exists($path = $dir . '/' . $class . '.php'))
			{
				require($path);
				return;
			}
		}
	}

	if (defined('DIR_MCWEB'))
	{
		$dir_autoload = array(
			  DIR_MCWEB . '/class'
			, DIR_MCWEB . '/class/sns'
			, DIR_MCWEB . '/class/validator'
			, DIR_MCWEB . '/class/database'
			, DIR_MCWEB . '/class/database/connector'
			, DIR_MCWEB . '/class/database/accessor'
			, DIR_MCWEB . '/class/message'
			, DIR_MCWEB . '/filter/startup'
			, DIR_MCWEB . '/filter/scene_create'
			, DIR_MCWEB . '/filter/scene_input'
			, DIR_MCWEB . '/filter/scene_check'
			, DIR_MCWEB . '/filter/scene_task'
			, DIR_MCWEB . '/filter/scene_draw'
			, DIR_MCWEB . '/filter/rewrite_template'
			, DIR_MCWEB . '/filter/post_output'
		);
		foreach($dir_autoload as $dir)
		{
			if (file_exists($path = $dir . '/' . $class . '.php'))
			{
				require($path);
				return;
			}
		}
	}
}

class MCWEB_Exception extends Exception
{}

class MCWEB_LogicErrorException extends Exception
{}

class MCWEB_InternalServerErrorException extends MCWEB_Exception
{}

class MCWEB_BadRequestException extends MCWEB_Exception
{}

class MCWEB_ActionNotFoundException extends MCWEB_Exception
{}

class MCWEB_TemplateNotFoundException extends MCWEB_Exception
{}

class MCWEB_ServerConfigurationErrorException extends MCWEB_Exception
{}

class MCWEB_DenyCarrierException extends MCWEB_Exception
{}

class MCWEB_DenyUidException extends MCWEB_Exception
{}

?>