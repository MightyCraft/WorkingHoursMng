<?php
//	認証
require_once ('OAuth.php');

interface InterfaceCallbackUtilSNS
{
	/**
	 * 通信でエラーが発生した場合に呼ばれるコールバックメソッド
	 */
	function error($curl);
}

class UtilSNS
{
	//	コールバックインスタンス
	var $callback;

	//	OAuthConsumer インスタンス
	var $consumer;

	//	OAuthToken インスタンス
	var $token;

	//	OAuthRequest インスタンス
	var $request;

	/**
	 * コンストラクタ
	 */
	protected function __construct($consumer_key, $consumer_secret, InterfaceCallbackUtilSNS $callback = NULL)
	{
		//	コールバックインスタンス登録
		$this->callback = $callback;

		//	consumer インスタンス
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret, NULL);
	}

	/**
	 * Trustedモデル用のUtilSNSインスタンスを生成する
	 *
	 * @return UtilSNS インスタンス
	 */
	static function createTrusted($consumer_key, $consumer_secret, InterfaceCallbackUtilSNS $callback = NULL)
	{
		return new UtilSNS($consumer_key, $consumer_secret, $callback);
	}

	/**
	 * Proxyモデル用のUtilSNSインスタンスを生成する
	 *
	 * @return UtilSNS インスタンス
	 */
	static function createProxy($consumer_key, $consumer_secret, InterfaceCallbackUtilSNS $callback = NULL)
	{
		$c = new UtilSNS($consumer_key, $consumer_secret, $callback);

		//	ロードバランサー対応
		$host = (isset($_SERVER["HTTP_X_FORWARDED_HOST"])) ? $_SERVER["HTTP_X_FORWARDED_HOST"] : $_SERVER["HTTP_HOST"];
		$scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
		$http_url = $scheme . '://' . $host . ':' . $_SERVER['SERVER_PORT'] . str_replace("//", "/", $_SERVER['REQUEST_URI']);
		$c->request = OAuthRequest::from_request($_SERVER['REQUEST_METHOD'], $http_url);

		//	token インスタンス
		$c->token = new OAuthToken($c->request->get_parameter('oauth_token'), $c->request->get_parameter('oauth_token_secret'));

		return $c;
	}

	/**
	 * Authorizationシグネチャ検証
	 *
	 * @return bool 結果
	 */
	function auth()
	{
		if(isset($this->request))
		{
			$sign_method = new OAuthSignatureMethod_HMAC_SHA1();
			$sign = $this->request->get_parameter('oauth_signature');
			return $sign_method->check_signature($this->request, $this->consumer, $this->token, $sign);
		}
		else
		{
			return false;
		}
	}

	function get($url, $headers = array())
	{
		return $this->exec('GET', $url, $headers);
	}

	function post($url, $post_data, $headers = array())
	{
		return $this->exec('POST', $url, $headers, $post_data);
	}

	function put($url, $put_data, $headers = array())
	{
		return $this->exec('PUT', $url, $headers, $put_data);
	}

	function delete($url, $headers = array())
	{
		return $this->exec('DELETE', $url, $headers);
	}

	/**
	 * 実行部
	 *
	 * @param str   $http_method
	 * @param str   $url
	 * @param array $params
	 * @param bool  $set_token
	 */
	private function exec($http_method, $url, $headers = array(), $post_data = NULL)
	{
		$params = NULL;
		if (FALSE !== ($pos = strpos($url, '?')))
		{
			parse_str(substr($url, $pos + 1), $params);
			$url = substr($url, 0, $pos);
		}

		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $http_method, $url, $params);

		//	Sign the constructed OAuth request using HMAC-SHA1
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		$request->sign_request($signature_method, $this->consumer, $this->token);

		//	Make signed OAuth request to the Contacts API server
		if (!is_null($params))
		{
			$arr = array();
			foreach($params as $key => $value)
			{
				$arr[] = $key . '=' . urlencode($value);
			}
			$url = $url . '?' . implode('&', $arr);
		}

		//	CURL
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);	//	成功時にレスポンスを返す
		curl_setopt($curl, CURLOPT_FAILONERROR, false);		//	400以上のHTTPコードを取得
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);	//	cURL はサーバ証明書の検証を行いません。

		$headers = array_merge($headers, array($request->to_header())); // 認証ヘッダーのセット
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		if ($http_method == 'POST')
		{
			//	POST
			curl_setopt($curl, CURLOPT_POST, true);
			if (!is_null($post_data) && $post_data != "")
			{
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
			}
		}
		else if($http_method == 'PUT')
		{
			//	PUT
			curl_setopt($curl, CURLOPT_PUT, true);

			$putData = tmpfile();
			fwrite($putData, $post_data);
			fseek($putData, 0);

			curl_setopt($curl, CURLOPT_INFILE, $putData);
			curl_setopt($curl, CURLOPT_INFILESIZE, strlen($post_data));
		}
		else if($http_method == 'DELETE')
		{
			//	DELETE
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}

		$response = curl_exec($curl);
		$info = curl_getinfo($curl);

		if (curl_errno($curl) || $info['http_code'] >= 400)
		{
			//	エラーコールバック
			if (!is_null($this->callback))
			{
				$this->callback->error($curl);
			}

			if($http_method == 'PUT')
			{
				fclose($putData);
			}
			curl_close($curl);

			return FALSE;
		}
		else
		{
			if($http_method == 'PUT')
			{
				fclose($putData);
			}
			curl_close($curl);

			//	JSON_decode
			return json_decode($response, TRUE);
		}
	}

}
?>