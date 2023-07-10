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

namespace Civi\AIP\Processor;

use Civi\AIP\AbstractComponent;

class Base extends AbstractComponent
{
  public function __construct()
  {

  }

  /**
   *
   * @return void
   */
  /**
   * Process the given record
   *
   * @param array $record
   *
   * @throws \Exception
   */
  public function processRecord($record)
  {
    // do nothing here, override in implementation
  }
}