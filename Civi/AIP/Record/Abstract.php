<?php
/**
 * -------------------------------------------------------+
 * | SYSTOPIA Automatic Input Processing (AIP) Framework    |
 * | Copyright (C) 2023 SYSTOPIA                            |
 * | Author: B. Endres (endres@systopia.de)                 |
 * +--------------------------------------------------------+
 * | This program is released as free software under the    |
 * | Affero GPL license. You can redistribute it and/or     |
 * | modify it under the terms of this license which you    |
 * | can read by viewing the included agpl.txt or online    |
 * | at www.gnu.org/licenses/agpl.html. Removal of this     |
 * | copyright header is strictly prohibited without        |
 * | written permission from the original author(s).        |
 * +--------------------------------------------------------
 */

declare(strict_types = 1);

abstract class AbstractRecord {

  /**
   * Get a list of field names
   *
   * @return array
   */
  abstract public function getFields() : array;

  /**
   * Get the full record as an array
   *
   * @return array
   */
  abstract public function asArray() : array;

  /**
   * Get the value of the given field in this record
   *
   * @return ?string
   *   field value
   */
  abstract public function getValue(string $field_name): ?string;

}
