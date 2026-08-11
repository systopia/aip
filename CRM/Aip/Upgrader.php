<?php
/*-------------------------------------------------------+
| SYSTOPIA Automatic Input Processing (AIP) Framework    |
| Copyright (C) 2023 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

declare(strict_types = 1);

// phpcs:disable
use CRM_Aip_ExtensionUtil as E;
// phpcs:enable

/**
 * Collection of upgrade steps.
 */
class CRM_Aip_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Installer
   */
  public function install(): void {
    $this->executeSqlFile('sql/auto_install.sql');
  }

  /**
   * Uninstaller
   */
  public function uninstall(): void {
    $this->executeSqlFile('sql/auto_uninstall.sql');
  }

  /**
   * Drop schema
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  public function upgrade_0001(): bool {
    $this->ctx->log->info('Updating DB schema');
    // in this case, we actually CAN just run auto_install.sql again,
    //  because it just does CREATE IF NOT EXISTS on the tables.
    $this->executeSqlFile('sql/auto_install.sql');
    return TRUE;
  }

  /**
   * Update to version 1.1
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0002() : bool {
    $this->ctx->log->info('Updating "AIP" schema to version 1.1...');

    // add column: category
    $column_exists = CRM_Core_DAO::singleValueQuery("SHOW COLUMNS FROM `civicrm_aip_error_log` LIKE 'is_resolved';");
    if ($column_exists === NULL || $column_exists === '') {
      CRM_Core_DAO::executeQuery(
        "ALTER TABLE `civicrm_aip_error_log` ADD COLUMN `is_resolved` BOOL COMMENT 'has this error been resolved?';"
      );
    }

    // update rebuild log tables
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();

    return TRUE;
  }

  /**
   * Upgrade to version 1.3
   * @return TRUE on success
   */
  public function upgrade_0003() : bool {
    CRM_Core_Session::setStatus(
      'AIP does map parameters before filtering parameter now. Please check if your AIP Configuration needs changes'
    );
    return TRUE;
  }

}
