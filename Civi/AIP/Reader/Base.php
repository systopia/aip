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

abstract class Base extends AbstractComponent
{
  /**
   * Test if the Reader can access and read the given source
   *
   * @param string $source
   *   URI identifying a source
   *
   * @return bool
   *   can the given source be read
   */
  abstract static public function canReadSource(string  $source) : bool;

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


}