<?php

namespace Civi\AIP;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\CiviEnvBuilder;

/**
 * Basic complete setup & run tests
 *
 * @group headless
 *
 */
class BasicTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple process (UrlRequestFile, CSV reader, TestProcessor) and runs it
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
}
