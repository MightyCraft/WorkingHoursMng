<?php
/**
 * メッセージ管理クラス
 *
 */

class MessageManager
{
	/** 定義ファイルのCSVパス群(配列) */
	static protected $csv_paths;

	/** 定義ファイルのPHPパス */
	static protected $php_path;

	/**
	 * コンストラクタ
	 */
	protected function __construct()
	{
	}

	/**
	 * 唯一のインスタンスを取得します。
	 * @return MessageManager 唯一のインスタンス
	 */
	static public function getInstance()
	{
		static $s = NULL;
		if (NULL == $s)
		{
			$s = new MessageManager();
			// この時点で定義ファイル生成
			self::buildMessageDefine();
			require_once(self::$php_path);
		}

		return $s;
	}

	/**
	 * メッセージ管理で必要なパスをセットします
	 * @param string or array	$csv_path		CSVファイルパス(配列可)
	 * @param string			$php_path		PHPファイル(定義ファイル)パス
	 */
	static public function setPath($csv_path, $php_path)
	{
		if(isset($csv_path) && is_array($csv_path))
		{
			// 配列の場合はそのまま配列としてセット
			self::$csv_paths = $csv_path;
		}
		else
		{
			// 単体の場合は配列化してセット
			self::$csv_paths = array($csv_path);
		}
		self::$php_path = $php_path;
	}

	/**
	 * メッセージ文字列を取得します
	 *
	 * @param int		$id			メッセージID
	 * @param mixed		$args		可変長引数
	 * @return object		インスタンス
	 */
	public function sprintfMessage($id)
	{
		$args = func_get_args();
		array_shift($args);	// 先頭のメッセージIDを除外

		return $this->vsprintfMessageWithIndex($id, 0, $args);
	}

	/**
	 * メッセージ文字列を取得します
	 *
	 * @param int		$id			メッセージID
	 * @param array		$args		配列
	 * @return object		インスタンス
	 */
	public function vsprintfMessage($id, $args)
	{
		return $this->vsprintfMessageWithIndex($id, 0, $args);
	}

	/**
	 * インデックスを指定してメッセージ文字列を取得します
	 *
	 * @param int		$id			メッセージID
	 * @param int		$index		メッセージインデックス
	 * @param mixed		$args		可変長引数
	 * @return object		インスタンス
	 */
	public function sprintfMessageWithIndex($id, $index)
	{
		$args = func_get_args();
		array_shift($args);	// メッセージIDを除外
		array_shift($args);	// メッセージインデックスを除外

		return $this->vsprintfMessageWithIndex($id, $index, $args);
	}

	/**
	 * メッセージ文字列を取得します
	 *
	 * @param int		$id			メッセージID
	 * @param array		$args		配列
	 * @return object		インスタンス
	 */
	public function vsprintfMessageWithIndex($id, $index, $args)
	{
		if(!isset(MessageDefine::$messages[$id]))
		{
			throw new Exception('MessageManager::getMessage メッセージIDの指定が不正です');
		}
		if(!isset(MessageDefine::$messages[$id][$index]))
		{
			throw new Exception('MessageManager::getMessage メッセージインデックスの指定が不正です');
		}

		$message = MessageDefine::$messages[$id][$index];

		if(0 < count($args))
		{
			return vsprintf($message, $args);
		}

		return $message;
	}

	/**
	 * CSVファイルを読み込んでメッセージマネージャー用のメッセージ定義PHPを生成します。
	 * エラーが発生したらExceptionを生成します。
	 */
	static protected function buildMessageDefine()
	{
		$csv_paths = self::$csv_paths;
		$php_path = self::$php_path;

		$php_filemtime = -1;
		if(file_exists($php_path))
		{
			$php_filemtime = filemtime($php_path);
		}
		$regen_flg = FALSE;
		foreach($csv_paths as $csv_path)
		{
			if(!file_exists($csv_path))
			{
				throw new Exception("ERROR! : {$csv_path} not exist\n");
			}
			// CSVファイルと生成物の時刻チェック
			if(filemtime($csv_path) > $php_filemtime)
			{
				// 処理の必要あり
				$regen_flg = TRUE;
			}
		}
		if(!$regen_flg) return;

		$php_dir = dirname($php_path);
		if (!file_exists($php_dir))	{mkdir($php_dir, 0777, true);	chmod($php_dir, 0777);}

		$labels = array();
		$messages = array();
		$total = 0;
		foreach($csv_paths as $csv_path)
		{
			$row = 0;
			// CSVファイルの読込処理
			if(($fp = fopen($csv_path, "r")) === FALSE)
			{
				throw new Exception("ERROR! : {$csv_path} open failed\n");
			}
			while(($data = self::fgetcsv_reg($fp, 0)) !== FALSE)
			{
				$row++;
				$num = count($data);
				if($num < 2)
				{
				//	throw new Exception("ERROR! : {$csv_path} {$num} fields in line {$csv_path}({$row})\n");
					// 非有効行はスキップとする
					continue;
				}
				$total++;
				$label = array_shift($data);

				if(empty($label))
				{
				//	throw new Exception("ERROR! : {$csv_path} {$num} fields in line {$csv_path}({$row})\n");
					// 非有効行はスキップとする
					continue;
				}

				if(array_key_exists($label, $messages))
				{
					throw new Exception("ERROR! : {$data[0]} duplicate in line {$csv_path}({$row})\n");
				}

				$labels[$total] = $label;
				$messages[$label] = $data;
			}
			fclose($fp);
		}

		// メッセージ定義ファイルの出力処理
		$output = "";
		$output .= "<?php\n";
		$output .= "class MessageDefine\n";
		$output .= "{\n";
		foreach($labels as $row => $label)
		{
			$row = mb_convert_encoding($row, "utf8", "sjis");
			$label = mb_convert_encoding($label, "utf8", "sjis");

			$output .= "\tconst {$label} = {$row};\n";
		}
		$output .= "\n";
		$output .= "\tstatic public \$messages = array(\n";
		foreach($messages as $label => $message_array)
		{
			$label = mb_convert_encoding($label, "utf8", "sjis");
			$message_cols = array();
			foreach($message_array as $message)
			{
				$message = mb_convert_encoding($message, "utf8", "sjis");
				$message_cols[] = "'{$message}'";
			}
			$message = implode(', ', $message_cols);

			$output .= "\t\tself::{$label} => array({$message}),\n";
		}
		$output .= "\t);\n";
		$output .= "}\n";
		$output .= "?>\n";

		self::safety_file_overwrite($php_path, $output);

		chmod($php_path, 0777);
	}

	/**
	 * テンポラリファイルとファイルのリネームを使って安全にファイルを上書きする。
	 * 通常の方法でファイルを上書きしようとすると、データ量が大きかった場合にロック時間が長くなってしまうのを回避することができる。
	 *
	 * @param	$filename	ファイル名
	 * @param	$data		書き込みデータ
	 */
	static protected function safety_file_overwrite($filename, $data)
	{
		$tmp = tempnam(dirname($filename), 'TMP');
		file_put_contents($tmp, $data);

		//	既存ファイルを削除
		@unlink($filename);

		//	テンポラリファイルをリネーム
		rename($tmp, $filename);
	}

	/**
	 * ファイルポインタから行を取得し、CSVフィールドを処理する
	 * @param resource handle
	 * @param int length
	 * @param string delimiter
	 * @param string enclosure
	 * @return ファイルの終端に達した場合を含み、エラー時にFALSEを返します。
	 */
	static protected function fgetcsv_reg (&$handle, $length = null, $d = ',', $e = '"') {
		$d = preg_quote($d);
		$e = preg_quote($e);
		$_line = "";
		$eof = false;
		while ($eof != true) {
			$_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
			$itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);
			if ($itemcnt % 2 == 0) $eof = true;
		}
		$_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
		$_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
		preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
		$_csv_data = $_csv_matches[1];
		for($_csv_i=0;$_csv_i<count($_csv_data);$_csv_i++){
			$_csv_data[$_csv_i]=preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
			$_csv_data[$_csv_i]=str_replace($e.$e, $e, $_csv_data[$_csv_i]);
		}
		return empty($_line) ? false : $_csv_data;
	}
}
?>