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

declare(strict_types = 1);

namespace Civi\AIP\Reader;

use CRM_Aip_ExtensionUtil as E;

/**
 * This is a simple CVS file reader
 *
 * *********** CONFIG VALUES ***********************
 *  csv_separator        (default ';')
 *  csv_string_enclosure (default '";"')
 *  csv_string_escape    (default '\')
 *
 * *********** STATE VALUES ************************
 * current_file            file currently working on
 * processed_record_count  number of records processed
 * failed_record_count     number of records failed to process
 */
class CSV extends Base {

  public function __construct() {
    parent::__construct();
  }

  /**
   * The file this is working on
   *
   * @var resource
   */
  protected $current_file_handle = NULL;

  /**
   * The headers of the current CSV file
   *
   * @var ?array
   */
  protected ?array $current_file_headers = NULL;

  /**
   * The record currently being processed
   *
   * @var ?array
   */
  protected ?array $current_record = NULL;

  /**
   * The record to be processed next
   *
   * @var ?array
   */
  protected ?array $lookahead_record = NULL;

  /**
   * The record that was processed last
   *
   * @var ?array
   */
  protected ?array $last_processed_record = NULL;

  public function canReadSource(string $source): bool {
    if (parent::canReadSource($source)) {
      // file exists and is readable, check for the file type
      $file_type = mime_content_type($source);

      if (!in_array($file_type, ['text/csv', 'text/plain'], TRUE)) {
        $this->log(E::ts("Cannot process files of type '%1'.", [1 => $file_type]), 'warning');
        return FALSE;
      }

      // looks good
      return TRUE;

    }
    else {
      // parent class check says: cannot access
      return FALSE;
    }
  }

  /**
   * Open and init the CSV file
   *
   * @throws \Exception
   *   Any issues with opening/reading the file.
   */
  public function initialiseWithSource($source) {
    parent::initialiseWithSource($source);

    // check if we're working on another file
    $this->current_record = NULL;
    $current_file = $this->getCurrentFile();
    if ($current_file === $source) {
      // same file: we should restart/resume where we left off:
      // 1) open file
      $this->openFile($current_file);

      // 2) read headers
      $this->current_file_headers = $this->getNextRecord();

      // 3) skip all already processed rows
      $records_previously_processed = $this->getProcessedRecordCount() + $this->getFailedRecordCount();
      for ($skip = 0; $skip < $records_previously_processed; $skip++) {
        $this->getNextRecord();
      }
      if ($records_previously_processed > 0) {
        $this->log("Resume: skipped {$records_previously_processed} previously processed record(s).", 'info');
      }

    }
    else {
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
   *   If the file couldn't be opened.
   */
  protected function openFile(string $source) {
    if (is_resource($this->current_file_handle)) {
      $this->raiseException(E::ts('There is already an open file', [1 => $source]));
    }

    // check if accessible
    if (!$this->canReadSource($source)) {
      $this->raiseException(E::ts("Cannot open source '%1'.", [1 => $source]));
    }

    // open the file
    $file_handle = fopen($source, 'r');
    if (!is_resource($file_handle)) {
      $this->raiseException(E::ts("Cannot read source '%1'.", [1 => $source]));
    }
    $this->current_file_handle = $file_handle;

    // update state
    $this->setCurrentFile($source);

    // read first record
    $this->lookahead_record = $this->readNextRecord();
  }

  public function hasMoreRecords(): bool {
    return is_array($this->lookahead_record);
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
    $record = $this->lookahead_record;
    if ($record === NULL) {
      return NULL;
    }

    $this->current_record   = $record;
    $this->lookahead_record = $this->readNextRecord();

    // map to headers
    if (is_array($this->current_file_headers)) {
      // todo: setting to disable labelling?

      $file_headers = $this->current_file_headers;
      if (count($record) !== count($file_headers)) {
        $this->fixHeaderRecordColumnMismatch($file_headers, $record);
      }
      $record = array_combine($file_headers, $record);
    }

    return $record;
  }

  /**
   * Read the next record from the open file
   *
   * @todo needed?
   */
  public function skipNextRecord() {
    if (!is_resource($this->current_file_handle)) {
      throw new \Exception('No file handle!');
    }

    // read record
    $separator = $this->getConfigString('csv_separator', ';');
    $enclosure = $this->getConfigString('csv_string_enclosure', '"');
    $escape = $this->getConfigString('csv_string_escape', '\\');
    fgetcsv($this->current_file_handle, NULL, $separator, $enclosure, $escape);
  }

  /**
   * Read the next record from the open file
   */
  public function readNextRecord() {
    if (!is_resource($this->current_file_handle)) {
      throw new \Exception('No file opened.');
    }

    // read record
    // todo: move to class properties?
    $separator = $this->getConfigString('csv_separator', ';');
    $enclosure = $this->getConfigString('csv_string_enclosure', '"');
    $escape = $this->getConfigString('csv_string_escape', '\\');
    $encoding = $this->getConfigString('csv_string_encoding', 'UTF8');
    $skip_empty_lines = $this->getConfigValue('skip_empty_lines', FALSE);

    $record = fgetcsv($this->current_file_handle, NULL, $separator, $enclosure, $escape);

    // check for empty lines
    if ((bool) $skip_empty_lines) {
      if (is_array($record) && current($record) === NULL && count($record) <= 1) {
        // this is an empty line, move on to the next one
        // todo: address recursion issue for files _only_ consisting of line breaks
        $this->increaseLinesSkipped();
        return $this->readNextRecord();
      }
    }

    if ($record !== FALSE) {
      // apply the encoding
      // encode record using utf8_encode helper
      if ($encoding !== 'UTF8') {
        if ($encoding === 'utf8_encode') {
          // utf8_encode() is deprecated as of PHP 8.2; it only ever converted
          // ISO-8859-1 to UTF-8, so mb_convert_encoding() is the direct replacement.
          $new_record = [];
          foreach ($record as $key => $value) {
            $new_record[$key] = $value === NULL ? NULL : mb_convert_encoding($value, 'UTF8', 'ISO-8859-1');
          }
          $record = $new_record;
        }
        else {
          // use mb_convert
          $record = mb_convert_encoding($record, 'UTF8', $encoding);
        }
      }
    }
    else {
      // this should be the end of the file
      $record = NULL;
    }

    return $record;
  }

  public function markLastRecordProcessed() {
    $this->records_processed_in_this_session++;
    $this->setProcessedRecordCount($this->getProcessedRecordCount() + 1);
    $this->current_record = $this->lookahead_record;
  }

  public function markLastRecordFailed() {
    $this->records_processed_in_this_session++;
    $this->setFailedRecordCount($this->getFailedRecordCount() + 1);
    $this->current_record = $this->lookahead_record;
  }

  /**
   * The file this is working on
   *
   * @return string the current file path/url
   */
  public function getCurrentFile() : ?string {
    $current_file = $this->getStateValue('current_file');
    return is_string($current_file) ? $current_file : NULL;
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
  protected function fixHeaderRecordColumnMismatch(array &$file_headers, array &$record) {
    // if there are not enough headers, just add some generic ones
    while (count($file_headers) < count($record)) {
      $file_headers[] = 'Column ' . (count($file_headers) + 1);
    }

    // if there are not enough values, just add some empty ones
    while (count($file_headers) > count($record)) {
      $record[] = '';
    }
  }

  /**
   * Simply increases the 'lines_skipped' counter
   */
  protected function increaseLinesSkipped() {
    $lines_skipped_state = $this->getStateValue('lines_skipped');
    $lines_skipped = is_numeric($lines_skipped_state) ? (int) $lines_skipped_state : 0;
    $lines_skipped++;
    $this->setStateValue('lines_skipped', $lines_skipped);
  }

}
