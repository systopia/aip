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
use Civi\AIP\Reader\InfiniteRecords;
use \Civi\Test\Api3TestTrait;

/**
 * Tests about record and time restrictions
 *
 * @group headless
 *
 */
class ProcessTimeRestrictionTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple process (UrlRequestFile, CSV reader, Api3 processor)
   */
  public function testSimpleTimeout()
  {
    // create dummy finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test03.csv'));

    // create infinite reader
    $reader = new Reader\InfiniteRecords();

    // create processor
    $processor = new Processor\DelayedTestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue('processing_limit/processing_time', 0.01);

    // run the process
    $process->run();

    // check stats. Quite tricky, because this depends on the calculating power of the machine running this
    $this->assertGreaterThan(10, $reader->getProcessedRecordCount(), "A good amount of records in this time");
    $this->assertLessThan(10000, $reader->getProcessedRecordCount(), "This should've not been that many");
  }

  /**
   * Create a simple process (UrlRequestFile, CSV reader, Api3 processor)
   */
  public function testCombinedTimeout()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test03.csv'));

    // create reader
    $reader = new Reader\InfiniteRecords();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);

    // create processor
    $processor = new Processor\DelayedTestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue('processing_limit/processing_time', 0.01);

    // run the process
    $process->run();

    // check stats. Quite tricky, because this depends on the calculating power of the machine running this
    $this->assertGreaterThan(10, $reader->getProcessedRecordCount(), "A good amount of records in this time");
    $this->assertLessThan(10000, $reader->getProcessedRecordCount(), "This should've not been that many");
  }
}
