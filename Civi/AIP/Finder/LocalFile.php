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
 * A FINDER is used to identify new files to process
 **/
class LocalFile extends AbstractFile
{
  /** @var string $local_folder
   *     the folder to look in for files
   */
  protected string $local_folder;

  /** @var $file_pattern string
   *     regex pattern of the file to be looking for
   */
  protected string $file_pattern;

  public function getNextReader()
  {

  }
}