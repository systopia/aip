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

use Civi\AIP\AbstractComponent;
use CRM_Aip_ExtensionUtil as E;

class Base extends AbstractComponent
{
  /**
   * The last record that was processed
   * @var array|null
   */
  protected ?array $last_processed_record = null;

  /**
   *
   * @return void
   */
  /**
   * Process the given record
   *
   * @param array $record
   *
   * @throws \Exception
   */
  public function processRecord($record)
  {
    // do nothing here, override in implementation
    $this->last_processed_record = $record;
  }

  /**
   * @return ?array
   *   get the last record processed by this processor
   */
  public function getLastProcessedRecord() : ?array
  {
    return $this->last_processed_record;
  }

  /**
   * Return the type of the given component
   *
   * @return string
   */
  public function getTypeName() : string
  {
    return E::ts("Processor");
  }
}