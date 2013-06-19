<?php
/**
 * ファイル圧縮/解凍クラス
 *
 * PEAR::File_Archiveを使用しています。
 * インストールしていない環境の場合はインストールして下さい。
 * http://pear.php.net/package/File_Archive
 * http://pear.php.net/manual/ja/package.fileformats.file-archive.php
 *
 * 圧縮など時間がかかる場合は「set_time_limit(0)」などでタイムアウトしないようにして下さい。
 *
 * File_Archiveは読み込みや書き込みの際に、ファイル名の拡張子から勝手にアーカイバを識別して、
 * それぞれに対応した方法で圧縮解凍を行ってくれます。
 */
require_once 'File/Archive.php';
class FileArchive
{
	/**
	 * データから直接ダウンロード（※未圧縮）
	 *
	 * 指定するファイル名は関数内でS-JIS変換しています。
	 *
	 * @param string	$download_data		ダウンロードする文字列
	 * @param stirng	$download_filename	ダウンロードファイル名（拡張子込み）
	 * @return	boolean	true：DLあり、false：DLなし
	 */
	static function downloadDataToFile($download_data, $download_filename)
	{
		// DLファイル名が未指定の時は処理をしない
		if (empty($download_filename))
		{
			return false;
		}

		File_Archive::setOption("zipCompressionLevel", 9);		// 圧縮率の設定
		$fa = new File_Archive();
		$fa->extract($fa->readMemory($download_data, convertUtf8ToSjiswin($download_filename)), $fa->toOutput());
		return true;
	}

	/**
	 * データから直接圧縮ダウンロード
	 *
	 * 指定するファイル名は関数内でS-JIS変換しています。
	 * データを配列で指定する事で複数のファイルに書き出す事が可能です。
	 *
	 * @param array		$download_datas		ダウンロードする文字列
	 * @param string	$download_filenames	ダウンロードファイル名
	 * @param array		$filename_paths		生成する圧縮ファイル内のディレクトリ構成やファイル名を指定します。
	 * @return	boolean	true：DLあり、false：DLなし
	 */
	static function downloadDatasToArchive($download_datas, $download_filename, $filename_paths)
	{
		// DLファイル名が未指定の時は処理をしない
		if (empty($download_filename) || empty($filename_paths))
		{
			return false;
		}

		File_Archive::setOption("zipCompressionLevel", 9);		// 圧縮率の設定
		$fa = new File_Archive();
		$src = $fa->readMulti();

		$empty_flg = true;
		foreach ($download_datas as $key => $download_data)
		{
			if (!empty($filename_paths[$key]))
			{
				// ファイル名が指定してある時だけ書き出しをする
				$src->addSource($fa->readMemory($download_data, convertUtf8ToSjiswin($filename_paths[$key])));
				// １ファイルでも書き出せたらフラグをOFF
				$empty_flg = false;
			}
		}

		// 圧縮するファイルが1件も存在しない場合に解凍できない圧縮ファイルが生成されるのを回避
		if (!$empty_flg)
		{
			$fa->extract($src, $fa->toArchive(convertUtf8ToSjiswin($download_filename), $fa->toOutput()));
			return true;
		}

		return false;
	}

	/**
	 * 生成済みのファイルをダウンロード（※未圧縮）
	 *
	 * 指定する各ファイル名は関数内でS-JIS変換しています。
	 *
	 * @param string	$download_file		生成済みのファイル
	 * @param string	$download_filename	ダウンロードファイル名（任意）
	 * @return	boolean	true：DLあり、false：DLなし
	 */
	static function downloadFileToFile($download_file, $download_filename=null)
	{
		// DLファイル名、DLファイルが未指定の時は処理をしない
		if (empty($download_filename) || empty($download_file))
		{
			return false;
		}

		// ファイルの存在チェック AND DLファイルの設定
		$conv_download_file = convertUtf8ToSjiswin($download_file);
		if (file_exists($conv_download_file) && is_file($conv_download_file))
		{
			// FileArchiveクラス
			File_Archive::setOption("zipCompressionLevel", 9);		// 圧縮率の設定
			$fa = new File_Archive();

			if (!empty($download_filename))
			{
				// DLファイル名の指定あり
				$src = $fa->readMulti();
				$src->addSource(File_Archive::read($conv_download_file,convertUtf8ToSjiswin($download_filename)));
				$fa->extract($src, $fa->toOutput());
			}
			else
			{
				$fa->extract($conv_download_file, $fa->toOutput());
			}

			return true;
		}

		return false;
	}

	/**
	 * 生成済みのファイルを圧縮してダウンロード
	 *
	 * 指定する各ファイル名は関数内でS-JIS変換しています。
	 *
	 * @param array	$download_files		生成済みのDLするファイルを配列で指定します。複数ファイル指定可です。
	 * @param array	$download_filename	DLする圧縮ファイル名を指定します。拡張子込みです。
	 * @param array	$rename_paths		生成する圧縮ファイル内のディレクトリ構成やファイル名のリネームを指定します。（任意）
	 * @return	boolean	true：DLあり、false：DLなし
	 *
	 * 	使用例）$download_filename = 'archive.zip';
	 * 			$download_files = array('test1.txt','test2.txt','test3.txt');
	 * 			$rename_paths = array('aaa/test_a.txt','test_b.txt');
	 * 			⇒	下記のようなディレクトリ構成の圧縮ファイルがダウンロードできます
	 * 				archive.zip
	 * 					test3.txt
	 * 					test_b.txt（中身はtext2.txt）
	 * 					aaa/test_a.txt（中身はtext1.txt）
	 *
	 * 			TODO: ブラッシュアップ
	 * 			$download_files = array(
	 * 								'test3.txt',								// 直下にそのままのファイル名で配置
	 * 								array('test2.txt','test_b.txt'),			// ファイル名のリネームだけする
	 * 								'aaa' => array('test1.txt','test_a.txt'),	// ディレクトリ階層の指定をKEYで行う
	 * 							);
	 */
	static function downloadFilesToArchive($download_files, $download_filename, $rename_paths=array())
	{
		// DLファイル名、DLファイルが未指定の時は処理をしない
		if (empty($download_filename) || empty($download_files) || !is_array($download_files))
		{
			return false;
		}

		// DLの設定
		File_Archive::setOption("zipCompressionLevel", 9);		// 圧縮率の設定
		$fa = new File_Archive();
		$src = $fa->readMulti();
		$empty_flg = true;
		foreach ($download_files as $key => $tmp_download_file)
		{
			// ファイルの存在チェック AND DLファイルの設定
			$download_file = convertUtf8ToSjiswin($tmp_download_file);
			if (file_exists($download_file) && is_file($download_file))
			{
				$empty_flg = false;	// １ファイルでも存在したらフラグをOFF

				if (!empty($rename_paths[$key]))
				{
					// 圧縮ファイル内のディレクトリ階層やファイル名が指定されていたら使用する
					$src->addSource(File_Archive::read($download_file,convertUtf8ToSjiswin($rename_paths[$key])));
				}
				else
				{
					$src->addSource(File_Archive::read($download_file));
				}
			}
		}

		// 圧縮するファイルが1件も存在しない場合に解凍できない圧縮ファイルが生成されるのを回避
		if (!$empty_flg)
		{
			$fa->extract($src, $fa->toArchive(convertUtf8ToSjiswin($download_filename), $fa->toOutput()));
			return true;
		}

		return false;
	}

}