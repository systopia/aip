<?php
/*-------------------------------------------------------+
| SYSTOPIA Automatic Input Processing (AIP) Framework    |
| Copyright (C) 2024 SYSTOPIA                            |
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

declare(strict_types = 1);

namespace Civi\AIP\Reader;

use CRM_Aip_ExtensionUtil as E;

/**
 * This is a simple JSON file reader
 *
 * *********** STATE VALUES ************************
 * current_file            file currently working on
 * last_processed_index    index number, null for none
 * processed_record_count  number of records processed
 * failed_record_count     number of records failed to process
 */
class JSON extends Base {

  public function __construct() {
    parent::__construct();
  }

  /**
   * The file this is working on
   *
   * @var string
   */
  protected $current_file = NULL;

  /**
   * @var int index of the next record to be returned
   */
  protected int $next_record_index = 0;

  /**
   * All records contained by the JSON source
   *
   * @var ?array
   */
  protected ?array $all_records = NULL;

  /**
   * @var resource|false|null
   */
  protected $current_file_handle = NULL;

  public function canReadSource(string $source): bool {
    if (parent::canReadSource($source)) {
      // file exists and is readable, check for the file type
      $file_type = mime_content_type($source);

      if (!in_array($file_type, ['application/json'], TRUE)) {
        $this->log(E::ts("Cannot process files of type '%1'.", [1 => $file_type]), 'warning');
        return FALSE;
      }

      // looks good
      return TRUE;

    }
    else {
      // parent class check already says: cannot access
      return FALSE;
    }
  }

  /**
   * Open and init the JSON file
   *
   * @throws \Exception
   *   Any issues with opening/reading the file.
   */
  public function initialiseWithSource($source) {
    parent::initialiseWithSource($source);

    // check if we're working on the same file
    $previous_file = $this->getCurrentFile();
    if ($previous_file !== $source) {
      // reset processed record index
      $this->resetState();
      $this->setCurrentFile($source);
      $this->log("Started processing file '{$source}'.", 'debug');

    }
    else {
      // resume the file
      $this->next_record_index = $this->getProcessedRecordCount() + $this->getFailedRecordCount();
      if ($this->next_record_index) {
        $this->log("Resume: skipped {$this->next_record_index} previously processed record(s).", 'info');
      }
    }

    // try and open the file
    try {
      // todo: check if 'file_get_contents' can process URLs, including credentials?
      $current_file_content = file_get_contents($source);
    }
    catch (\Exception $ex) {
      $this->log("Couldn't open file '{$source}' for reading", 'error');
      throw $ex;
    }

    // parse the JSON
    try {
      $this->all_records = json_decode($current_file_content, TRUE);
      $record_count = count($this->all_records);
      $this->log("JSON file '{$source}' contains {$record_count} records.");
    }
    catch (\Exception $exception) {
      // @ignoreException a failed parse must not abort processing; continue with an empty record set
      $this->log("Couldn't parse JSON file '{$source}'", 'error');
      $this->markSourceFailed($source);
      $this->all_records = [];
    }
  }

  /**
   * Open the given source
   *
   * @param string $source
   *
   * @return void
   *
   * @throws \Exception
   *   If the file couldn't be opened.
   *
   * @todo this method is never called (JSON reads the whole file via
   *   file_get_contents() in initialiseWithSource() instead) - leftover
   *   from copying CSV.php. TODO: delete?
   */
  protected function openFile(string $source) {
    if ($this->current_file_handle) {
      $this->raiseException(E::ts('There is already an open file', [1 => $source]));
    }

    // check if accessible
    if (!$this->canReadSource($source)) {
      $this->raiseException(E::ts("Cannot open source '%1'.", [1 => $source]));
    }

    // update state
    $this->setCurrentFile($source);
  }

  public function hasMoreRecords(): bool {
    return count($this->all_records) > $this->next_record_index;
  }

  /**
   * Get the next record from the file
   *
   * @return array|null
   *   a record, or null if there are no more records
   *
   * @throws \Exception
   *   If there is a read error.
   */
  public function getNextRecord(): ?array {
    if ($this->hasMoreRecords()) {
      $record = $this->all_records[$this->next_record_index] ?? NULL;
      $this->next_record_index++;

      if (!is_array($record)) {
        // there was an error reading the record
        $this->log('Failed to read record, data type is: ' . gettype($record), 'error');
        throw new \Exception("Couldn't read record.");
      }

      // apply path
      $path = $this->getConfigValue('path');
      if ((bool) $path) {
        $path = explode('/', $path);
        foreach ($path as $path_element) {
          $record = $record[$path_element] ?? NULL;
        }
      }

      return $record;
    }
    else {
      return NULL;
    }
  }

  public function markLastRecordProcessed() {
    $this->records_processed_in_this_session++;
    $this->setProcessedRecordCount($this->getProcessedRecordCount() + 1);
  }

  public function markLastRecordFailed() {
    $this->records_processed_in_this_session++;
    $this->setFailedRecordCount($this->getFailedRecordCount() + 1);
  }

  /**
   * The file this is working on
   *
   * @return string the current file path/url
   */
  public function getCurrentFile() : ?string {
    return $this->getStateValue('current_file');
  }

  /**
   * The file this is working on
   *
   * @param $file string the current file path/url
   */
  protected function setCurrentFile($file) {
    return $this->setStateValue('current_file', $file);
  }

  public function resetState() {
    $this->setStateValue('current_file', NULL);
    $this->all_records = NULL;
    $this->next_record_index = 0;
    parent::resetState();
  }

  /**
   * Mark the given resource as processed/completed
   *
   * @param string $uri
   *   an URI to marked processed/completed
   */
  public function markSourceProcessed(string $uri) {
    $this->setStateValue('current_file', NULL);
  }

  /**
   * Mark the given resource as failed
   *
   * @param string $uri
   *   an URI to marked as FAILED
   */
  public function markSourceFailed(string $uri) {
    $this->setStateValue('current_file', NULL);
  }

}
