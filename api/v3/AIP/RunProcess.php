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
use Civi\AIP\Process;

/**
 * AIProcessor.run implementation
 *
 * @todo refactor
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_a_i_p_run_process($params)
{
  // stats
  $total_processed = 0;
  $session_processed = 0;

  // verify pid parameter
  if (empty($params['pid'])) {
    throw new CiviCRM_API3_Exception("Missing pid.");
  }

  // extract pIDs
  $pIDs = [];
  foreach (explode(',', $params['pid']) as $pid_string) {
    $pid = (int) $pid_string;
    if ($pid) {
      $pIDs[] = $pid;
    } else {
      Civi::log()->warning("AIP.run_process: PID '{$pid_string}' invalid. Skipped");
    }
  }

  // execute
  foreach ($pIDs as $pid) {
    $process = Process::restore($pid);
    $process->run();

    $total_processed += $process->getReader()->getProcessedRecordCount();
    $session_processed += $process->getReader()->getSessionProcessedRecordCount();
  }

  // create reply
  return civicrm_api3_create_success([
          'total_processed' => $total_processed,
          'session_processed' => $session_processed,
  ]);
}
