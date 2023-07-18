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

require_once 'aip.civix.php';

use CRM_AIP_ExtensionUtil as E;

/**
 * AIProcessor.run implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_a_i_p_run_process($params)
{
  // verify pid parameter
  if (empty($params['pid']) || !is_int($params['pid'])) {
    throw new CiviCRM_API3_Exception("Invalid pid.");
  }

  $pid = (int) $params['pid'];
  $process = \Civi\AIP\Process::restore($pid);
  $process->run();

  // create reply
  return civicrm_api3_create_success([
          'total_processed' => $process->getReader()->getProcessedRecordCount(),
          '$session_processed' => $process->getReader()->getSessionProcessedRecordCount(),
  ]);
}
