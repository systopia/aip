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

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Basic CVS Reader tests
 *
 * @group headless
 *
 */
class CSVReaderTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor)
   */
  public function testReadSimpleCSV()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));

    // create reader
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);

    // create processor
    $processor = new Processor\TestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);

    // run the process
    $process->run();

    // check results
    $this->assertEquals(2, $reader->getProcessedRecordCount(), "This should've processed the two records in the file.");
    $this->assertEquals(0, $reader->getFailedRecordCount());
  }

  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor),
   * But then process one record, suspend,
   *      revive, process the remaining record
   */
  public function testReadWithStopAndRestore()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));

    // create reader
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'ISO-8859-15']);

    // create processor
    $processor = new Processor\TestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue('processing_limit/record_count', 1);

    // run the process
    $process->run();
    $last_processed_record = $process->getProcessor()->getLastProcessedRecord();
    $this->assertEquals("25120510", reset($last_processed_record), "This should've read the first record of the file");

    // check results
    $this->assertEquals(1, $reader->getSessionProcessedRecordCount(), "This should've processed the only one record because of the processing_limit/record_count = 1 limit.");
    $this->assertEquals(0, $reader->getFailedRecordCount());

    // revive the process
    $process2 = Process::restore($process->getID());
    $process2->getReader()->setConfigValue('processing_limit/record_count', 1);

    // run the process
    $process2->run();

    // check results
    $processor = $process2->getProcessor();
    $last_processed_record = $process2->getProcessor()->getLastProcessedRecord();
    $this->assertEquals("25120511", $last_processed_record['BLZ'], "This should've read the *second* record of the file");
    $this->assertEquals(1, $process2->getReader()->getSessionProcessedRecordCount(), "This should've processed the only one record because of the processing_limit/record_count = 1 limit.");
    $this->assertEquals(0, $process2->getReader()->getFailedRecordCount());
  }
}
