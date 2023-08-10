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
use Civi\AIP\Finder\InfiniteSources;

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
   * Create a simple process and see if the timeout works
   */
  public function testSimpleTimeout()
  {
    // create dummy finder
    $finder = new InfiniteSources();

    // create infinite reader
    $reader = new InfiniteRecords();

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
   * Create a couple of processes and see if the 'total runtime' timeout works
   */
  public function testTotalRuntimeTimeout()
  {
    // we need this baseline for the PHP process time
    $test_time_elapsed = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];

    // run process 1
    $finder1 = new InfiniteSources();
    $reader1 = new InfiniteRecords();
    $processor1 = new Processor\DelayedTestProcessor();
    $process1 = new Process($finder1, $reader1, $processor1);
    $process1->setConfigValue('processing_limit/processing_time', 0.010);
    $process1->setConfigValue('processing_limit/php_process_time', $test_time_elapsed + 0.015);
    $process1->run();
    $this->assertTrue($reader1->getProcessedRecordCount() > 0, "First process didn't process any records. There's something wrong with the test setup. Or the process was paused during testing.");

    // run process 2
    $finder2 = new InfiniteSources();
    $reader2 = new InfiniteRecords();
    $processor2 = new Processor\DelayedTestProcessor();
    $process2 = new Process($finder2, $reader2, $processor2);
    $process2->setConfigValue('processing_limit/processing_time', 0.010);
    $process2->setConfigValue('processing_limit/php_process_time', $test_time_elapsed + 0.015);
    $process2->run();

    // check stats. Quite tricky, but the second one should've been cut short by the combined runtime of the two
    $this->assertTrue($reader2->getProcessedRecordCount() > 0, "Second process didn't process any records. There's something wrong with the test setup. Or the process was paused during testing.");
    $this->assertTrue($reader1->getProcessedRecordCount() > $reader2->getProcessedRecordCount(), "The second process should've been cut short by the accumulated time limit");
  }
}
