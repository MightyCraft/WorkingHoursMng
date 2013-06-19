<?php

class SwfHack
{
	//	ファイル分解結果
	private $header;
	private $tag;

	//	CharacterIDを添え字にした、$tagへの参照テーブル
	private $ref_tag_table;

	//	JPEGTablesへの参照
	private $ref_jpeg_table;

	//	登録変数(DoActionTagを新規生成し、SWF内の変数にデータの登録を行う)
	private $params;

	private function __construct()
	{}

	/**
	 * 編集元となるSWFを指定し、インスタンスを作成する。
	 * @param	$filename	読み込むSWFのファイル名。
	 * @return				インスタンス
	*/
	static function createInstance($filename)
	{
		$file = file_get_contents($filename);
		if (FALSE === $file)
		{
			return NULL;
		}

		//	圧縮タイプかどうか調べる
		if (0 == strncmp($file, 'CWS', 3))
		{
			$file = substr($file, 0, 8) . gzUncompress(substr($file, 8));
		}

		$c = new SwfHack;

		// ヘッダの長さは可変なので途中まで読んでから確定させる
		// 背景色設定タグよりも前に DoActionTag 挿入するとエラーでるので
		// 便宜的にそいつもヘッダ扱い($headlen 計算の末尾の "+5" 部分)
		$rb			= ord($file[8]) >> 3; // rectbit
		$rb			= $rb * 4 + 5;
		$headlen	= ceil($rb / 8)
					+ 8	//	先頭8バイト("FWS" + version + filesize(int))
					+ 4	//	フレームレート(short) + 全体フレーム数(short)
					; 

		//	ヘッダを保持
		$c->header = substr($file, 0, $headlen);

		//	タグ解析
		$filesize = strlen($file);
		$offset = $headlen;

		//	タグリストの生成開始
		$c->tag = array();
		$c->ref_tag_table = array();

		while($offset < $filesize)
		{
			$s = unpack("v", substr($file, $offset, 2));
			$s = $s[1];
			$size_body = $s & 0x3F;
			$tagtype = ($s >> 6) & 0x03FF;

			$offset_header = $offset;
			$offset += 2;
			$tagsize_long = (0x3F === $size_body);
			if ($tagsize_long)
			{
				$s = unpack("V", substr($file, $offset, 4));
				$size_body = $s[1];
				$offset += 4;
			}
			$offset += $size_body;

			//	タグを取得
			$c->tag[] = substr($file, $offset_header, $offset - $offset_header);

			switch($tagtype)
			{
			case 2:		//	DefineShape
			case 6:		//	DefineBits
			case 20:	//	DefineBitsLossless
			case 21:	//	DefineBitsJPEG2
			case 22:	//	DefineShape2
			case 32:	//	DefineShape3
			case 35:	//	DefineBitsJPEG3
			case 36:	//	DefineBitsLossless2
			case 46:	//	DefineMorphShape
			case 83:	//	DefineShape4
			case 84:	//	DefineMorphShape2
			case 90:	//	DefineBitsJPEG4
				if ($tagsize_long)	$character_id = unpack("v", substr($file, $offset_header + 6, 2));
				else				$character_id = unpack("v", substr($file, $offset_header + 2, 2));

				//	参照をref_tag_tableに記録
				$c->ref_tag_table[$character_id[1]] = &$c->tag[count($c->tag) - 1];
				break;
			case 8:
				$c->ref_jpeg_table = &$c->tag[count($c->tag) - 1];
				break;
			}
		}
		return $c;
	}

	/**
	 * 編集後のSWFファイルデータを取得する。
	 * @param	$compress	圧縮を行うかどうか。圧縮形式はFlashLite2.0以降の機能なので注意。
	 * @return				SWFバイナリデータ。
	*/
	function output($compress = FALSE)
	{
		//	登録変数がある場合、DoActionTagを新規生成
		if (!is_null($this->params) && 0 < count($this->params))
		{
			$action = SwfHack::createTagDoAction($this->params);
			$n = count($this->tag);
			for($i = 0; $i < $n; ++$i)
			{
				if (0 == strncmp($this->tag[$i], "\x43\x02", 2))
				{
					$this->tag = array_merge(array_slice($this->tag, 0, $i), array($action), array_slice($this->tag, $i));
					break;
				}
			}
		}

		//	実測したところ、implodeでの連結が最も速かった
		$body = implode('', $this->tag);
		$size = strlen($this->header) + strlen($body);

		if ($compress)
		{
			return
				'C' . substr($this->header, 1, 3) .
				pack("V", $size) .
				gzCompress(substr($this->header, 8) . $body, 9);
		}
		else
		{
			return
				substr($this->header, 0, 4) .
				pack("V", $size) .
				substr($this->header, 8) .
				$body
				;
		}
	}

	/**
	 * 登録変数を追加します
	 */
	public function registParams($params)
	{
		if (is_null($params) || !is_array($params))
		{
			return;
		}
		if (is_null($this->params))	$this->params = $params;
		else						array_merge($this->params, $params);
	}

	/**
	 * 画像の置換を行う
	 * filenameで指定する画像は、PNG8, PNG24, PNG32, GIF, JPEGに対応
	 * 
	 * @param	$character_id	置換するキャラクターID
	 * @param	$filename		置換する画像ファイル名
	 *
	 */
	public function replaceImage($character_id, $filename)
	{
		if (!isset($this->ref_tag_table[$character_id]))
		{
			return;
		}
		$tag = &$this->ref_tag_table[$character_id];
		$character_id = unpack("v", substr($tag, 6, 2));
		$tag = SwfHack::createImageTag($character_id[1], $filename);
	}

	/**
	 * SWFに含まれる画像を全て出力する
	 * JPEG以外の画像はフルカラーPNGとして出力される
	 * 出力される際のファイル名の番号が、SWF内でのCharacterIDである
	 * 
	 * この機能はCharacterIDを調べるために使うデバッグモードであり、これが常に正しく動くことを期待してはならない
	 * 特にJPEG周りは正しく出力される保証が無い
	 */
	public function extract()
	{
		foreach($this->ref_tag_table as $character_id => $tag)
		{
			$filename = sprintf("%05d.", $character_id);

			$offset = 0;
			$s = unpack("v", substr($tag, $offset, 2));
			$s = $s[1];
			$size_body = $s & 0x3F;
			$tagtype = ($s >> 6) & 0x03FF;
			$offset += 2;
			$tagsize_long = (0x3F === $size_body);
			if ($tagsize_long)
			{
				$s = unpack("V", substr($tag, $offset, 4));
				$size_body = $s[1];
				$offset += 4;
			}
			$tag = substr($tag, $offset);

			switch($tagtype)
			{
			case 6:		//	DefineBits
				//	無理やりJpegTablesと結合。一応正しく動いているが、常に正しく動くかはわからない
				file_put_contents($filename . 'jpg', substr($this->ref_jpeg_table, 6, -2) . substr($tag, 4));
				break;
			case 21:	//	DefineBitsJPEG2
				file_put_contents($filename . 'jpg', substr($tag, 2 + 4));	//	CharacterID(2bye) + SOI&EOI(4byte)
				break;
			case 35:	//	DefineBitsJPEG3
				$s = unpack("V", substr($tag, 0, 4));
				file_put_contents($filename . 'jpg', substr($tag, 2 + 4 + 4, $s[1]));	//	CharacterID(2bye) + size(4byte) + SOI&EOI(4byte)
				break;
			case 90:	//	DefineBitsJPEG4
				$s = unpack("V", substr($tag, 0, 4));
				file_put_contents($filename . 'jpg', substr($tag, 2 + 4 + 2 + 4, $s[1]));	//	CharacterID(2bye) + size(4byte) + smooze(2byte) + SOI&EOI(4byte)
				break;

			case 20:	//	DefineBitsLossless
			case 36:	//	DefineBitsLossless2
				$s = unpack("C", substr($tag, 2, 1));
				$format = $s[1];
				$s = unpack("v*", substr($tag, 3, 4));
				$width = $s[1];
				$height = $s[2];

				$img = imageCreateTrueColor($width, $height);

				//	ブレンドモードをFALSEにしておかないと、imageSetPixelした時にAlpha値が書きこめない
				imageAlphaBlending($img, FALSE);
				imageSaveAlpha($img, TRUE);

				if (3 === $format)
				{
					//	インデックスカラー
					$s = unpack("C", substr($tag, 7, 1));
					$color_num = $s[1] + 1;
					$data = gzUncompress(substr($tag, 8));

					if (36 === $tagtype)
					{
						//	アルファ有り
						$palete = array();
						$s = unpack("C" . ($color_num * 4), $data);
						for($i = 0; $i < $color_num; ++$i)
						{
							$r = $s[$i * 4 + 1];
							$g = $s[$i * 4 + 2];
							$b = $s[$i * 4 + 3];
							$a = $s[$i * 4 + 4];
							if (0 != $a)
							{
								$r = floor($r * 255 / $a);
								$g = floor($g * 255 / $a);
								$b = floor($b * 255 / $a);
							}
							$a = 127 - floor($a / 2);
							if (127 == $a)
							{
								//	SWFに格納されている完全透明なドットは、SWFの仕様上必ずRGB(0, 0, 0)になってしまう
								//	それだとツールで開いた際に透明色部分が黒になり見づらいので、一般的に透明色として使われるピンクに書き換え
								$r = 255;
								$g = 0;
								$b = 255;
							}
							$palete[] = array('alpha' => $a, 'red' => $r, 'green' => $g, 'blue' => $b);
						}
						$data = substr($data, $color_num * 4);

						$width_dummy = ($width + 3) & ~3;
						$s = unpack("C*", $data);
						for($y = 0; $y < $height; ++$y)
						{
							for($x = 0; $x < $width; ++$x)
							{
								$color = $palete[$s[$width_dummy * $y + $x + 1]];
								imageSetPixel($img, $x, $y, imageColorAllocateAlpha($img, $color['red'], $color['green'], $color['blue'], $color['alpha']));
							}
						}
					}
					else
					{
						//	アルファ無し
						$palete = array();
						$s = unpack("C" . ($color_num * 3), $data);
						for($i = 0; $i < $color_num; ++$i)
						{
							$r = $s[$i * 3 + 1];
							$g = $s[$i * 3 + 2];
							$b = $s[$i * 3 + 3];
							$palete[] = array('red' => $r, 'green' => $g, 'blue' => $b);
						}
						$data = substr($data, $color_num * 4);

						$width_dummy = ($width + 3) & ~3;
						$s = unpack("C*", $data);
						for($y = 0; $y < $height; ++$y)
						{
							for($x = 0; $x < $width; ++$x)
							{
								$color = $palete[$s[$width_dummy * $y + $x + 1]];
								imageSetPixel($img, $x, $y, imageColorAllocate($img, $color['red'], $color['green'], $color['blue']));
							}
						}
					}
				}
				else if (5 == $format)
				{
					//	フルカラー
					$data = gzUncompress(substr($tag, 7));
					$s = unpack("C*", $data);

					for($y = 0; $y < $height; ++$y)
					{
						for($x = 0; $x < $width; ++$x)
						{
							$a = $s[($width * $y + $x) * 4 + 1];
							$r = $s[($width * $y + $x) * 4 + 2];
							$g = $s[($width * $y + $x) * 4 + 3];
							$b = $s[($width * $y + $x) * 4 + 4];
							if (0 != $a)
							{
								$r = floor($r * 255 / $a);
								$g = floor($g * 255 / $a);
								$b = floor($b * 255 / $a);
							}
							$a = 127 - floor($a / 2);
							if (127 == $a)
							{
								//	SWFに格納されている完全透明なドットは、SWFの仕様上必ずRGB(0, 0, 0)になってしまう
								//	それだとツールで開いた際に透明色部分が黒になり見づらいので、一般的に透明色として使われるピンクに書き換え
								$r = 255;
								$g = 0;
								$b = 255;
							}
							imageSetPixel($img, $x, $y, imageColorAllocateAlpha($img, $r, $g, $b, $a));
						}
					}
				}
				imagePng($img, $filename . 'png');
				imageDestroy($img);
			}
		}
	}

	//---------------------------------------------------------------------------------
	//	ここから下は、内部で使う static private function
	//---------------------------------------------------------------------------------

	/**
	 * 変数登録タグ生成関数
	 */
	private static function createTagDoAction($params)
	{
		$tag = '';
		foreach ($params as $key => $value)
		{
			/*
			変数名、変数内容をPushし、SetVariableを呼び出す

			0x96	ActionPush（変数にPush）
			%s		Pushするものの長さ（文字列の場合：タイプ1byte + 文字列 + 文字列終末記号1byte）
			0x00	Pushするタイプが文字列であることを表現
			%s		文字列
			0x00	文字列終末記号
			0x96	|
			%s		|
			0x00	|同上
			%s		|
			0x00	|
			0x1d	ActionSetVariable
			*/
			$tag .= sprintf(
				"\x96%s\x00%s\x00\x96%s\x00%s\x00\x1d",
				pack("v", strlen($key) + 2), $key,
				pack("v", strlen($value) + 2), $value
			);
		}

		/*
		0x3f	タグサイズ拡張モード（サイズをint型で定義することを宣言）
		0x03	DoActionタグ
		%s		タグサイズ
		%s		タグ内容
		0x00	タグ終了
		*/
		return sprintf("\x3f\x03%s%s\x00", pack("V", strlen($tag) + 1), $tag);
	}

	/**
	 * 画像タグ生成関数
	 * 指定した画像のフォーマットを解釈し、適切なタグを返却する
	 * PNG8, PNG24, PNG32, GIF, JPEGに対応
	 */
	private static function createImageTag($character_id, $filename)
	{
		$image_size = getImageSize($filename);

		switch($image_size[2])
		{
		case IMAGETYPE_PNG:
			$resource_id = imageCreateFromPng($filename);
			$tag = SwfHack::createTagDefineBitsLossless($character_id, $resource_id, $image_size[0], $image_size[1]);
			imageDestroy($resource_id);
			return $tag;
		case IMAGETYPE_GIF:
			$resource_id = imageCreateFromGif($filename);
			$tag = SwfHack::createTagDefineBitsLossless($character_id, $resource_id, $image_size[0], $image_size[1]);
			imageDestroy($resource_id);
			return $tag;
		case IMAGETYPE_JPEG:
			return SwfHack::createTagDefineBitsJPEG($character_id, $filename);
		}
		return NULL;
	}

	/**
	 * Jpeg画像タグ生成関数
	 */
	private static function createTagDefineBitsJPEG($character_id, $filename)
	{
		$image = file_get_contents($filename, FILE_BINARY);
		return sprintf(
			"\x7f\x05%s%s\xff\xd9\xff\xd8%s",
			pack("V", strlen($image) + 6),
			pack("v", $character_id),	//	CharacterID
			$image
		);
	}

	/**
	 * Lossless画像タグ生成関数
	 */
	private static function createTagDefineBitsLossless($character_id, $resource_id, $width, $height)
	{
		$data = '';
		if (0 === ($color_num = imageColorsTotal($resource_id)))
		{
			//	フルカラー
			$format = 5;

			//	透過色あり
			$tagheader = "\x3f\x09";

			for($y = 0; $y < $height; ++$y)
			{
				for($x = 0; $x < $width; ++$x)
				{
					$color = imageColorAt($resource_id, $x, $y);
					$alpha = 255 - floor((($color >> 24) & 0xFF) * 255 / 127);
					$r = floor((($color >> 16) & 0xFF) * $alpha / 255);
					$g = floor((($color >>  8) & 0xFF) * $alpha / 255);
					$b = floor((($color >>  0) & 0xFF) * $alpha / 255);

					$data .= pack("CCCC", $alpha, $r, $g, $b);
				}
			}
			$data = gzCompress($data, 9);
			return
				  $tagheader
				. pack("VvCvv", 7 + strlen($data), $character_id, $format, $width, $height)
				. $data
				;
		}
		else
		{
			//	インデックスカラー
			$format = 3;
			if (-1 === imageColorTransparent($resource_id))
			{
				//	透過色が存在しない
				$tagheader = "\x3f\x05";

				for($i = 0; $i < $color_num; ++$i)
				{
					$color = imageColorsForIndex($resource_id, $i);
					$data .= pack("C", floor($color['red']))
							. pack("C", floor($color['green']))
							. pack("C", floor($color['blue']));
				}
			}
			else
			{
				//	透過色あり
				$tagheader = "\x3f\x09";

				for($i = 0; $i < $color_num; ++$i)
				{
					$color = imageColorsForIndex($resource_id, $i);

					//	不透明が0、完全透明が127という仕様のため、
					//	これを一般的な
					//	不透明が255、完全透明が0に変換
					$alpha = 255 - floor($color['alpha'] * 255 / 127);
					$data .= pack("CCCC",
								floor($color['red'] * $alpha / 255),
								floor($color['green'] * $alpha / 255),
								floor($color['blue'] * $alpha / 255),
								$alpha);
				}
			}

			$width_over = (($width + 3) & ~3) - $width;
			for($y = 0; $y < $height; ++$y)
			{
				for($x = 0; $x < $width; ++$x)
				{
					$data .= pack("C", imageColorAt($resource_id, $x, $y));
				}
				for($x = 0; $x < $width_over; ++$x)
				{
					$data .= "\0";
				}
			}
			$data = gzCompress($data, 9);
			return
				  $tagheader
				. pack("VvCvvC", 8 + strlen($data), $character_id, $format, $width, $height, $color_num - 1)
				. $data
				;
		}

	}

}
?>