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
  protected float $timestamp_start;

  /**
   * @var string process name
   */
  protected string $name = '';

  /**
   * @var string documentation
   */
  protected string $documentation = '';

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
    $this->timestamp_start = 0.0;
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
    // find a source
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
    }

    // check if there is a source for us
    if ($source_url && $this->reader->canReadSource($source_url)) {
      // read and process
      $this->log('Reading source ' . $source_url, 'info');
      $this->reader->initialiseWithSource($source_url);
      $this->log('Reader initialised with source: ' . $source_url, 'info');
      while ($this->shouldProcessMoreRecords() && $this->reader->hasMoreRecords()) {
        $record = $this->reader->getNextRecord();
        try {
          $this->processor->processRecord($record);
          $this->reader->markLastRecordProcessed();
        } catch (\Exception $exception) {
          $this->reader->markLastRecordFailed();
          if (!$this->continueWithFailedRecord()) {
            $this->finder->markSourceFailed($source_url);
            throw new Exception(E::ts("Processing aborted due to an exception: %1", [1 => $exception->getMessage()]));
          }
        }
      }
      $this->finder->markSourceProcessed($source_url);
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
   $this->store();
   $this->flushAllLogs();
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
    // todo: setting?
    return false;
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
    // check processing count limit
    $processing_record_limit = (int) $this->getConfigValue('processing_limit/record_count');
    if ($processing_record_limit && $this->reader->getSessionProcessedRecordCount() >= $processing_record_limit) {
      $this->log("Processing record limit of {$processing_record_limit} hit.", 'info');
      return false;
    }

    // check processing time limit
    $processing_time_limit = (int) $this->getConfigValue('processing_limit/processing_time');
    if ($processing_time_limit) {
      $elapsed_time = microtime(true) - $this->timestamp_start;
      if ($elapsed_time > $processing_time_limit) {
        $this->log("Processing time limit of {$processing_time_limit}s exceeded.", 'info');
        return false;
      }
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
  public function store() : int
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
    $this->log("Process [{$this->id}] suspended.", 'debug');
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
        $finder->configuration = $config['finder']['config'] ?? [];
        $finder->state = $state['finder']['state'] ?? [];

        // restore reader
        $reader = new $config['reader']['class']();
        unset($config['reader']['class']);
        $reader->configuration = $config['reader'] ?? [];
        $reader->state = $state['reader'] ?? [];

        // restore reader
        $processor = new $config['processor']['class']();
        unset($config['processor']['class']);
        $processor->configuration = $config['processor'] ?? [];
        $processor->state = $state['processor'] ?? [];

        // finally, reconstruct the process
        $process_class = $data_query->class;
        \Civi::log()->debug("Loading class {$process_class}");
        $process = new $process_class($finder, $reader, $processor, $id);
        $process->name = $data_query->name;
        $process->configuration = $config['process'] ?? [];
        $process->state = $state['process'] ?? [];

        $process_id = $process->getId();
        $process->log("Process [{$process_id}] restored.");
        return $process;

      } catch (\Exception $ex) {
        throw new \Exception("Error while loading process [{$id}]");
      }
    } else {
      throw new \Exception("Couldn't find or restore process [{$id}]");
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
}