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

use Civi\FormProcessor\API\Exception;
use CRM_Aip_ExtensionUtil as E;
/**
 * This is a simple CVS file reader
 *
 ************ CONFIG VALUES ***********************
 *  csv_separator        (default ';')
 *  csv_string_enclosure (default '";"')
 *  csv_string_escape    (default '\')

 ************ STATE VALUES ************************
 * current_file            file currently working on
 * processed_record_count  number of records processed
 * failed_record_count     number of records failed to process
 */
class CSV extends Base
{
  public function __construct() {
    parent::__construct();
  }

  /**
   * The file this is working on
   *
   * @var resource $current_file_handle
   */
  protected $current_file_handle = null;

  /**
   * The headers of the current CSV file
   *
   * @var ?array $current_file_headers
   */
  protected ?array $current_file_headers = null;

  /**
   * The record currently being processed
   *
   * @var ?array
   */
  protected ?array $current_record = null;

  /**
   * The record to be processed next
   *
   * @var ?array
   */
  protected ?array $lookahead_record = null;

  /**
   * The record that was processed last
   *
   * @var ?array
   */
  protected ?array $last_processed_record = null;

  /**
   * @var int timestamp since this component was created
   */
  protected int $running_since = 0;


  public function canReadSource(string $source): bool
  {
    if (parent::canReadSource($source)) {
      // file exists and is readable, check for the file type
      $file_type = mime_content_type($source);

      if (!in_array($file_type, ['text/csv', 'text/plain'])) {
        $this->log(E::ts("Cannot process files of type '%1'.", [1 => $file_type]), 'warning');
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
   * Open and init the CSV file
   *
   * @throws \Exception
   *   any issues with opening/reading the file
   */
  public function initialiseWithSource($source)
  {
    parent::initialiseWithSource($source);

    // check if we're working on another file
    $this->current_record = null;
    $current_file = $this->getCurrentFile();
    if ($current_file == $source) {
      // same file: we should restart/resume where we left off:
      // 1) open file
      $this->openFile($current_file);

      // 2) read headers
      $this->current_file_headers = $this->getNextRecord();

      // 3) skip all already processed rows
      $records_previously_processed = $this->getProcessedRecordCount() + $this->getFailedRecordCount();
      for ($skip = 0; $skip < $records_previously_processed; $skip ++) {
        $this->getNextRecord();
      }
      if ($skip) $this->log("Resume: skipped {$skip} previously processed record(s).", 'info');

    } else {
      // this is a NEW file, re-init file
      $this->resetState();
      $this->openFile($source);
      $this->log("Opened new source: {$source}", 'info');
      $this->current_file_headers = $this->getNextRecord();
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
   *   if the file couldn't be opened
   */
  protected function openFile(string $source)
  {
    if ($this->current_file_handle) {
      $this->raiseException(E::ts("There is already an open file", [1 => $source]));
    }

    // check if accessible
    if (!$this->canReadSource($source)) {
      $this->raiseException(E::ts("Cannot open source '%1'.", [1 => $source]));
    }

    // open the file
    $this->current_file_handle = fopen($source, 'r');
    if (empty($this->current_file_handle)) {
      $this->raiseException(E::ts("Cannot read source '%1'.", [1 => $source]));
    }

    // update state
    $this->setCurrentFile($source);

    // read first record
    $this->lookahead_record = $this->readNextRecord();
  }

  public function hasMoreRecords(): bool
  {
    return is_array($this->lookahead_record);
  }

  /**
   * Get the next record from the file
   *
   * @return array|null
   *   a record, or null if there are no more records
   *
   * @throws \Exception
   *   if there is a read error
   */
  public function getNextRecord(): ?array
  {
    if ($this->hasMoreRecords()) {
      $this->current_record   = $this->lookahead_record;
      $this->lookahead_record = $this->readNextRecord();

      // map to headers
      $record = $this->current_record;
      if (is_array($this->current_file_headers)) {
        // todo: setting to disable labelling?

        $file_headers = $this->current_file_headers;
        if (count($record) != count($file_headers)) {
          $this->fixHeaderRecordColumnMismatch($file_headers, $record);
        }
        $record = array_combine($file_headers, $record);
      }

      if ($record === true) {
        $this->log("Skipped empty line in CSV.", 'info');
        return $this->getNextRecord();
      }

      if (!is_array($record)){
        // there was an error reading the record
        $this->log("Failed to read record, data type is: " . gettype($record), 'error');
        throw new \Exception("Couldn't read record.");
      }

      return $record;
    } else {
      return null;
    }
  }

  /**
   * Read the next record from the open file
   *
   * @todo needed?
   */
  public function skipNextRecord() {
    if (empty($this->current_file_handle)) {
      throw new \Exception("No file handle!");
    }

    // read record
    $separator = $this->getConfigValue('csv_separator', ';');
    $enclosure = $this->getConfigValue('csv_string_enclosure', '"');
    $escape = $this->getConfigValue('csv_string_escape', '\\');
    fgetcsv($this->current_file_handle, $separator, $enclosure, $escape);
  }

  /**
   * Read the next record from the open file
   */
  public function readNextRecord() {
    if (empty($this->current_file_handle)) {
      throw new \Exception("No file opened.");
    }

    // read record
    // todo: move to class properties
    $separator = $this->getConfigValue('csv_separator', ';');
    $enclosure = $this->getConfigValue('csv_string_enclosure', '"');
    $escape = $this->getConfigValue('csv_string_escape', '\\');
    $encoding = $this->getConfigValue('csv_string_encoding', 'UTF8');
    $record = fgetcsv($this->current_file_handle, null, $separator, $enclosure, $escape);

    if ($record) {
      // apply the encoding
      // encode record using utf8_encode helper
      if ($encoding != 'UTF8') {
        if ($encoding == 'utf8_encode') {
          // use the utf8_encode function
          $new_record = [];
          foreach ($record as $key => $value) {
            $new_record[$key] = utf8_encode($value);
          }
          $record = $new_record;
        } else {
          // use mb_convert
          $record = mb_convert_encoding($record, 'UTF8', $encoding);
        }
      }
    } else {
      // this should be the end of the file
      $record = null;
    }

    return $record;
  }

  public function markLastRecordProcessed()
  {
    $this->records_processed_in_this_session++;
    $this->setProcessedRecordCount($this->getProcessedRecordCount() + 1);
    $this->current_record = $this->lookahead_record;
  }

  public function markLastRecordFailed()
  {
    $this->records_processed_in_this_session++;
    $this->setFailedRecordCount($this->getFailedRecordCount() + 1);
    $this->current_record = $this->lookahead_record;
  }

  /**
   * The file this is working on
   *
   * @return string the current file path/url
   */
  public function getCurrentFile()
  {
    return $this->getStateValue('current_file');
  }

  /**
   * The file this is working on
   *
   * @param $file string the current file path/url
   */
  protected function setCurrentFile($file)
  {
    return $this->setStateValue('current_file', $file);
  }

  public function resetState()
  {
    $this->setStateValue('current_file', null);
    parent::resetState();
  }

  /**
   * Mark the given resource as processed/completed
   *
   * @param string $uri
   *   an URI to marked processed/completed
   */
  public function markSourceProcessed(string $uri)
  {
    $this->setStateValue('current_file', null);
  }

  /**
   * Mark the given resource as failed
   *
   * @param string $uri
   *   an URI to marked as FAILED
   */
  public function markSourceFailed(string $uri)
  {
    $this->setStateValue('current_file', null);
  }


  /**
   * Fix a mismatch of the column count of the headers,
   *  and the number of entries in the record
   *
   * @param array $file_headers
   *   the column headers
   *
   * @param array $record
   *   the record
   *
   * @return void
   */
  protected function fixHeaderRecordColumnMismatch(array &$file_headers, array &$record)
  {
    // if there are not enough headers, just add some generic ones
    while (count($file_headers) < count($record)) {
      $file_headers[] = "Column " . (count($file_headers) + 1);
    }

    // if there are not enough values, just add some empty ones
    while (count($file_headers) > count($record)) {
      $record[] = '';
    }
  }
}