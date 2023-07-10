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

/**
 * This is a simple CVS file reader
 */
class CSV extends Base
{

  static public function canReadSource(string $source): bool
  {
    // TODO: Implement canReadSource() method.
  }

  public function hasMoreRecords(): bool
  {
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