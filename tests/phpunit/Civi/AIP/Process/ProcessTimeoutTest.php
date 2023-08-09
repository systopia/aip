<?php

namespace Civi\AIP;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test process timeouts
 *
 * @group headless
 *
 */
class ProcessSpecsTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Test the timeouts
   */
  public function testTimeout()
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
    $process->setConfigValue('processing_limit/processing_time', "1 sec");
    $process->setConfigValue('processing_limit/php_process_time', "2 sec");

    // run the process
    $process->run();
  }
}
