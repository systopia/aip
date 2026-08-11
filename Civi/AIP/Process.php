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

namespace Civi\AIP;

use Civi\AIP\Finder\Base    as Finder;
use Civi\AIP\Reader\Base    as Reader;
use Civi\AIP\Processor\Base as Processor;
use CRM_Aip_ExtensionUtil   as E;
use Exception;

/**
 * Default timeout for the process no-parallel-execution lock */
// 10 minutes
const DEFAULT_PROCESS_LOCK_TIMEOUT = 600;

class TimeoutException extends Exception {}

/**
 * A PROCESS will enclose various components
 */
// phpcs:ignore Generic.Files.OneClassPerFile.MultipleFound, Generic.Files.OneObjectStructurePerFile.MultipleFound
class Process extends \Civi\AIP\AbstractComponent {
  /**
   * @var integer
   *  the processor's ID. Only present (>0) if the process is persisted
   */
  protected int $id = 0;

  /**
   * @var \Civi\AIP\Finder\Base
   *   The finder instance used in this process
   */
  protected Finder $finder;

  /**
   * @var \Civi\AIP\Reader\Base
   *   The reader instance used in this process
   */
  protected Reader $reader;

  /**
   * @var \Civi\AIP\Processor\Base
   *   The processor instance used in this process
   */
  protected Processor $processor;

  /**
   * @var float timestamp on when the process was started
   */
  protected float $timeout = 0;

  /**
   * @var float timestamp on when the entire PHP process was started
   */
  protected float $timeout_php_process = 0;

  /**
   * @var float timestamp on when this run() started, used to log the duration
   */
  protected float $timestamp_start = 0;

  /**
   * @var string process name
   */
  protected string $name = '';

  /**
   * @var string documentation
   */
  protected string $documentation = '';

  /**
   * Create a new process with the given finder, reader and processor
   *
   * @param \Civi\AIP\Finder\Base $finder
   * @param \Civi\AIP\Reader\Base $reader
   * @param \Civi\AIP\Processor\Base $processor
   * @param int $id
   */
  public function __construct($finder, $reader, $processor, $id = 0) {
    parent::__construct();
    $this->id = $id;
    $this->process = $this;
    $this->finder = $finder;
    $this->finder->process = $this;
    $this->reader = $reader;
    $this->reader->process = $this;
    $this->processor = $processor;
    $this->processor->process = $this;
  }

  /**
   * Internal function to prepare for the actual RUN.
   *
   * Will be called as one of the first things in the the run() function
   *
   * @return void
   */
  protected function prepareForRun() {
    // calculate processor timeout (individual processing)
    $processing_time_limit = $this->getConfigValue('processing_limit/processing_time');
    if (is_scalar($processing_time_limit) && (bool) $processing_time_limit) {
      if (is_numeric($processing_time_limit)) {
        // this expressed as a number of seconds
        $this->timeout = microtime(TRUE) + (float) $processing_time_limit;
      }
      else {
        // this is a strtotime term
        $timeout_value = strtotime((string) $processing_time_limit);
        if (!(bool) $timeout_value) {
          $this->log("Processing time limit invalid: {$processing_time_limit}. Time limit ignored.");
        }
        else {
          $this->timeout = (float) $timeout_value;
        }
      }
    }

    // set total runtime timeout
    $php_process_start = is_numeric($_SERVER['REQUEST_TIME_FLOAT'] ?? NULL)
      ? (float) $_SERVER['REQUEST_TIME_FLOAT']
      : microtime(TRUE);
    $php_process_time_limit = $this->getConfigValue('processing_limit/php_process_time');
    if (is_scalar($php_process_time_limit) && (bool) $php_process_time_limit) {
      if (is_numeric($php_process_time_limit)) {
        // this expressed as a number of seconds
        $process_time_ms = (float) $php_process_time_limit;
        $this->timeout_php_process = $php_process_start + $process_time_ms;
      }
      else {
        // this is a strtotime term
        $timeout_value = strtotime((string) $php_process_time_limit);
        if (!(bool) $timeout_value) {
          $this->log("Processing time limit invalid: {$php_process_time_limit}. Time limit ignored.");
        }
        else {
          $process_time_ms = (float) $timeout_value;
          $this->timeout_php_process = $php_process_start + $process_time_ms;
        }
      }
    }
  }

  /**
   * Run the given process, this is the main loop for processing records
   *
   * @return void
   *
   * @throws Exception  should an unhandled exception appear
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public function run() {
    // locking / parallel execution
    $parallel_execution = $this->getConfigValue('parallel_execution', 0);
    $lock = NULL;
    if (!(bool) $parallel_execution) {
      $lock = \Civi::lockManager()->create("aip-{$this->id}");
      $configured_lock_timeout = $this->getConfigValue('lock_timeout', DEFAULT_PROCESS_LOCK_TIMEOUT);
      $lock_timeout = is_numeric($configured_lock_timeout)
        ? (int) $configured_lock_timeout
        : DEFAULT_PROCESS_LOCK_TIMEOUT;
      $lock->acquire($lock_timeout);
      if (!$lock->isAcquired()) {
        throw new \Exception("Timeout while waiting for lock for process [{$this->id}]. Timeout was {$lock_timeout}s.");
      }
    }

    $this->prepareForRun();

    // find a source
    $is_new_source = FALSE;
    $this->timestamp_start = microtime(TRUE);
    $this->log('Starting process [' . $this->getID() . ']', 'info');

    // check if the components are fine:
    $this->verifyConfiguration();
    $this->finder->verifyConfiguration();
    $this->reader->verifyConfiguration();
    $this->processor->verifyConfiguration();

    // check if this is a resume
    $current_file = $this->reader->getCurrentFile();
    if ($current_file !== NULL && $current_file !== '') {
      // this is a resume
      $source_url = $current_file;
    }
    else {
      // this is a new source
      $source_url = $this->finder->findNextSource();
      $is_new_source = TRUE;
    }

    // check if there is a source for us
    if ($source_url !== NULL && $source_url !== '' && $this->reader->canReadSource($source_url)) {
      // claim new source
      if ($is_new_source) {
        $source_url = $this->finder->claimSource($source_url);
      }

      // read and process
      $this->log('Reading source ' . $source_url, 'info');
      $this->reader->initialiseWithSource($source_url);
      $this->log('Reader initialised with source: ' . $source_url, 'info');
      while ($this->shouldProcessMoreRecords() && $this->reader->hasMoreRecords()) {
        $record = [];
        try {
          $next_record = $this->reader->getNextRecord();
          if ($next_record === NULL) {
            break;
          }
          $record = $next_record;
          $this->processor->processRecord($record);
          $this->reader->markLastRecordProcessed();
        }
        catch (TimeoutException $exception) {
          $this->log(E::ts('reader.getNextrecord Timed Out: %1', [1 => $exception->getMessage()]), 'info');
        }
        catch (\Exception $exception) {
          // @ignoreException a failed record must not abort the whole batch
          $this->reader->markLastRecordFailed();
          $this->handleFailedRecord($record, $exception);
          if ($this->continueWithFailedRecord()) {
            $this->log($exception->getMessage(), 'error');
          }
          else {
            $this->finder->markSourceFailed($source_url);
            $this->reader->markSourceFailed($source_url);
            $this->log(E::ts('Processing aborted due to an exception: %1', [1 => $exception->getMessage()]), 'warning');
            break;
          }
        }
      }
      // mark source as processed, if we're done with this file
      if (!$this->reader->hasMoreRecords()) {
        $this->finder->markSourceProcessed($source_url);
        $this->reader->markSourceProcessed($source_url);
      }
    }

    // store current state
    $total_processed_count = $this->getReader()->getProcessedRecordCount();
    $session_processed_count = $this->getReader()->getSessionProcessedRecordCount();
    $duration = microtime(TRUE) - $this->timestamp_start;
    $this->log(E::ts(
      'Finished process [%1] after processing %2 records, %3 in total on this source(%4), duration: %5s',
      [
        1 => $this->getID(),
        2 => $session_processed_count,
        3 => $total_processed_count,
        4 => $source_url,
        5 => round($duration, 2),
      ]
    ), 'info');
    $this->store(TRUE);
    $this->flushAllLogs();

    // release lock (if there is one)
    if ($lock !== NULL) {
      $lock->release();
    }
  }

  /**
   * Get the process ID
   *
   * @return int
   */
  public function getID() {
    return $this->id;
  }

  /**
   * Should this process continue, even if at least one record has failed?
   *
   * @return bool
   */
  public function continueWithFailedRecord() : bool {
    if ($this->getConfigValue('continue_with_failed_record') !== NULL) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function getTypeName() : string {
    return E::ts('Processor');
  }

  /**
   * Should / could this instance process more records right now?
   *
   * @return bool
   */
  public function shouldProcessMoreRecords() : bool {
    // check time based restrictions
    if ($this->timeout_php_process !== 0.0 || $this->timeout !== 0.0) {
      $timestamp = microtime(TRUE);
      if ($this->timeout !== 0.0 && $timestamp > $this->timeout) {
        $this->log('Process time limit hit.');
        return FALSE;
      }
      if ($this->timeout_php_process !== 0.0 && $timestamp > $this->timeout_php_process) {
        $this->log('PHP process time limit hit.');
        return FALSE;
      }
    }

    // check processing count limit
    $configured_record_limit = $this->getConfigValue('processing_limit/record_count');
    $processing_record_limit = is_numeric($configured_record_limit) ? (int) $configured_record_limit : 0;
    if ($processing_record_limit !== 0 && $this->reader->getSessionProcessedRecordCount() >= $processing_record_limit) {
      $this->log("Processing record limit of {$processing_record_limit} hit.", 'info');
      return FALSE;
    }

    // should the process continue?
    return TRUE;
  }

  /**
   * Get the reader object in this process
   *
   * @return \Civi\AIP\Finder\Base
   */
  public function getFinder() : Finder {
    return $this->finder;
  }

  /**
   * Get the reader object in this process
   *
   * @return \Civi\AIP\Reader\Base
   */
  public function getReader() : Reader {
    return $this->reader;
  }

  /**
   * Get the reader object in this process
   *
   * @return \Civi\AIP\Processor\Base
   */
  public function getProcessor () : Processor {
    return $this->processor;
  }

  /**
   * STORE/RESTORE LOGIC
   */

  /**
   * Store the given component
   *
   * @return int
   *   component ID
   */
  public function store($debug_output = FALSE) : int {
    $serialised_config = (string) json_encode([
      'finder'    => $this->finder->configuration + ['class' => get_class($this->finder)],
      'reader'    => $this->reader->configuration + ['class' => get_class($this->reader)],
      'processor' => $this->processor->configuration + ['class' => get_class($this->processor)],
      'process'   => $this->configuration,
    ]);

    $serialised_state = (string) json_encode([
      'finder'    => $this->finder->state,
      'reader'    => $this->reader->state,
      'processor' => $this->processor->state,
      'process'   => $this->state,
    ]);

    if ($this->id === 0) {
      \CRM_Core_DAO::executeQuery(
              'INSERT INTO civicrm_aip_process (name, class, config, state) VALUES (%1, %2, %3, %4)',
              [
                1 => [$this->name, 'String'],
                2 => [\get_class($this), 'String'],
                3 => [$serialised_config, 'String'],
                4 => [$serialised_state, 'String'],
              ]);
      $this->id = (int) \CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
      $this->log("Process [{$this->id}] created.", 'debug');

    }
    else {
      \CRM_Core_DAO::executeQuery(
              'UPDATE civicrm_aip_process SET name = %1, class = %2, config = %3, state = %4 WHERE id = %5',
              [
                1 => [$this->name, 'String'],
                2 => [\get_class($this), 'String'],
                3 => [$serialised_config, 'String'],
                4 => [$serialised_state, 'String'],
                5 => [$this->id, 'Integer'],
              ]);
    }
    $this->log("Process [{$this->id}] stored/suspended.", 'debug');

    if ($debug_output) {
      \Civi::log()->debug(
        "to update config in DB:\nUPDATE civicrm_aip_process SET config='"
        . str_replace('\\', '\\\\', $serialised_config) . "' WHERE id=?{$this->id};"
      );
      \Civi::log()->debug(
        "to update state in DB: \nUPDATE civicrm_aip_process SET  state='"
        . str_replace('\\', '\\\\', $serialised_state) . "' WHERE id=?{$this->id};"
      );
    }

    return $this->id;
  }

  /**
   * Store the given component
   *
   * @param int $id
   *   component ID (in database)
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
  public static function restore(int $id) : Process {
    /** @var \CRM_Core_DAO $data_query */
    $data_query = \CRM_Core_DAO::executeQuery(
            'SELECT name, class, config, state FROM civicrm_aip_process WHERE id = %1',
            [1 => [$id, 'Integer']]);
    if ($data_query->fetch()) {
      try {
        // restore process:
        $config = self::decodeStoredData($data_query->config, $id);
        $state = self::decodeStoredData($data_query->state, $id);

        // restore finder
        $finder_config = self::getStoredSection($config, 'finder');
        $finder_class = self::getStoredComponentClass($finder_config['class'] ?? NULL, Finder::class, $id);
        unset($finder_config['class']);
        $finder = new $finder_class();
        $finder->configuration = $finder_config;
        $finder->state = self::getStoredSection($state, 'finder');

        // restore reader
        $reader_config = self::getStoredSection($config, 'reader');
        $reader_class = self::getStoredComponentClass($reader_config['class'] ?? NULL, Reader::class, $id);
        unset($reader_config['class']);
        $reader = new $reader_class();
        $reader->configuration = $reader_config;
        $reader->state = self::getStoredSection($state, 'reader');

        // restore processor
        $processor_config = self::getStoredSection($config, 'processor');
        $processor_class = self::getStoredComponentClass($processor_config['class'] ?? NULL, Processor::class, $id);
        unset($processor_config['class']);
        $processor = new $processor_class();
        $processor->configuration = $processor_config;
        $processor->state = self::getStoredSection($state, 'processor');

        // finally, reconstruct the process
        $process_class = self::getStoredComponentClass($data_query->class, self::class, $id);
        \Civi::log()->debug("Loading class {$process_class} with process ID [{$id}]");
        $process = new $process_class($finder, $reader, $processor, $id);
        $process->name = is_string($data_query->name) ? $data_query->name : '';
        $process->configuration = self::getStoredSection($config, 'process');
        $process->state = self::getStoredSection($state, 'process');

        $process_id = $process->getID();
        // don't do this here: $process->log("Process [{$process_id}] restored.");
        return $process;

      }
      catch (\Exception $ex) {
        throw new \Exception("Error while loading process [{$id}]", 0, $ex);
      }
    }
    else {
      throw new \Exception("Couldn't find or restore process [{$id}]");
    }
  }

  /**
   * @param mixed $data
   *
   * @return array<mixed>
   *
   * @throws \Exception
   */
  protected static function decodeStoredData($data, int $id) : array {
    $decoded = is_string($data) ? json_decode($data, TRUE) : NULL;
    if (!is_array($decoded)) {
      throw new \Exception("Process [{$id}] has an invalid configuration or state.");
    }
    return $decoded;
  }

  /**
   * @param array<mixed> $data
   *
   * @return array<mixed>
   */
  protected static function getStoredSection(array $data, string $section) : array {
    $section_data = $data[$section] ?? NULL;
    return is_array($section_data) ? $section_data : [];
  }

  /**
   * @template T of \Civi\AIP\AbstractComponent
   *
   * @param mixed $class
   * @param class-string<T> $base_class
   *
   * @return class-string<T>
   *
   * @throws \Exception
   */
  protected static function getStoredComponentClass($class, string $base_class, int $id) : string {
    if (!is_string($class) || !is_a($class, $base_class, TRUE)) {
      throw new \Exception("Process [{$id}] has an invalid {$base_class} class.");
    }
    return $class;
  }

  /**
   * Stores the current state and the exception in the database
   *   if this feature is enabled. This way, it could later be retried.
   *
   * @param array $record the record processed
   * @param \Exception $exception the exception/error caught
   *
   * @todo create BAOs and APIv4 for these
   * @return void
   */
  public function handleFailedRecord($record, $exception) {
    // check setting
    $store_failed_record = $this->getConfigValue('use_aip_error_log');
    if ((bool) $store_failed_record) {
      // we want to store the failed record in the civicrm_aip_error_log table!

      // first, make sure the ID exists:
      if ($this->id === 0) {
        $this->store();
      }

      // then simply write out the error log entry
      \CRM_Core_DAO::executeQuery(
              'INSERT INTO civicrm_aip_error_log (process_id, error_timestamp, error_message, data)'
              . ' VALUES (%1, %2, %3, %4)',
              [
                1 => [$this->id, 'Integer'],
                2 => [date('YmdHis'), 'String'],
                3 => [$exception->getMessage(), 'String'],
                4 => [json_encode($record), 'String'],
              ]);
    }
  }

  /**
   * Flush all logs
   *
   * @return void
   */
  public function flushAllLogs() {
    foreach (self::$log_files as $log_file) {
      fflush($log_file);
    }
  }

  /**
   * The number of records already processed
   *
   * @return integer processed
   */
  public function getProcessedRecordCount() {
    $record_count = $this->getReader()->getStateValue('processed_record_count', 0);
    return is_numeric($record_count) ? (int) $record_count : 0;
  }

  /**
   * Number of records failed while processing
   *
   * @return integer failed
   */
  public function getFailedRecordCount() {
    $record_count = $this->getReader()->getStateValue('failed_record_count', 0);
    return is_numeric($record_count) ? (int) $record_count : 0;
  }

}
