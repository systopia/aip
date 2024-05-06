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

use function GuzzleHttp\Psr7\parse_request;

/**
 * Basic CVS Reader tests
 *
 * @group headless
 *
 */
class JSONOnlineReaderTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  public function setUp(): void
  {
    parent::setUp();
  }

  protected function getJsonFileUrl($path = null)
  {
    return \Civi::paths()->getPath('[civicrm.files]/ext/aip/tests/resources/finder/termine.json');
  }

  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor)
   */
  public function testReadOnlineJSON()
  {
    // create finder
    $finder = new Finder\StaticUrlFileFinder();
    $finder->setConfigValue('url', $this->getJsonFileUrl());
    $finder->setConfigValue('detect_changes', 'true');

    // create reader
    $reader = new Reader\JSON();
    $reader->setConfiguration(['path' => 'Veranstaltung']);

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
  public function testReadWithStopAndRestart()
  {
    // create finder
    $finder = new Finder\StaticUrlFileFinder();
    $finder->setConfigValue('url', $this->getJsonFileUrl());
    $finder->setConfigValue('detect_changes', 'true');

    // create reader
    $reader = new Reader\JSON();
    $reader->setConfiguration(['path' => 'Veranstaltung']);

    // create processor
    $processor = new Processor\TestProcessor();

    // run the process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue('processing_limit/record_count', 1);
    $process->run();
    $last_processed_record = $process->getProcessor()->getLastProcessedRecord();
    $this->assertEquals("470580", $last_processed_record['_event_ID'] ?? null, "This should've processed the first record of the file");

    // check results
    $this->assertEquals(1, $reader->getSessionProcessedRecordCount(), "This should've processed the only one record because of the processing_limit/record_count = 1 limit.");
    $this->assertEquals(0, $reader->getFailedRecordCount());
    $process->store();


    // revive the process
    $process2 = Process::restore($process->getID());
    $process2->setConfigValue('processing_limit/record_count', 2); // there should only be one left

    // run the process
    $process2->run();

    // check results
    $last_processed_record = $process2->getProcessor()->getLastProcessedRecord();
    $this->assertEquals("470581", $last_processed_record['_event_ID'], "This should've read the *second* record of the file");
    $this->assertEquals(1, $process2->getReader()->getSessionProcessedRecordCount(), "This should've processed the only one record because of the processing_limit/record_count = 1 limit.");
    $this->assertEquals(0, $process2->getReader()->getFailedRecordCount());
  }

}
