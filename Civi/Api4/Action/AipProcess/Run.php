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

namespace Civi\Api4\Action\AipProcess;

/**
 * AipProcess.run action
 */

use \Civi\Api4\Generic\AbstractAction;
use \Civi\Api4\Generic\Result;
use CiviCRM_API3_Exception;
use CRM_Core_Exception;

/**
 * Generate a security checksum for anonymous access to CiviCRM.
 *
 * @method $this setProcessId(int $process_id) Set process ID (required)
 * @method int getProcessId() Get process ID param
 */
class Run extends AbstractAction {

  /**
   * Process ID
   *
   * @var int
   *
   * @required
   */
  protected int $process_id;

  /**
   * Runs the AIP process with the given ID
   *
   * @param Result $result
   *
   * @throws CRM_Core_Exception
   */
  public function _run(Result $result) {
    try {
      // todo refactor RUN implementation
      $api3_result = civicrm_api3('AIP', 'run_process', ['pid' => $this->process_id]);
    } catch (CiviCRM_API3_Exception $exception) {
      throw new CRM_Core_Exception($exception->getMessage());
    }
    $result[] = $api3_result;
  }
}
