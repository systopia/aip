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
use \Civi\Test\Api3TestTrait;

/**
 * Basic DropFolder Finder
 *
 * @group headless
 *
 */
class DropFolderFinderTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple process (DropFolderFinder, CSV reader, Api3 processor)
   */
  public function testSetup()
  {
    // create finder
    $finder = new Finder\DropFolderFinder();
    $finder->setConfigValue('filter/file_name', '#[a-z0-9]+.csv#');
    $finder->setConfigValue('folder/inbox', $this->createTempDir());
    $finder->setConfigValue('folder/processing', $this->createTempDir());
    $finder->setConfigValue('folder/processed', $this->createTempDir());
    $finder->setConfigValue('folder/uploading', $this->createTempDir());
    $finder->setConfigValue('folder/failed', $this->createTempDir());

    // create reader + put I file there
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);
    $file = $this->getTestResourcePath('input/CSV/Test03.csv');
    copy($file, $finder->getConfigValue('folder/inbox') . DIRECTORY_SEPARATOR . 'sdasoi3423.csv');

    // create processor
    $processor = new Processor\Api3();
    $processor->setConfigValue('api_entity', 'Contact');
    $processor->setConfigValue('api_action', 'create');
    $processor->setConfigValue('api_values', ['contact_type' => 'Individual']);
    $processor->setConfigValue('parameter_mapping', ['Vorname' => 'first_name', 'Nachname' => 'last_name', 'E-Mail' => 'email']);

    // create a process
    $process = new Process($finder, $reader, $processor);

    // run the process
    $process->run();

    // check stats
    $this->assertEquals(3, $reader->getProcessedRecordCount(), "This should've processed the two records in the file.");
    $this->assertEquals(0, $reader->getFailedRecordCount());

    // check results
    try {
      \civicrm_api3('Contact', 'getsingle', ['email' => 'anto@exis.ts']);
      \civicrm_api3('Contact', 'getsingle', ['email' => 'berty@exis.ts']);
      \civicrm_api3('Contact', 'getsingle', ['email' => 'cc@exis.ts']);
    } catch (\CRM_Core_Exception $ex) {
      $this->fail("Contacts not created, the API calls probably didn't go through!");
    }
  }

  /**
   * Create a 'faulty' process (DropFolderFinder, CSV reader, ExceptionTest processor)
   */
  public function testProcessorException()
  {
    // create finder
    $finder = new Finder\DropFolderFinder();
    $finder->setConfigValue('filter/file_name', '#[a-z0-9]+.csv#');
    $finder->setConfigValue('folder/inbox', $this->createTempDir());
    $finder->setConfigValue('folder/processing', $this->createTempDir());
    $finder->setConfigValue('folder/processed', $this->createTempDir());
    $finder->setConfigValue('folder/uploading', $this->createTempDir());
    $finder->setConfigValue('folder/failed', $this->createTempDir());

    // create reader + put I file there
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);
    $file = $this->getTestResourcePath('input/CSV/Test03.csv');
    copy($file, $finder->getConfigValue('folder/inbox') . DIRECTORY_SEPARATOR . 'sdasoi3423.csv');

    // create processor
    $processor = new Processor\ExceptionTestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);

    // run the process
    $process->run();

    // check stats
    $this->assertEquals(0, $reader->getProcessedRecordCount(), "This should've processed the two records in the file.");
    $this->assertEquals(1, $reader->getFailedRecordCount());

    // make sure the reader's file is cleared
    $this->assertNull($reader->getCurrentFile(), "The current file hasn't been reset after an abortion");
  }

  /**
   * Create a 'faulty' process (DropFolderFinder, CSV reader, ExceptionTest processor)
   * AND check if the writing out of records worked
   */
  public function testProcessorExceptionLog()
  {
    // create finder
    $finder = new Finder\DropFolderFinder();
    $finder->setConfigValue('filter/file_name', '#[a-z0-9]+.csv#');
    $finder->setConfigValue('folder/inbox', $this->createTempDir());
    $finder->setConfigValue('folder/processing', $this->createTempDir());
    $finder->setConfigValue('folder/processed', $this->createTempDir());
    $finder->setConfigValue('folder/uploading', $this->createTempDir());
    $finder->setConfigValue('folder/failed', $this->createTempDir());

    // create reader + put I file there
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);
    $file = $this->getTestResourcePath('input/CSV/Test03.csv');
    copy($file, $finder->getConfigValue('folder/inbox') . DIRECTORY_SEPARATOR . 'sdasoi3423.csv');

    // create processor
    $processor = new Processor\ExceptionTestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue('use_aip_error_log', 1);

    // run the process
    $process->run();

    // check the record
    $record = \CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_aip_error_log WHERE process_id = %1;", [1 => [$process->getID(), 'Integer']]);
    $record->fetch();
    $this->assertEquals('oh-oh', $record->error_message, "The error messages does not match the exceptions' error message");
    $this->assertEqualsWithDelta(strtotime('now'), strtotime($record->error_timestamp), 1.0, "The error messages timestamp is off.");
    $this->assertIsArray(json_decode($record->data, true), "The data was not stored as a JSON array");
    $this->assertNotEmpty(json_decode($record->data, true), "The data was not stored as a JSON array");
  }
}
