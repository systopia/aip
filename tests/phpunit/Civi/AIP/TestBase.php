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

use CRM_Aip_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\CiviEnvBuilder;
use \PHPUnit\Framework\TestCase as TestCase;

/**
 * Base class for all CiviBanking tests
 *
 * @group headless
 */
class TestBase extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface
{
  use \Civi\Test\Api3TestTrait {
    callAPISuccess as protected traitCallAPISuccess;
  }

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder
  {
    $this->setUp();

    // make sure the table is there??
    //\CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, 'sql/auto_install.sql');

    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void
  {
    parent::setUp();
  }

  public function tearDown(): void
  {
    parent::tearDown();
  }

  /**
   * Get the full path of a test resource
   *
   * @param string $internal_path
   *   the internal path
   *
   * @return string
   *   the full path
   */
  public function getTestResourcePath($internal_path)
  {
    $importer_spec = 'tests/resources/' . $internal_path;
    $full_path     = E::path($importer_spec);
    $this->assertTrue(file_exists($full_path), "Test resource '{$internal_path}' not found.");
    $this->assertTrue(is_readable($full_path), "Test resource '{$internal_path}' cannot be opened.");
    return $full_path;
  }

}
