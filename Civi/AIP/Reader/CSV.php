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

use CRM_Aip_ExtensionUtil as E;
/**
 * This is a simple CVS file reader
 */
class CSV extends Base
{
  /**
   * The file this is working on
   *
   * @var string $current_file
   */
  protected $current_file = null;

  /**
   * The record currently being processed
   *
   * @var ?array
   */
  protected ?array $current_record = null;

  /**
   * The record currently being processed
   *
   * @var ?array
   */
  protected ?array $next_record = null;

  public function canReadSource(string $source): bool
  {
    if (parent::canReadSource($source)) {
      // file exists and is readable, check for the file type
      $file_type = mime_content_type($source);

      if (!in_array($file_type, ['text/csv', 'text/plain'])) {
        $this->log(E::ts("Cannot process files of type '%1'.", [1 => $file_type]));
        return false;
      }

      // looks good
      return true;

    } else {
      // parent class check says: cannot access
      return false;
    }
  }

  /**
   * Open the given source
   *
   * @param string $source
   *
   * @return void
   */
  protected function openFile($source)
  {
    if (!$this->canReadSource($source)) {
      $this->raiseException(E::ts("Cannot open source '%1'.", [1 => $source]));
    }


  }

  public function hasMoreRecords(): bool
  {
    if (!isset($this->next_record)) {

    }
    // TODO: Implement hasMoreRecords() method.
  }

  public function getNextRecord(): array
  {

    // TODO: Implement getNextRecord() method.
  }

  public function markLastRecordProcessed()
  {
    // TODO: Implement markLastRecordProcessed() method.
  }

  public function markLastRecordFailed()
  {
    // TODO: Implement markLastRecordFailed() method.
  }
}