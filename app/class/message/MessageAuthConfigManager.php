<?php
/**
 *	権限設定メッセージ管理クラス
 */
class MessageAuthConfigManager
{	
	/**
	 * コンストラクタ
	 */
	private function __construct()
	{
		// メッセージインスタンスを生成
		MessageManager::getInstance();
	}
	
	/**
	 * 唯一のインスタンスを取得します。
	 * @return MessageAuthConfigManager 唯一のインスタンス
	 */
	static public function getInstance()
	{
		static $s = NULL;
		if (NULL == $s)
		{
			$s = new MessageAuthConfigManager();
		}
		return $s;
	}
	
	/**
	 * 選択中の権限設定インデックスを取得
	 *
	 * @param unknown $auth_config 権限設定
	 * @return multitype 選択中の権限設定インデックス配列
	 */
	public function getAuthConfigSelectedIndex($auth_config_array)
	{
		if (!$auth_config_array)
		{
			return array();
		}
	
		// 選択済みのインデックスを取得
		$array_auth_config_key = $this->getAuthConfigKeys();
		$selected_index_array = array();
		foreach($auth_config_array as $key => $value)
		{
			$exists_key = array_search($value, $array_auth_config_key);
			if ($exists_key >= 0)
			{
				$selected_index_array[$exists_key] = $exists_key;
			}
		}
		return $selected_index_array;
	}
	
	/**
	 * 権限設定キーの配列を取得
	 * 
	 * @return multitype:multitype:string  
	 */
	public function getAuthConfigKeys()
	{
		return MessageDefine::$messages[MessageDefine::AUTH_CONFIG_KEY];
	}
	
	/**
	 * 権限設定キーの配列を取得
	 * 
	 * @return multitype:multitype:string  
	 */
	public function getAuthConfigNames()
	{
		return MessageDefine::$messages[MessageDefine::AUTH_CONFIG_NAME];
	}
}
?>