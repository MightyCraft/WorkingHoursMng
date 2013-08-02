-- 
-- データベース `working_hours_mng`
-- 

CREATE DATABASE `working_hours_mng`;

-- 
-- データベース選択 `working_hours_mng`
--

USE `working_hours_mng`;

-- 
-- テーブルの構造 `mst_client`
-- 

CREATE TABLE `mst_client` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `memo` text,
  `delete_flg` tinyint(1) NOT NULL default '0',
  `regist_date` datetime NOT NULL,
  `update_date` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- テーブルの構造 `mst_holiday`
-- 

CREATE TABLE `mst_holiday` (
  `holiday_year` smallint(6) NOT NULL default '0',
  `holiday_month` tinyint(4) NOT NULL default '0',
  `holiday_day` tinyint(4) NOT NULL default '0',
  `regist_date` datetime NOT NULL,
  PRIMARY KEY  (`holiday_year`,`holiday_month`,`holiday_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='休日管理マスタ';

-- --------------------------------------------------------

-- 
-- テーブルの構造 `mst_member`
-- 

CREATE TABLE `mst_member` (
  `id` int(11) NOT NULL auto_increment,
  `member_code` varchar(16) NOT NULL,
  `name` varchar(32) NOT NULL,
  `auth_lv` tinyint(4) NOT NULL default '0',
  `post` tinyint(4) NOT NULL default '0',
  `position` tinyint(4) NOT NULL DEFAULT '1',
  `password` varchar(64) NOT NULL,
  `delete_flg` tinyint(1) NOT NULL default '0',
  `regist_date` datetime NOT NULL,
  `update_date` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `member_code` (`member_code`),
  KEY `name` (`name`),
  KEY `auth_lv` (`auth_lv`),
  KEY `post` (`post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- テーブルの構造 `mst_project`
-- 

CREATE TABLE `mst_project` (
  `id` int(11) NOT NULL auto_increment,
  `project_code` varchar(16) NOT NULL,
  `name` varchar(256) NOT NULL,
  `client_id` int(11) NOT NULL,
  `member_id` int(11) default NULL,
  `project_type` tinyint(2) NOT NULL default '0',
  `max_manhour` smallint(6) default NULL,
  `total_budget_manhour` int(11) default NULL,
  `total_budget` int(11) default NULL,
  `project_start_date` date default NULL,
  `project_end_date` date default NULL,
  `start_date` date default NULL,
  `end_date` date default NULL,
  `nouki` varchar(20) default NULL,
  `memo_flg` tinyint(1) NOT NULL,
  `memo` text,
  `delete_flg` tinyint(1) NOT NULL default '0',
  `regist_date` datetime NOT NULL,
  `update_date` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `name` (`name`(255)),
  KEY `client` (`client_id`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `mst_post`
--

CREATE TABLE IF NOT EXISTS `mst_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(64) NOT NULL,
  `delete_flg` tinyint(1) NOT NULL,
  `regist_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='部署マスタ';

-- --------------------------------------------------------

--
-- テーブルの構造 `mst_position`
--

CREATE TABLE IF NOT EXISTS `mst_position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `delete_flg` tinyint(1) NOT NULL,
  `regist_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='役職マスタ';

--
-- テーブルのデータのダンプ `mst_position`
-- 
INSERT INTO `mst_position` (`id`, `name`, `regist_date`, `update_date`) VALUES
(1, '一般', '2013-06-04 00:00:00', '2013-06-04 00:00:00');

-- --------------------------------------------------------

-- 
-- テーブルの構造 `trn_manhour`
-- 

CREATE TABLE `trn_manhour` (
  `project_id` int(11) NOT NULL default '0',
  `member_id` int(11) NOT NULL default '0',
  `man_hour` double NOT NULL,
  `memo` varchar(128) default NULL,
  `end_project_id` int(11) NOT NULL default '0',
  `work_year` smallint(6) NOT NULL default '0',
  `work_month` tinyint(4) NOT NULL default '0',
  `work_day` tinyint(4) NOT NULL default '0',
  `regist_date` datetime NOT NULL,
  `update_date` datetime default NULL,
  PRIMARY KEY  (`member_id`,`project_id`,`work_year`,`work_month`,`work_day`,`end_project_id`),
  KEY `end_project_id` (`end_project_id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- テーブルの構造 `trn_project_team`
-- 

CREATE TABLE `trn_project_team` (
  `member_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `regist_date` datetime NOT NULL,
  KEY `member_id` (`member_id`),
  KEY `project_id` (`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='所属プロジェクトデータ';


--
-- テーブルのデータのダンプ `mst_member`
--

INSERT INTO `mst_member` (`id`, `member_code`, `name`, `auth_lv`, `post`, `position`, `password`, `delete_flg`, `regist_date`, `update_date`) VALUES
(1, '0', 'root', 5, 1, 1, '4813494d137e1631bba301d5acab6e7bb7aa74ce1185d456565ef51d737677b2', 0, '2013-06-01 00:00:00', '2013-06-01 00:00:00');