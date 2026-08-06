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

use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Basic CVS Reader tests
 *
 * @group headless
 * @covers \Civi\AIP\Reader\JSON
 *
 */
class JSONOnlineReaderTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUp(): void {
    parent::setUp();
  }

  protected function getJsonFileUrl($path = NULL) {
    return $this->getTestResourcePath('finder/termine.json');
  }

  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor)
   */
  public function testReadOnlineJSON() {
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
  public function testReadWithStopAndRestart() {
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
    $this->assertEquals(
      '470580',
      $last_processed_record['_event_ID'] ?? NULL,
      "This should've processed the first record of the file"
    );

    // check results
    $this->assertEquals(
      1,
      $reader->getSessionProcessedRecordCount(),
      "This should've processed the only one record because of the processing_limit/record_count = 1 limit."
    );
    $this->assertEquals(0, $reader->getFailedRecordCount());
    $process->store();

    // revive the process
    $process2 = Process::restore($process->getID());
    // there should only be one left
    $process2->setConfigValue('processing_limit/record_count', 2);

    // run the process
    $process2->run();

    // check results
    $last_processed_record = $process2->getProcessor()->getLastProcessedRecord();
    $this->assertIsArray($last_processed_record);
    $this->assertEquals(
      '470581',
      $last_processed_record['_event_ID'],
      "This should've read the *second* record of the file"
    );
    $this->assertEquals(
      1,
      $process2->getReader()->getSessionProcessedRecordCount(),
      "This should've processed the only one record because of the processing_limit/record_count = 1 limit."
    );
    $this->assertEquals(0, $process2->getReader()->getFailedRecordCount());
  }

  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor),
   * But then process one record, suspend,
   *      revive, process the remaining record
   */
  public function testSkipIdenticalFiles() {
    // create finder
    $finder = new Finder\StaticUrlFileFinder();
    $finder->setConfigValue('url', $this->getJsonFileUrl());
    $finder->setConfigValue('detect_changes', FALSE);

    // create reader
    $reader = new Reader\JSON();
    $reader->setConfiguration(['path' => 'Veranstaltung']);

    // create processor
    $processor = new Processor\TestProcessor();

    // run the process -> should process all (2) records
    $process = new Process($finder, $reader, $processor);
    $process->getFinder()->setConfigValue('detect_changes', FALSE);
    $process->run();
    $this->assertEquals(2, $processor->getProcessedRecordCount(),
                        'Should have processed two records.');

    $process->getFinder()->setConfigValue('detect_changes', TRUE);
    $process->run();
    $this->assertEquals(2, $processor->getProcessedRecordCount(),
                        'Should NOT have processed two more records from the identical source: detect_changes is on.');

    $process->getFinder()->setConfigValue('detect_changes', FALSE);
    $process->run();
    $this->assertEquals(4, $processor->getProcessedRecordCount(),
                        'SHOULD have processed two more records from the identical source: detect_changes is off.');

  }

}
