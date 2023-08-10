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
 * Test with specific real-world configurations
 *
 * Some of them will be disabled, because the setup doesn't work on a generic test platform
 *
 * @group headless
 *
 */
class ProcessApiTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create the Client1 process (DropFolderFinder, CSV reader, Api3Processor)
   */
  public function testProcessApiSingle()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));

    // create reader
    $reader = new Reader\CSV();

    // create processor
    $processor = new Processor\TestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process_id = $process->store();

    // run the process via APIv3
    $this->traitCallAPISuccess('AIP', 'run_process', ['pid' => $process_id]);
  }

  /**
   * Create the Client1 process (DropFolderFinder, CSV reader, Api3Processor)
   */
  public function testProcessMultiple()
  {
    // create process 1
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));
    $reader = new Reader\CSV();
    $processor = new Processor\TestProcessor();
    $process1 = new Process($finder, $reader, $processor);
    $process1_id = $process1->store();

    // create process 2
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));
    $reader = new Reader\CSV();
    $processor = new Processor\TestProcessor();
    $process2 = new Process($finder, $reader, $processor);
    $process2_id = $process2->store();

    // run the process via APIv3
    $result = $this->traitCallAPISuccess('AIP', 'run_process', ['pid' => "{$process1_id},{$process2_id}"]);
    $this->assertEquals(4, $result['values']['total_processed'], "should have processed Test01.csv (2 lines) *twice*");
    $this->assertEquals(4, $result['values']['session_processed'], "should have processed Test01.csv (2 lines) *twice*");
  }
}
