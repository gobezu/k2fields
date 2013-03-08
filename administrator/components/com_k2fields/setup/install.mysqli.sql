CREATE TABLE IF NOT EXISTS `#__k2_extra_fields_list_values` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `list` SMALLINT(10) UNSIGNED NOT NULL,
        `lft` INT(11) NOT NULL DEFAULT 0,
        `rgt` INT(11) NOT NULL DEFAULT 0,
        `depth` INT(10) UNSIGNED NOT NULL DEFAULT 0,
        `path` VARCHAR(255) NOT NULL DEFAULT '',
        `fullpath` VARCHAR(255) NOT NULL DEFAULT '',
        `val` VARCHAR(255) NOT NULL,
        `img` VARCHAR(255) NOT NULL DEFAULT '',
        `published` TINYINT(1) NOT NULL DEFAULT 0,
        `language` CHAR(7) NOT NULL DEFAULT '*',
        `access` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
        `fieldtype` VARCHAR(30) NULL DEFAULT NULL,
        `lat` FLOAT(10,6) NULL DEFAULT NULL,
        `lng` FLOAT(10,6) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_left_right` (`lft`, `rgt`),
        INDEX `idx_list_list` (`list`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8; 

CREATE TABLE IF NOT EXISTS `#__k2_extra_fields_values` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `itemid` INT(11) NOT NULL,
        `fieldid` INT(11) NOT NULL,
        `listindex` TINYINT(4) NOT NULL DEFAULT 0,
        `partindex` TINYINT(4) NOT NULL DEFAULT 0,
        `index` TINYINT(4) NOT NULL DEFAULT 0,
        `value` TEXT NOT NULL,
        `lat` FLOAT(10,6) NULL DEFAULT NULL,
        `lng` FLOAT(10,6) NULL DEFAULT NULL,
        `txt` VARCHAR(255) NULL DEFAULT NULL,
        `img` VARCHAR(255) NULL DEFAULT NULL,
        `datum` DATETIME NULL DEFAULT NULL,
	`related` INT(11) NULL DEFAULT NULL,
	`duration` INT(11) NULL DEFAULT NULL,
        `isadjusted` VARCHAR(255) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_values_itemid` (`itemid`),
        INDEX `idx_values_fieldid` (`fieldid`),
        FULLTEXT INDEX `idx_values_ft` (`value`, `txt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;                               

CREATE TABLE IF NOT EXISTS `#__k2_extra_fields_definition` (
        `id` SMALLINT(6) NOT NULL AUTO_INCREMENT,
        `definition` TEXT NOT NULL,
        PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;