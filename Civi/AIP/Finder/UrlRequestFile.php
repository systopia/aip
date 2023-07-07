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

namespace Civi\AIP\Finder;

/**
 * This finder will get a file URL from the $_REQUEST['aip_input']
 *
 * BE *VERY* careful with this, it could be used to leak local information
 **/
class UrlRequestFile extends AbstractSource
{
  public function findNextSource()
  {
    $potential_file_path = $_REQUEST['aip_input'];
    $this->log("Received file path '{$potential_file_path}, investigating");
    if (isset($potential_file_path)) {
      // check if the file exists
      if (file_exists($potential_file_path) && is_readable($potential_file_path)) {
        return $potential_file_path;
      }
    }
  }

  /**
   * Set the aip_input parameter in the current request object.
   *
   * This is for debugging / testing only
   *
   * @param string $local_file
   *    the local file that should be tested
   * @return void
   */
  public function setFile(string $local_file)
  {
    $_REQUEST['aip_input'] = $local_file;
  }
}