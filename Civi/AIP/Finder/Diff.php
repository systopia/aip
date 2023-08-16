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

namespace Civi\AIP\Finder;

use CRM_Aip_ExtensionUtil as E;
use Civi\AIP\Finder\Base as Finder;

/**
 * A FINDER that stores the previously processed files of the same type,
 *   and only passes the DIFF records to the readers.
 *
 * In a way, this is a "wrapper" for another finder, and
 *   it needs another finder "inside" to actually look for new files
 *
 * It has the following configuration options:
 *  filter/file_name  - regular expression to filter for file name
 *  folder/temp       - folder in which the currently active file is stored
 *  folder/history    - folder in which this module will store previously processed versions of the files
 *  header            - number of rows to exclude from the diff process, e.g. a CSV header
 *  column/id         - column to be used as ID - the only way to generate the status 'changed'
 *  column/status     - column in the file to list the status (added,changed,removed). If it doesn't exist, it will be added
 **/
class Diff extends Base
{
  /** @var Finder the finder to actually look for sources  */
  protected $inner_finder = null;

  /**
   * Check if the component is ready,
   *   i.e. configured correctly.
   *
   * @throws \Exception
   *   an exception will be thrown if something's wrong with the
   *     configuration or state
   */
  public function verifyConfiguration()
  {
    // first check the actual finder
    $this->inner_finder->verifyConfiguration();

    // todo: check if diff tools are there

  }

  public function getTypeName() : string
  {
    return E::ts("CSV Diff Finder");
  }


  /**
   * See if there is a new source in the unter
   *
   * @return ?string
   */
  public function findNextSource(): ?string
  {
    // // get
    $next_source = $this->inner_finder->findNextSource();
    if (!$next_source) return null;

    $last_source = $this->findLastSource($next_source);
    if ($next_source) {
      // step 1: identify the last processed version of the file
      // todo:

      // step 2: separate headers

      // step 3: run diff

      //
    }

    return null;
  }


  //        FUNCTIONS DIRECTLY DELEGATED TO WRAPPED FINDER

  /**
   * This function claims the source file by moving it to the 'processing' folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function claimSource(string $file_path)
  {
    return $this->inner_finder->claimSource($file_path);
  }

  /**
   * This function marks the resource as processed by moving it into the respective folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function markSourceProcessed(string $file_path)
  {
    $this->inner_finder->markSourceProcessed($file_path);
  }

  /**
   * This function marks the resource as processed by moving it into the respective folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function markSourceFailed(string $file_path)
  {
    $this->inner_finder->markSourceFailed($file_path);
  }
}