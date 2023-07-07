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

use Civi\AIP\Finder\Base    as Finder;
use Civi\AIP\Reader\Base    as Reader;
use Civi\AIP\Processor\Base as Processor;

/**
 * A PROCESS will enclose various components
 **/
abstract class Process extends \Civi\AIP\AbstractComponent
{
  /**
   * @var Finder $finder
   *   The finder instance used in this process
   */
  protected Finder $finder;

  /**
   * @var Reader $reader
   *   The reader instance used in this process
   */
  protected Reader $reader;

  /**
   * @var Processor $processor
   *   The processor instance used in this process
   */
  protected Processor $processor;


  public static function getProcesses($active = true) : array
  {
    // todo
  }

  /**
   * Get the finder component
   *
   * @return Finder
   */
  public function getFinder() : Finder
  {
    return $this->finder;
  }

  /**
   * Run the given process
   *
   * @return void
   */
  public function run()
  {
    $finder = $this->getFinder();
    $source = $finder->findNextSource();
    if ($source) {
      if ($this->reader->canReadSource($source)) {

      }
    }

    $this->reader = $reader = $finder->getReader();
    if ($reader) {
      if ($reader->isResume()) {
        $this->log($reader->getId(), "Resuming processing resource: " . $reader->getInputName());
      } else {
        $this->log($reader->getId(), "Starting processing resource: " . $reader->getInputName());
      }
    }

    // read and process
    while ($this->processMoreRecords() && $reader->hasMoreRecords()) {
      $record = $reader->getNextRecord();
      try {
        if ($this->processor->processRecord($record)) {
          $reader->markRecordProcessed($record);
        }
      } catch (Exception $exception) {
        $reader->markRecordFailed($record);
        if (!$this->continueWithFailedRecord()) {

        }
      }
    }
  }

  public function processMoreRecords() : bool
  {
    // should the process continue?
    return true;
  }
}