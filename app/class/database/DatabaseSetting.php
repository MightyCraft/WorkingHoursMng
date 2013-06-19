<?php

/**
 * このクラスは、データベースアクセス用のクラスを作るクラス作成者以外が使うことを禁止する。
 * 実際に作業員にデータベースアクセスをさせる場合は、このクラスを使わせてはいけません。
 *
 * 作業員にデータベースアクセスさせる場合は、このクラスを利用して製作された、データベースアクセス用クラスを作ります。
 * 例としてユーザーデータを取得/書き込みするクラスである、DB_UserInfoクラスを同梱しておきます。
 *
 * これはサンプルです。
 * レプリケーションを行い、マスター/スレイブ構成を行っている場合のサンプルとなります。
 *
 * このサンプルのように、サーバーの接続設定を、staticなクラスに閉じ込めることを推奨しています。
 *
 */
class DatabaseSetting
{
	static $master;
	static $slaves;

	static function setMaster($master)
	{
		self::$master = $master;
	}

	static function setSlaves($slaves)
	{
		self::$slaves = $slaves;
	}

	/**
	 *
	 * @return DatabaseAccessor 返りの型を指定しておくと、Eclipseの補完が効くようになって便利なので、絶対に書くこと
	 */
	static function getAccessor()
	{
		static $db = null;
		if (is_null($db))
		{
			$master = self::$master;
			$slave = self::$slaves[array_rand(self::$slaves)];

			$db = new BasicDatabaseAccessor(
				new ReplicationDatabaseConnector(
					new BasicDatabaseConnector($master),
					new BasicDatabaseConnector($slave)
				)
			);
		}
		return $db;
	}
}