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

namespace Civi\AIP;

use Civi\AIP\Finder\Base    as Finder;
use Civi\AIP\Reader\Base    as Reader;
use Civi\AIP\Processor\Base as Processor;
use \Exception;

/**
 * A PROCESS will enclose various components
 **/
class Process extends \Civi\AIP\AbstractComponent
{
  /**
   * @var integer id
   *   the ID of this process
   */
  protected int $id;


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
   * Create a new process with the given finder, reader and processor
   *
   * @param Finder $finder
   * @param Reader $reader
   * @param Processor $processor
   * @param int $id
   */
  public function __construct($finder, $reader, $processor, $id = 0)
  {
    $this->id = $id;
    $this->finder = $finder;
    $this->finder->process = $this;
    $this->reader = $reader;
    $this->reader->process = $this;
    $this->processor = $processor;
    $this->processor->process = $this;
  }

  public static function load($id) : Process
  {
    throw new \Exception("Persistence not yet implemented");
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
   * Get the process ID
   *
   * @return int
   */
  public function getID()
  {
    return $this->id;
  }

  /**
   * Run the given process
   *
   * @return void
   *
   * @throws Exception  should an unhandled exception appear
   */
  public function run()
  {
    // find a source
    $source_url = $this->finder->findNextSource();

    // check if there is a source for us
    if ($source_url && $this->reader->canReadSource($source_url)) {
      // read and process
      $this->log('Reading source ' . $source_url);
      $this->reader->initialiseWithSource($source_url);
      while ($this->shouldProcessMoreRecords() && $this->reader->hasMoreRecords()) {
        $record = $this->reader->getNextRecord();
        try {
          $this->processor->processRecord($record);
          $this->reader->markLastRecordProcessed();
        } catch (\Exception $exception) {
          $this->reader->markLastRecordFailed();
          if (!$this->continueWithFailedRecord()) {
            throw new Exception("Processing aborted due to an exception.");
          }
        }
      }
    }
  }

  /**
   * Should / could this instance process more records right now?
   *
   * @return bool
   */
  public function shouldProcessMoreRecords() : bool
  {
    // should the process continue?
    return true;
  }

  /**
   * Should this process continue, even if at least one record has failed?
   *
   * @return bool
   */
  public function continueWithFailedRecord() : bool
  {
    // todo: setting?
    return false;
  }

  public function getType()
  {
    return E::ts("Processor");
  }
}