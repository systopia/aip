-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Based on schema.tpl
--
-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_aip_process`;

SET FOREIGN_KEY_CHECKS=1;


-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_aip_processor
-- *
-- *******************************************************/
CREATE TABLE IF NOT EXISTS `civicrm_aip_process` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique Process ID.',
  `name`                VARCHAR(96) COMMENT 'name of the process',
  `is_active`           BOOL        COMMENT 'is this process active',
  `last_run`            DATETIME    COMMENT 'when was this process last run',
  `class`               VARCHAR(96) COMMENT 'process implementation class, most likely \\Civi\\AIP\\Process',
  `config`              TEXT        COMMENT 'configuration/state of the process',
  `documentation`       TEXT        COMMENT 'should explain what the process does',
  `state`               TEXT        COMMENT 'current state of the process',
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;

-- /*******************************************************
-- *
-- * civicrm_aip_error_log
-- *
-- *******************************************************/
CREATE TABLE IF NOT EXISTS `civicrm_aip_error_log` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Error log ID.',
  `process_id`          INT UNSIGNED NOT NULL                COMMENT 'Process ID that produced this error',
  `error_timestamp`     DATETIME                             COMMENT 'When did the error occur.',
  `error_message`       TEXT                                 COMMENT 'Error message',
  `data`                TEXT                                 COMMENT 'json-encoded data (e.g. a record)',
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_civicrm_aip_error_log_process_id` FOREIGN KEY (`process_id`) REFERENCES `civicrm_aip_process` (`id`) ON DELETE CASCADE,
  KEY `process_id` (`process_id`)
) ENGINE=InnoDB;
