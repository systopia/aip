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
   * Test the 'set' action.
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
