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
use CRM_Aip_ExtensionUtil   as E;
use \Exception;

use function GuzzleHttp\Psr7\str;

/** Default timeout for the process no-parallel-execution lock */
const DEFAULT_PROCESS_LOCK_TIMEOUT = 600; // 10 minutes

class TimeoutException extends Exception {}

/**
 * A PROCESS will enclose various components
 **/
class Process extends \Civi\AIP\AbstractComponent
{
  /**
   * @var integer $id
   *  the processor's ID. Only present (>0) if the process is persisted
   */
  protected int $id = 0;

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

  /**
   * @var float timestamp on when the process was started
   */
  protected float $timeout = 0;

  /**
   * @var float timestamp on when the entire PHP process was started
   */
  protected float $timeout_php_process = 0;

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
   * @param Finder $finder
   * @param Reader $reader
   * @param Processor $processor
   * @param int $id
   */
  public function __construct($finder, $reader, $processor, $id = 0)
  {
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
  protected function prepareForRun()
  {
    // calculate processor timeout (individual processing)
    $processing_time_limit = $this->getConfigValue('processing_limit/processing_time');
    if ($processing_time_limit) {
      if (is_numeric($processing_time_limit)) {
        // this expressed as a number of seconds
        $this->timeout = microtime(true) + (float) $processing_time_limit;
      } else {
        // this is a strtotime term
        $timeout_value = strtotime($processing_time_limit);
        if (!$timeout_value) {
          $this->log("Processing time limit invalid: {$processing_time_limit}. Time limit ignored.");
        } else {
          $this->timeout = (float) $timeout_value;
        }
      }
    }

    // set total runtime timeout
    $php_process_time_limit = $this->getConfigValue('processing_limit/php_process_time');
    if ($php_process_time_limit) {
      if (is_numeric($php_process_time_limit)) {
        // this expressed as a number of seconds
        $process_time_ms = (float) $php_process_time_limit;
        $this->timeout_php_process = $_SERVER['REQUEST_TIME_FLOAT'] + $process_time_ms;
      } else {
        // this is a strtotime term
        $timeout_value = strtotime($php_process_time_limit);
        if (!$timeout_value) {
          $this->log("Processing time limit invalid: {$php_process_time_limit}. Time limit ignored.");
        } else {
          $process_time_ms = (float) $timeout_value;
          $this->timeout_php_process = $_SERVER['REQUEST_TIME_FLOAT'] + $process_time_ms;
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
  public function run()
  {
    // locking / parallel execution
    $parallel_execution = $this->getConfigValue('parallel_execution', 0);
    $lock = null;
    if (!$parallel_execution) {
      $lock = \Civi::lockManager()->create("aip-{$this->id}");
      $lock_timeout = $this->getConfigValue('lock_timeout', DEFAULT_PROCESS_LOCK_TIMEOUT);
      $lock->acquire($lock_timeout);
      if (!$lock->isAcquired()) {
        throw new \Exception("Timeout while waiting for lock for process [{$this->id}]. Timeout was {$lock_timeout}s.");
      }
    }

    $this->prepareForRun();

    // find a source
    $is_new_source = false;
    $this->timestamp_start = microtime(true);
    $this->log("Starting process [" . $this->getID() . "]", 'info');

    // check if the components are fine:
    $this->verifyConfiguration();
    $this->finder->verifyConfiguration();
    $this->reader->verifyConfiguration();
    $this->processor->verifyConfiguration();

    // check if this is a resume
    if ($this->reader->getCurrentFile()) {
      // this is a resume
      $source_url = $this->reader->getCurrentFile();
    } else {
      // this is a new source
      $source_url = $this->finder->findNextSource();
      $is_new_source = true;
    }

    // check if there is a source for us
    if ($source_url && $this->reader->canReadSource($source_url)) {
      // claim new source
      if ($is_new_source) {
        $source_url = $this->finder->claimSource($source_url);
      }

      // read and process
      $this->log('Reading source ' . $source_url, 'info');
      $this->reader->initialiseWithSource($source_url);
      $this->log('Reader initialised with source: ' . $source_url, 'info');
      while ($this->shouldProcessMoreRecords() && $this->reader->hasMoreRecords()) {
        try {
          $record = $this->reader->getNextRecord();
          $this->processor->processRecord($record);
          $this->reader->markLastRecordProcessed();
        } catch (TimeoutException $exception) {
            $this->log(E::ts("reader.getNextrecord Timed Out: %1", [1 => $exception->getMessage()]), 'info');
        } catch (\Exception $exception) {
          $this->reader->markLastRecordFailed();
          $this->handleFailedRecord($record, $exception);
          if ($this->continueWithFailedRecord($exception)) {
            $this->log($exception->getMessage(), 'error');
          } else {
            $this->finder->markSourceFailed($source_url);
            $this->reader->markSourceFailed($source_url);
            $this->log(E::ts("Processing aborted due to an exception: %1", [1 => $exception->getMessage()]), 'warning');
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
    $this->log(E::ts("Finished process [%1] after processing %2 records, %3 in total on this source(%4)", [
            1 => $this->getID(),
            2 => $session_processed_count,
            3 => $total_processed_count,
            4 => $source_url,
      ]), 'info');
    $this->store(true);
    $this->flushAllLogs();

    // release lock (if there is one)
    if ($lock) $lock->release();
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
   * Should this process continue, even if at least one record has failed?
   *
   * @return bool
   */
  public function continueWithFailedRecord() : bool
  {
    if(!is_null($this->getConfigValue('continue_with_failed_record'))){
      return true;
    }else{
      return false;
    }
  }

  public function getTypeName() : string
  {
    return E::ts("Processor");
  }  /**
 * Should / could this instance process more records right now?
 *
 * @return bool
 */

  public function shouldProcessMoreRecords() : bool
  {
    // check time based restrictions
    if ($this->timeout_php_process || $this->timeout) {
      $timestamp = microtime(true);
      if ($this->timeout && $timestamp > $this->timeout) {
        $this->log("Process time limit hit.");
        return false;
      }
      if ($this->timeout_php_process && $timestamp > $this->timeout_php_process) {
        $this->log("PHP process time limit hit.");
        return false;
      }
    }

    // check processing count limit
    $processing_record_limit = (int) $this->getConfigValue('processing_limit/record_count');
    if ($processing_record_limit && $this->reader->getSessionProcessedRecordCount() >= $processing_record_limit) {
      $this->log("Processing record limit of {$processing_record_limit} hit.", 'info');
      return false;
    }

    // should the process continue?
    return true;
  }

  /**
   * Get the reader object in this process
   *
   * @return Finder
   */
  public function getFinder() : Finder
  {
    return $this->finder;
  }

  /**
   * Get the reader object in this process
   *
   * @return Reader
   */
  public function getReader() : Reader
  {
    return $this->reader;
  }

  /**
   * Get the reader object in this process
   *
   * @return Processor
   */
  public function getProcessor () : Processor
  {
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
  public function store($debug_output = false) : int
  {
    $serialised_config = json_encode([
        'finder'    => $this->finder->configuration    + ['class' => get_class($this->finder)],
        'reader'    => $this->reader->configuration    + ['class' => get_class($this->reader)],
        'processor' => $this->processor->configuration + ['class' => get_class($this->processor)],
        'process'   => $this->configuration,
     ]);

    $serialised_state = json_encode([
       'finder'    => $this->finder->state,
       'reader'    => $this->reader->state,
       'processor' => $this->processor->state,
       'process'   => $this->state,
     ]);

    if (!$this->id) {
      \CRM_Core_DAO::executeQuery(
              "INSERT INTO civicrm_aip_process (name, class, config, state) VALUES (%1, %2, %3, %4)",
              [
                      1 => [$this->name, 'String'],
                      2 => [\get_class($this), 'String'],
                      3 => [$serialised_config, 'String'],
                      4 => [$serialised_state, 'String']
              ]);
      $this->id = \CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
      $this->log("Process [{$this->id}] created.", 'debug');

    } else {
      \CRM_Core_DAO::executeQuery(
              "UPDATE civicrm_aip_process SET name = %1, class = %2, config = %3, state = %4 WHERE id = %5",
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
      \Civi::log()->debug("to update config in DB:\nUPDATE civicrm_aip_process SET config='" . str_replace('\\', '\\\\' , $serialised_config) . "' WHERE id=?{$this->id};");
      \Civi::log()->debug("to update state in DB: \nUPDATE civicrm_aip_process SET  state='" . str_replace('\\', '\\\\' , $serialised_state)  . "' WHERE id=?{$this->id};");
    }

    return $this->id;
  }

  /**
   * Store the given component
   *
   * @param int $id
   *   component ID (in database)
   */
  public static function restore(int $id) : Process
  {
    $data_query = \CRM_Core_DAO::executeQuery(
            "SELECT name, class, config, state FROM civicrm_aip_process WHERE id = %1",
            [1 => [$id, 'Integer']]);
    if ($data_query->fetch()) {
      try {
        // restore process:
        $config = json_decode($data_query->config, true);
        $state = json_decode($data_query->state, true);

        // restore finder
        $finder = new $config['finder']['class']();
        unset($config['finder']['class']);
        $finder->configuration = $config['finder'] ?? [];
        $finder->state = $state['finder'] ?? [];

        // restore reader
        $reader = new $config['reader']['class']();
        unset($config['reader']['class']);
        $reader->configuration = $config['reader'] ?? [];
        $reader->state = $state['reader'] ?? [];

        // restore processor
        $processor = new $config['processor']['class']();
        unset($config['processor']['class']);
        $processor->configuration = $config['processor'] ?? [];
        $processor->state = $state['processor'] ?? [];

        // finally, reconstruct the process
        $process_class = $data_query->class;
        \Civi::log()->debug("Loading class {$process_class} with process ID [{$id}]");
        $process = new $process_class($finder, $reader, $processor, $id);
        $process->name = $data_query->name;
        $process->configuration = $config['process'] ?? [];
        $process->state = $state['process'] ?? [];

        $process_id = $process->getId();
        // don't do this here: $process->log("Process [{$process_id}] restored.");
        return $process;

      } catch (\Exception $ex) {
        throw new \Exception("Error while loading process [{$id}]");
      }
    } else {
      throw new \Exception("Couldn't find or restore process [{$id}]");
    }
  }

  /**
   * Stores the current state and the exception in the database
   *   if this feature is enabled. This way, it could later be retried.
   *
   * @param array $record the record processed
   * @param Exception $exception the exception/error caught
   *
   * @todo create BAOs and APIv4 for these
   * @return void
   */
  public function handleFailedRecord($record, $exception)
  {
    // check setting
    $store_failed_record = $this->getConfigValue('use_aip_error_log');
    if (!empty($store_failed_record)) {
      // we want to store the failed record in the civicrm_aip_error_log table!

      // first, make sure the ID exists:
      if (empty($this->id)) {
        $this->store();
      }

      // then simply write out the error log entry
      \CRM_Core_DAO::executeQuery(
              "INSERT INTO civicrm_aip_error_log (process_id, error_timestamp, error_message, data) VALUES (%1, %2, %3, %4)",
              [
                      1 => [$this->id, 'Integer'],
                      2 => [date('YmdHis'), 'String'],
                      3 => [$exception->getMessage(), 'String'],
                      4 => [json_encode($record), 'String']
              ]);
    }
  }

  /**
   * Flush all logs
   *
   * @return void
   */
  public function flushAllLogs()
  {
    foreach (self::$log_files as $log_file) {
      fflush($log_file);
    }
  }

  /**
   * The number of records already processed
   *
   * @return integer processed
   */
  public function getProcessedRecordCount()
  {
    return (int) $this->getReader()->getStateValue('processed_record_count', 0);
  }

  /**
   * Number of records failed while processing
   *
   * @return integer failed
   */
  public function getFailedRecordCount()
  {
    return (int) $this->getReader()->getStateValue('failed_record_count', 0);
  }


}