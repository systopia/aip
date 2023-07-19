<?php

namespace Civi\AIP;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\CiviEnvBuilder;

/**
 * Basic test to see if the components interact as they should
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *  Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *  rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *  If this test needs to manipulate schema or truncate tables, then either:
 *     a. Do all that using setupHeadless() and Civi\Test.
 *     b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 *
 * Mainly tests the regex extraction
 *  and the actions: copy, copy_append, copy_ltrim_zeros, set(ok), align_date, unset, strtolower, sha1, preg_replace, calculate, map(ok)
 */
class BasicTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor)
   */
  public function testSetupViaCode()
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

    // run the process
    $process->run();
  }

  /**
   * Create the Client1 process (DropFolderFinder, CSV reader, Api3Processor)
   */
  public function testSetupClient1ViaCode()
  {
    // create finder
    $finder = new Finder\DropFolderFinder();
    $finder->setConfigValue('filter/file_name', '#30_abo-digi.*[.]csv#');
    $finder->setConfigValue('folder/inbox', '/srv/direktmarketing/aip/inbox');
    $finder->setConfigValue('folder/processing', '/srv/direktmarketing/aip/processing');
    $finder->setConfigValue('folder/processed', '/srv/direktmarketing/aip/processed');
    $finder->setConfigValue('folder/uploading', '/srv/direktmarketing/aip/uploading');
    $finder->setConfigValue('folder/failed', '/srv/direktmarketing/aip/failed');

    // create reader
    $reader = new Reader\CSV();
    $finder->setConfigValue('folder/failed', '/srv/direktmarketing/aip/failed');

    // create processor
    $processor = new Processor\Api3();
    $processor->setConfigValue('api_entity', 'FormProcessor');
    $processor->setConfigValue('api_action', 'import_digiabo');
    $processor->setConfigValue('trim_parameters', 'all');

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue("log/file", "/srv/direktmarketing/aip/processing.log");
    $process->setConfigValue('processing_limit/record_count', 1);
    $process->store(); // check log for DB update tips


    // run the process
    $process->run();
  }
}
