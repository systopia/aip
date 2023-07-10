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

namespace Civi\AIP\Reader;

use Civi\AIP\AbstractComponent;
use CRM_Aip_ExtensionUtil as E;

abstract class Base extends AbstractComponent
{
  /**
   * See if the current source has more records
   *
   * @return bool
   *   can the given source be read
   */
  abstract public function hasMoreRecords() : bool;

  /**
   * Read and return the next record
   *
   * @return array|null
   *   next record as an array data set, or null if there is no more records
   */
  abstract public function getNextRecord() : array;

  /**
   * Mark the last record as delivered by getNextRecord() as processed
   */
  abstract public function markLastRecordProcessed();

  /**
   * Mark the last record as delivered by getNextRecord() as failed
   */
  abstract public function markLastRecordFailed();

  /**
   * Test if the Reader can access and read the given source
   *
   * @param string $source
   *   URI identifying a source
   *
   * @return bool
   *   can the given source be read
   */
  public function canReadSource(string $source): bool
  {
    // check if the source exists
    if (!file_exists($source)) {
      $this->log(E::ts("Couldn't find source '%1'.", [1 => $source]));
      return false;
    }

    // check if the source is readable
    if (!is_readable($source)) {
      $this->log(E::ts("Couldn't open source '%1'.", [1 => $source]));
      return false;
    }

    // from the abstract point of view, this is it
    return true;
  }

  /**
   * Return the type of the given component
   *
   * @return string
   */
  public function getType()
  {
    return E::ts("Reader");
  }
}