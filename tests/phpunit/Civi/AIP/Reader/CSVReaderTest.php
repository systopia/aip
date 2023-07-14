<?php

namespace Civi\AIP;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

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
class CSVReaderTest extends TestBase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  /**
   * Create a simple processor (UrlRequestFile, CSV reader, TestProcessor)
   */
  public function testReadSimpleCSV()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));

    // create reader
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'UTF-8']);

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
   * Create a simple processor (UrlRequestFile, CSV reader, TestProcessor),
   * But then process one record, suspend,
   *      revive, process the remaining record
   */
  public function testReadWithStopAndRestore()
  {
    // create finder
    $finder = new Finder\UrlRequestFile();
    $finder->setFile($this->getTestResourcePath('input/CSV/Test01.csv'));

    // create reader
    $reader = new Reader\CSV();
    $reader->setConfiguration(['csv_string_encoding' => 'ISO-8859-15']);

    // create processor
    $processor = new Processor\TestProcessor();

    // create a process
    $process = new Process($finder, $reader, $processor);
    $process->setConfigValue('processing_limit/record_count', 1);

    // run the process
    $process->run();
    $last_processed_record = $process->getProcessor()->getLastProcessedRecord();
    $this->assertEquals("25120510", reset($last_processed_record), "This should've read the first record of the file");

    // check results
    $this->assertEquals(1, $reader->getSessionProcessedRecordCount(), "This should've processed the only one record because of the processing_limit/record_count = 1 limit.");
    $this->assertEquals(0, $reader->getFailedRecordCount());

    // revive the process
    $process2 = Process::restore($process->getID());
    $process2->getReader()->setConfigValue('processing_limit/record_count', 1);

    // run the process
    $process2->run();

    // check results
    $processor = $process2->getProcessor();
    $last_processed_record = $process2->getProcessor()->getLastProcessedRecord();
    $this->assertEquals("25120511", $last_processed_record[0], "This should've read the second record of the file");
    $this->assertEquals(1, $process2->getReader()->getSessionProcessedRecordCount(), "This should've processed the only one record because of the processing_limit/record_count = 1 limit.");
    $this->assertEquals(0, $process2->getReader()->getFailedRecordCount());
    // todo: check if the second record was checked

  }
}
