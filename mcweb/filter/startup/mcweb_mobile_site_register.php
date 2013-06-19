<?php

/**
 * 会員登録/削除を行うための割り込みフィルタです。
 */
class mcweb_mobile_site_register implements MCWEB_filter_startup
{
	protected $regist;
	protected $unregist;

	public static $AU_CP_CODE = NULL;
	public static $AU_WSDL_FILENAME = NULL;

	/**
	 * コンストラクタ
	 */
	public function __construct(MCWEB_InterfaceMobileSiteRegister $regist, MCWEB_InterfaceMobileSiteUnregister $unregist)
	{
		$this->regist = $regist;
		$this->unregist = $unregist;
	}

	public function run($entry_path)
	{
		if (!defined('DEFINE_INI_GROUP'))		throw new MCWEB_LogicErrorException('DEFINE_INI_GROUPが定義されていません');
		if (!defined('CLIENT_CARRIER'))			throw new MCWEB_LogicErrorException('CLIENT_CARRIERが定義されていません');

		//	登録URLか、削除URLか、それ以外かを調べる
		if ($entry_path === $this->regist->actionEntry())
		{
			$callback = $this->regist;
		}
		else if ($entry_path === $this->unregist->actionEntry())
		{
			$callback = $this->unregist;
		}
		else
		{
			return;
		}

		if ('local' === DEFINE_INI_GROUP)
		{
			return $this->local($callback);
		}
		else
		{
			return $this->normal($callback);
		}
	}

	protected function normal($callback)
	{
		if ('DOCOMO' === CLIENT_CARRIER || 'MOVA' === CLIENT_CARRIER)
		{
			if (NULL != getenv('HTTP_USER_AGENT'))
			{
				$callback->illegalAccess();
				throw new MCWEB_ActionNotFoundException();
			}
			switch($callback->task())
			{
				case MCWEB_InterfaceMobileSiteRegister::SUCCEEDED:
				case MCWEB_InterfaceMobileSiteRegister::ALREADY_DONE:
					header('Content-Type: text/plain');
					header('Content-Length: 3');
					echo "OK\n";
					exit;

				case MCWEB_InterfaceMobileSiteRegister::FAILED:
					header('Content-Type: text/plain');
					header('Content-Length: 3');
					echo "NG\n";
					exit;

				default:
					throw new MCWEB_LogicErrorException();
			}
		}
		else if ('SOFTBANK' === CLIENT_CARRIER)
		{
			if (!isset($_SERVER['HTTP_X_JPHONE_REGISTERED']))	// ヘッダーがあるかどうかでチェック
			{
				$callback->illegalAccess();
				throw new MCWEB_ActionNotFoundException();
			}
			if ('0' === $_GET['reg'] || '1' === $_GET['reg'])
			{
				switch($callback->task())
				{
					case MCWEB_InterfaceMobileSiteRegister::SUCCEEDED:
					case MCWEB_InterfaceMobileSiteRegister::ALREADY_DONE:
						MCWEB_Framework::getInstance()->entry_path = $callback->actionSucceeded();
						return;

					case MCWEB_InterfaceMobileSiteRegister::FAILED:
						header('HTTP', true, 500);
						header('x-jphone-status: ERR800');
						MCWEB_Framework::getInstance()->entry_path = $callback->actionFailed();
						return;

					default:
						throw new MCWEB_LogicErrorException();
				}
			}
			else if ('2' === $_GET['reg'])
			{
				MCWEB_Framework::getInstance()->entry_path = $callback->actionCanceled();
				return;
			}
			else
			{
				throw new MCWEB_ActionNotFoundException();
			}
		}
		else if ('AU' === CLIENT_CARRIER)
		{
			// 課金の成否をチェックします。
			require_once(DIR_MCWEB . '/class/regist/PremiumEz.php');

			if (is_null(self::$AU_CP_CODE))			throw new MCWEB_LogicErrorException('mcweb_mobile_site_register::$AU_CP_CODE がセットされていません');
			if (is_null(self::$AU_WSDL_FILENAME))	throw new MCWEB_LogicErrorException('mcweb_mobile_site_register::$AU_WSDL_FILENAME がセットされていません');
			if (false === PremiumEz::checkDGConfReq(self::$AU_CP_CODE, self::$AU_WSDL_FILENAME))
			{
				$callback->illegalAccess();
				throw new MCWEB_ActionNotFoundException();
			}
			switch($callback->task())
			{
				case MCWEB_InterfaceMobileSiteRegister::SUCCEEDED:
				case MCWEB_InterfaceMobileSiteRegister::ALREADY_DONE:
					MCWEB_Util::redirectAction($callback->actionSucceeded());
					return;

				case MCWEB_InterfaceMobileSiteRegister::FAILED:
					MCWEB_Util::redirectAction($callback->actionFailed());
					return;

				default:
					throw new MCWEB_LogicErrorException();
			}
		}
		else
		{
			throw new MCWEB_ActionNotFoundException();
		}
	}

	protected function local($callback)
	{
		if (!isset($_GET['type']))
		{
			if (!empty($_SERVER['QUERY_STRING']))	$query = '&' . $_SERVER['QUERY_STRING'];
			else									$query = '';
			echo '<html><head><title>local debug page</title></head><body>select type for debug.<br><br><a href="?type=commit' . $query . '">user push "OK" button.</a><br><br><a href="?type=cancel' . $query . '">user push "CANCEL" button.</a><br></body></html>';
			exit;
		}

		if ('cancel' === $_GET['type'])
		{
			MCWEB_Framework::getInstance()->entry_path = $callback->actionCanceled();
			return;
		}

		switch($callback->task())
		{
			case MCWEB_InterfaceMobileSiteRegister::SUCCEEDED:
			case MCWEB_InterfaceMobileSiteRegister::ALREADY_DONE:
				MCWEB_Framework::getInstance()->entry_path = $callback->actionSucceeded();
				return;

			case MCWEB_InterfaceMobileSiteRegister::FAILED:
				MCWEB_Framework::getInstance()->entry_path = $callback->actionFailed();
				return;

			default:
				throw new MCWEB_LogicErrorException();
		}
	}
}

?>