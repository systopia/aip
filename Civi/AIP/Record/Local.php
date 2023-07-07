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

class Local extends AbstractRecord
{
  /** @var array data set */
  protected array $data;

  /**
   * Create a local data record
   *
   * @param $data
   *  an array with a key -> value data
   */
  public function __construct($data) {
    // todo: verify?
    $this->data = $data;
  }

  /**
   * Get a list of field names
   *
   * @return array
   */  public function getFields() : array
  {
    return array_keys($this->data);
  }

  /**
   * Get the full record as an array
   *
   * @return array
   */
  public function asArray() : array
  {
    return $this->data;
  }

  /**
   * Get the value of the given field in this record
   *
   * @return ?string
   *   field value
   */
  public function getValue(string $field_name) : ?string
  {
    return $this->data[$field_name] ?? null;
  }
}