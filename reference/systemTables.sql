CREATE TABLE `anticUser` (
  `userID` int(10) NOT NULL AUTO_INCREMENT,
  `email` varchar(512) DEFAULT NULL,
  `password` varchar(512) DEFAULT '',
  `firstName` varchar(40) DEFAULT '',
  `lastName` varchar(40) DEFAULT '',
  `lastLogin` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`userID`),
  KEY `ixEmailUserID` (`email`(255),`userID`,`password`(255))
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;


CREATE TABLE `anticVersionLog` (
  `id` bigint(20) NOT NULL,
  `tableName` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `pkArrayBaseJson` varchar(2048) CHARACTER SET latin1 DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `userID` int(11) DEFAULT NULL,
  `data` longblob,
  PRIMARY KEY (`id`),
  KEY `ixTablePK` (`tableName`,`pkArrayBaseJson`(767),`timestamp`),
  KEY `ixUserID` (`userID`),
  CONSTRAINT `userID` FOREIGN KEY (`userID`) REFERENCES `anticUser` (`userID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




CREATE TABLE `anticSystemLogEventType` (
  `eventTypeID` int(11) NOT NULL AUTO_INCREMENT,
  `eventDescription` varchar(255) NOT NULL,
  PRIMARY KEY (`eventTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




CREATE TABLE `anticSystemLog` (
  `eventID` bigint(20) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `eventTypeID` int(11) DEFAULT NULL,
  `eventDesc` mediumtext,
  `userID` int(10) DEFAULT NULL,
  `sourceIP` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`eventID`),
  KEY `eventType` (`eventTypeID`),
  KEY `timestamp` (`timestamp`),
  KEY `fk_anticSystemLog_1_idx` (`userID`),
  CONSTRAINT `fk_anticSystemLog_1` FOREIGN KEY (`userID`) REFERENCES `anticUser` (`userID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_anticSystemLog_2` FOREIGN KEY (`eventTypeID`) REFERENCES `anticSystemLogEventType` (`eventTypeID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `anticGroup` (
  `groupID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `groupName` varchar(80) NOT NULL,
  PRIMARY KEY (`groupID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `anticUserGroup` (
  `userID` int(10) NOT NULL DEFAULT '0',
  `groupID` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID`,`groupID`),
  KEY `ixGroup` (`groupID`,`userID`),
  CONSTRAINT `fkUserID` FOREIGN KEY (`userID`) REFERENCES `anticUser` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `anticSystemSettingType` (
  `systemSettingTypeID` INT NOT NULL,
  `systemSettingName` VARCHAR(65) NULL,
  `systemSettingDescription` VARCHAR(1024) NULL,
  PRIMARY KEY (`systemSettingTypeID`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


CREATE TABLE `anticSystemSetting` (
  `systemSettingID` INT NOT NULL AUTO_INCREMENT,
  `systemSettingTypeID` INT NULL,
  `systemSettingValue` VARCHAR(1024) NULL,
  PRIMARY KEY (`systemSettingID`),
  INDEX `index2` (`systemSettingTypeID` ASC),
  CONSTRAINT `fkSystemSettingTypes`
    FOREIGN KEY (`systemSettingTypeID`)
    REFERENCES `anticSystemSettingTypes` (`systemSettingTypeID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;



CREATE TABLE `anticPermission` (
  `id` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `groupID` int(11) DEFAULT NULL,
  `tableName` varchar(45) DEFAULT NULL,
  `pkArrayBaseJson` varchar(2048) DEFAULT NULL,
  `permissionObject` blob,
  PRIMARY KEY (`id`),
  KEY `ixTablePK` (`tableName`,`pkArrayBaseJson`(767)),
  KEY `fk_anticPermission_1_idx` (`userID`),
  CONSTRAINT `fk_anticPermission_1` FOREIGN KEY (`userID`) REFERENCES `anticUser` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

