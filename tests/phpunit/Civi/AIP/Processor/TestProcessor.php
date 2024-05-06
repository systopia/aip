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

class TestProcessor extends BaseProcessor
{
  /** @var int $processed_record_count number of records processed by this processor */
  protected int $processed_record_count = 0;

  /**
   * Process the given record
   *
   * @param array $record
   *
   * @throws \Exception
   */
  public function processRecord($record)
  {
    parent::processRecord($record);
    $this->log("Processed record #" . (1 + $this->process->getReader()->getRecordCount()), 'debug');
    $this->processed_record_count++;
  }

  /**
   *
   * @return int number of records processed by this processor
   */
  public function getProcessedRecordCount()
  {
    return $this->processed_record_count;
  }
}