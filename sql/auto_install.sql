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

DROP TABLE IF EXISTS `civicrm_aip_processor`;

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
-- * Configuration profiles implemented by config type providers.
-- *
-- *******************************************************/
CREATE TABLE `civicrm_aip_processor` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Process ID.',
  `config` text COMMENT 'configuration of the process',
  `state`  text COMMENT 'current state of the process',
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;
