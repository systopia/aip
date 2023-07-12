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
 */
class CSV extends Base
{
  /************ CONFIG VALUES ***********************
   *  csv_separator        (default ';')
   *  csv_string_enclosure (default '";"')
   *  csv_string_escape    (default '\')
   *************************************************/

  /************ STATE VALUES *********************
   * current_file            file currently working on
   * processed_record_count  number of records processed
   * failed_record_count     number of records failed to process
   **********************************************/

  /**
   * The file this is working on
   *
   * @var resource $current_file_handle
   */
  protected $current_file_handle = null;

  /**
   * The headers of the current CSV file
   *
   * @var array $current_file_headers
   */
  protected $current_file_headers = null;

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

  public function initialiseWithSource($source)
  {
    parent::initialiseWithSource($source);

    // check if we're working on another file
    $current_file = $this->getCurrentFile();
    if ($current_file == $source) {
      // we should restart where we left off:
      // 1) open file
      $this->openFile($current_file);

      // 2) read headers
      $this->current_file_headers = $this->readNextRecord();

      // 3) skip all already processed rows
      $line_nr = $this->getProcessedRecordCount();
      for ($skip = 1; $skip <= $line_nr; $skip ++) {
        $this->skipNextRecord();
      }

    } else {
      // this is a NEW file, re-init file
      $this->resetState();
      $this->openFile($source);
      $this->current_file_headers = $this->readNextRecord();
    }

    $this->readNextRecord();
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
    $this->resetState();
    $this->setCurrentFile($source);

    // read first record
    $this->readNextRecord();
  }

  public function hasMoreRecords(): bool
  {
    return isset($this->next_record);
  }

  /**
   * Get the next record from the file
   *
   * @return array|null
   *   a record, or null if there are no more records
   */
  public function getNextRecord(): ?array
  {
    if ($this->hasMoreRecords()) {
      $next_record = $this->next_record;
      $this->next_record = $this->readNextRecord();
      return $next_record;
    } else {
      return null;
    }
  }

  /**
   * Read the next record from the open file
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
      throw new \Exception("No file handle!");
    }

    // read record
    $separator = $this->getConfigValue('csv_separator', ';');
    $enclosure = $this->getConfigValue('csv_string_enclosure', '"');
    $escape = $this->getConfigValue('csv_string_escape', '\\');
    $encoding = $this->getConfigValue('csv_string_encoding', 'UTF8');
    $record = fgetcsv($this->current_file_handle, null, $separator, $enclosure, $escape);

    // encode record
    if ($encoding != 'UTF8') {
      $record = mb_convert_encoding($record, 'UTF8', $encoding);
    }

    return $record;
  }

  public function markLastRecordProcessed()
  {
    $this->setProcessedRecordCount($this->getProcessedRecordCount() + 1);
  }

  public function markLastRecordFailed()
  {
    $this->setFailedRecordCount($this->getFailedRecordCount() + 1);
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

}