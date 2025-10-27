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
 * Basic CVS Reader tests
 *
 * @group headless
 *
 */
class ProcessorExceptionTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple process (UrlRequestFile, CSV reader, Api3 processor)
   */
  public function test()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test03.csv'));

    // create reader
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);

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
}
