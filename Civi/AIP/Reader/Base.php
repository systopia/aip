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
  /** @var int counting the records processed in this session */
  protected int $records_processed_in_this_session = 0;

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
  abstract public function getNextRecord() : ?array;

  /**
   * Mark the last record as delivered by getNextRecord() as processed
   */
  public function markLastRecordProcessed()
  {
    $this->records_processed_in_this_session++;
    $processed_record_count = $this->getProcessedRecordCount();
    $this->setProcessedRecordCount($processed_record_count + 1);
  }

  /**
   * Get the
   * @return int
   */
  public function getSessionProcessedRecordCount() : int
  {
    return $this->records_processed_in_this_session;
  }

  /**
   * Mark the last record as delivered by getNextRecord() as failed
   */
  public function markLastRecordFailed()
  {
    $this->records_processed_in_this_session++;
    $failed_record_count = $this->getFailedRecordCount();
    $this->setFailedRecordCount($failed_record_count + 1);
  }

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
      $this->log(E::ts("Couldn't find source '%1'.", [1 => $source]), 'warning');
      return false;
    }

    // check if the source is readable
    if (!is_readable($source)) {
      $this->log(E::ts("Couldn't open source '%1'.", [1 => $source]), 'warning');
      return false;
    }

    // from the abstract point of view, this is it
    return true;
  }

  /**
   * @param string $source
   */
  public function initialiseWithSource($source)
  {
    // anything?
  }

  /**
   * Return the type of the given component
   *
   * @return string
   */
  public function getTypeName() : string
  {
    return E::ts("Reader");
  }

  /**
   * The number of records already processed
   *
   * @return integer processed
   */
  public function getProcessedRecordCount()
  {
    return (int) $this->getStateValue('processed_record_count', 0);
  }

  /**
   * Number of records processed
   *
   * @param $record_count int the new record count
   */
  protected function setProcessedRecordCount(int $record_count)
  {
    return $this->setStateValue('processed_record_count', $record_count);
  }

  /**
   * Mark the given resource as processed/completed
   *
   * @param string $uri
   *   an URI to marked processed/completed
   */
  public abstract function markSourceProcessed(string $uri);

  /**
   * Mark the given resource as failed
   *
   * @param string $uri
   *   an URI to marked as FAILED
   */
  public abstract function markSourceFailed(string $uri);

  /**
   * Number of records failed while processing
   *
   * @param $record_count int the new record count
   */
  protected function setFailedRecordCount(int $record_count)
  {
    return $this->setStateValue('failed_record_count', $record_count);
  }

  /**
   * Number of records failed while processing
   *
   * @return integer failed
   */
  public function getFailedRecordCount()
  {
    return (int) $this->getStateValue('failed_record_count');
  }

  /**
   * Get the number of records delivered by this reader
   *
   * @return int
   */
  public function getRecordCount()
  {
    return $this->getProcessedRecordCount() + $this->getFailedRecordCount();
  }

  /**
   * Reset the state of this module
   *
   * @return void
   */
  public function resetState()
  {
    $this->setProcessedRecordCount(0);
    $this->setFailedRecordCount(0);
    parent::resetState();
  }
}