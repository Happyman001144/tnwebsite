<?php
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Setting;
use App\Helpers;

$module_version = '2.0.9';
$module_name = 'forums';

class forumsModule {
    static function updateModuleTables($ver) {
      if ($ver < 10008) {
        $sql="
        ALTER TABLE `forum_boards`
    	    CHANGE COLUMN `name` `name` VARCHAR(50) NULL DEFAULT NULL AFTER `cid`;
        ALTER TABLE `forum_categories`
     	    CHANGE COLUMN `name` `name` VARCHAR(50) NULL DEFAULT NULL AFTER `cid`;
        ";
        $newVer = '1.0.8';
      } else if ($ver < 20009) {
        $sql="
        DELETE FROM `forum_threads` WHERE `tid` NOT IN (SELECT `tid` FROM `forum_posts`);
        ";
        $newVer = '2.0.9';
      }

      if (!empty($newVer)) {
        $sql .= 'UPDATE settings SET value="'.$newVer.'" WHERE setting="forums_version" AND category="updater";';
        DB::unprepared(DB::raw($sql));
        self::updateModuleTables(Helpers::verStrToInt($newVer));
      }
    }

    static function createModuleTables($module_version) {
      $sql = "
      /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
      /*!40101 SET NAMES utf8 */;
      /*!50503 SET NAMES utf8mb4 */;
      /*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
      /*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

      CREATE TABLE IF NOT EXISTS `forum_boards` (
        `bid` int(11) NOT NULL AUTO_INCREMENT,
        `cid` int(11) NOT NULL,
    	  `name` VARCHAR(50) DEFAULT NULL,
        `icon` varchar(25) DEFAULT NULL,
        `order` tinyint(4) DEFAULT NULL,
        PRIMARY KEY (`bid`),
        KEY `FK_forum_boards_forum_categories` (`cid`),
        CONSTRAINT `FK_forum_boards_forum_categories` FOREIGN KEY (`cid`) REFERENCES `forum_categories` (`cid`) ON UPDATE CASCADE
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_board_permissions` (
        `pid` int(11) NOT NULL AUTO_INCREMENT,
        `bid` int(11) NOT NULL,
        `gid` int(11) NOT NULL,
        `cannot_view` tinyint(4) DEFAULT NULL,
        `cannot_post_thread` tinyint(4) DEFAULT NULL,
        `cannot_post_reply` tinyint(4) DEFAULT NULL,
        `cannot_react` int(11) DEFAULT NULL,
        PRIMARY KEY (`pid`),
        UNIQUE KEY `bid_gid` (`bid`,`gid`),
        CONSTRAINT `FK_forum_board_permissions_forum_boards` FOREIGN KEY (`bid`) REFERENCES `forum_boards` (`bid`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_categories` (
        `cid` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) DEFAULT NULL,
        PRIMARY KEY (`cid`)
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_notifications` (
        `nid` int(11) NOT NULL AUTO_INCREMENT,
        `steamid` bigint(20) DEFAULT NULL,
        `type` varchar(50) DEFAULT NULL,
        `pid` int(11) DEFAULT NULL,
        `read` tinyint(4) DEFAULT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`nid`),
        KEY `FK_forum_notifications_forum_posts` (`pid`),
        KEY `FK_forum_notifications_users` (`steamid`),
        CONSTRAINT `FK_forum_notifications_forum_posts` FOREIGN KEY (`pid`) REFERENCES `forum_posts` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_forum_notifications_users` FOREIGN KEY (`steamid`) REFERENCES `users` (`steamid`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_posts` (
        `pid` int(11) NOT NULL AUTO_INCREMENT,
        `tid` int(11) NOT NULL,
        `reply_to_pid` int(11) DEFAULT NULL,
        `steamid` bigint(20) NOT NULL,
        `content` mediumtext NOT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_edit` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`pid`),
        KEY `FK_forum_posts_forum_threads` (`tid`),
        KEY `FK_forum_posts_users` (`steamid`),
        KEY `FK_forum_posts_forum_posts` (`reply_to_pid`),
        CONSTRAINT `FK_forum_posts_forum_posts` FOREIGN KEY (`reply_to_pid`) REFERENCES `forum_posts` (`pid`),
        CONSTRAINT `FK_forum_posts_forum_threads` FOREIGN KEY (`tid`) REFERENCES `forum_threads` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_forum_posts_users` FOREIGN KEY (`steamid`) REFERENCES `users` (`steamid`)
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_reactions` (
        `rid` int(11) NOT NULL AUTO_INCREMENT,
        `pid` int(11) NOT NULL,
        `steamid` bigint(20) NOT NULL,
        `rname` varchar(50) NOT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`rid`),
        KEY `FK_forum_reactions_forum_posts` (`pid`),
        KEY `FK_forum_reactions_users` (`steamid`),
        CONSTRAINT `FK_forum_reactions_forum_posts` FOREIGN KEY (`pid`) REFERENCES `forum_posts` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_forum_reactions_users` FOREIGN KEY (`steamid`) REFERENCES `users` (`steamid`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_threads` (
        `tid` int(11) NOT NULL AUTO_INCREMENT,
        `bid` int(11) NOT NULL,
        `steamid` bigint(20) NOT NULL,
        `topic` varchar(50) NOT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_post_steamid` bigint(20) DEFAULT NULL,
        `locked` tinyint(1) DEFAULT NULL,
        `pinned` tinyint(1) DEFAULT NULL,
        PRIMARY KEY (`tid`),
        KEY `FK_forum_threads_forum_boards` (`bid`),
        KEY `FK_forum_threads_users` (`steamid`),
        KEY `FK_forum_threads_users_2` (`last_post_steamid`),
        CONSTRAINT `FK_forum_threads_forum_boards` FOREIGN KEY (`bid`) REFERENCES `forum_boards` (`bid`) ON UPDATE CASCADE,
        CONSTRAINT `FK_forum_threads_users` FOREIGN KEY (`steamid`) REFERENCES `users` (`steamid`),
        CONSTRAINT `FK_forum_threads_users_2` FOREIGN KEY (`last_post_steamid`) REFERENCES `users` (`steamid`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

      CREATE TABLE IF NOT EXISTS `forum_threads_read` (
        `tid` int(11) NOT NULL,
        `steamid` bigint(20) NOT NULL,
        `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `steamid, tid` (`steamid`,`tid`),
        KEY `FK_forum_threads_read_forum_threads` (`tid`),
        CONSTRAINT `FK_forum_threads_read_forum_threads` FOREIGN KEY (`tid`) REFERENCES `forum_threads` (`tid`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_forum_threads_read_users` FOREIGN KEY (`steamid`) REFERENCES `users` (`steamid`) ON DELETE CASCADE ON UPDATE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

      /*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
      /*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
      /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
      ";

      $sql .= 'INSERT INTO settings SET value="'.$module_version.'", setting="forums_version", category="updater";';
      DB::unprepared(DB::raw($sql)); // 251968174
    }
}
