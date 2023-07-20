<?php

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
class ProcessSpecsTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create the Client1 process (DropFolderFinder, CSV reader, Api3Processor)
   */
  public function testSetupClient1ViaCode()
  {
    $this->markTestSkipped("Specific configuration example, needs specific environment");

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
    $reader->setConfigValue('csv_string_encoding', 'utf8_encode');

    // create processor
    $processor = new Processor\Api3();
    $processor->setConfigValue('api_entity', 'FormProcessor');
    $processor->setConfigValue('api_action', 'import_digiabo');
    $processor->setConfigValue('trim_parameters', 'all');

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue("log/file", "/srv/direktmarketing/aip/processing.log");
    $process->setConfigValue('processing_limit/record_count', 1000);
    $process->store(true); // check log for DB update tips

    // run the process
    $process->run();
  }
}
