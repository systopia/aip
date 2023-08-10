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
 * This is an infinite reader, i.e. it simply "invents" data records
 */

class InfiniteRecords extends Base
{
  /** @var ?string the source currently used */
  protected ?string $source = null;

  public function __construct() {
    parent::__construct();
  }

  /**
   * Simply 'invent' records. forever.
   */
  public function getNextRecord(): ?array
  {
    return [
        'field1' => random_bytes(16),
        'field2' => random_bytes(16),
        'field3' => random_bytes(16),
        'field4' => random_bytes(16),
    ];
  }


  public function canReadSource(string $source): bool
  {
    return true;
  }

  /**
   * Open and init the CSV file
   *
   * @throws \Exception
   *   any issues with opening/reading the file
   */
  public function initialiseWithSource($source)
  {
    $this->source = $source;
  }

  /**
   * Open the given source
   *
   * @param string $source
   *
   * @return void
   *
   * @throws \Exception
   *   if the file couldn't be opened
   */
  protected function openFile(string $source)
  {
    $this->source = $source;
  }

  public function hasMoreRecords(): bool
  {
    return true;
  }

  public function markSourceProcessed(string $uri)
  {
    // nothing to do
  }

  public function markSourceFailed(string $uri)
  {
    // nothing to do
  }

  public function getCurrentFile(): ?string
  {
    return $this->source;
  }
}