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

namespace Civi\AIP\Processor;
use Civi\AIP\Processor\Base as BaseProcessor;

class DelayedTestProcessor extends BaseProcessor
{
  /** @var int number of records processed */
  public int $processed_records = 0;

  /**
   * Process the given record
   *
   * @param array $record
   *
   * @throws \Exception
   */
  public function processRecord($record)
  {
    $delay_time_seconds = (double) $this->getConfigValue('test/sleep_time_seconds', 0.0);
    usleep($delay_time_seconds * 1000000);
    parent::processRecord($record);
    $this->processed_records++;
  }
}