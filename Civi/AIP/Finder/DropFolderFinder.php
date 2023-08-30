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

use CRM_Aip_ExtensionUtil   as E;

/**
 * A FINDER that monitors a file drop folder for input
 *
 * It has the following configuration options:
 *  filter/file_name  - regular expression to filter for file name
 *  folder/inbox      - folder in which this module will look for new files to process (with r/w permissions)
 *  folder/processing - folder in which this module will temporarily keep files while processing (with r/w permissions)
 *  folder/processed  - folder in which this module will store files after processing (with r/w permissions)
 *  folder/failed     - folder in which this module will keep files after processing failed (with r/w permissions)
 *  folder/uploading - folder for processed to upload files into, before mv'ing them to the inbox (r/w permissions)
 **/
class DropFolderFinder extends Base
{
  /**
   * Check if the component is ready,
   *   i.e. configured correctly.
   *
   * @throws \Exception
   *   an exception will be thrown if something's wrong with the
   *     configuration or state
   */
  public function verifyConfiguration()
  {
    // check if all the folders are there
    $all_folder_paths = [];
    foreach (['folder/uploading', 'folder/inbox', 'folder/processing', 'folder/processed', 'folder/failed'] as $folder_setting) {
      $folder_path = $this->getConfigValue($folder_setting);

      // folders have to be set
      if (empty($folder_path)) {
        throw new \Exception(E::ts("Folder '%1' is not configured.", [1 => $folder_path]));
      }

      // folders have to be different
      if (in_array($folder_path, $all_folder_paths)) {
        throw new \Exception(E::ts("Folder '%1' is used for multiple stages.", [1 => $folder_path]));
      }

      // folders have to be readable
      if (!is_readable($folder_path)) {
        throw new \Exception(E::ts("Folder '%1' is not readable.", [1 => $folder_path]));
      }

      // folders have to be writable
      if (!is_writable($folder_path)) {
        throw new \Exception(E::ts("Folder '%1' is not writable.", [1 => $folder_path]));
      }

      // folders have to be writable
      if (!is_dir($folder_path)) {
        throw new \Exception(E::ts("Folder '%1' is not a folder.", [1 => $folder_path]));
      }
    }

    // looks good.
  }

  public function getTypeName() : string
  {
    return E::ts("Dropbox File Finder");
  }


  /**
   * See if there is a new file in the dropbox
   *
   * @return ?string
   */
  public function findNextSource(): ?string
  {
    $file_name_filter = $this->getConfigValue('filter/file_name');
    $inbox_folder = $this->getConfigValue('folder/inbox');
    $files = scandir($inbox_folder, SCANDIR_SORT_ASCENDING);

    // check if that worked
    if (!is_array($files)) {
      throw new \Exception("Cannot list files in inbox folder '{$inbox_folder}'");
    }

    // find the files
    foreach ($files as $file) {
      // exclude directory links
      if (in_array($file, [".", ".."])) {
        continue;
      }

      // create full file path
      $file_path = $inbox_folder . DIRECTORY_SEPARATOR . $file;

      // only investigate files we can access
      $this->log(E::ts("Investigating file '%1'...", [1 => $file_path]));
      if (empty($file_name_filter) || preg_match($file_name_filter, $file_path)) {
        // this could be a file for us...
        if (is_file($file_path) && is_readable($file_path)) {
          return $file_path;
        } else {
          $this->log(E::ts("File %1 could not be read.", [1 => $file_path]), 'warning');
        }
      } else {
        $this->log(E::ts("File '%1' doesn't match the filter '%2'", [1 => $file_path, 2 => $file_name_filter]));
      }
    }
    return null;
  }

  /**
   * This function claims the source file by moving it to the 'processing' folder
   *
   * @param string $file_path
   *   this should be the file path
   *
   * @return string $uri
   *    the resulting URI (likely the same)
   */
  public function claimSource(string $file_path)
  {
    // todo: check if this path makes sense, and is in the inbox folder

    // claiming the source means moving it to the
    $processing_folder = $this->getConfigValue('folder/processing');
    $target_file_name = date('YmdHis') . '_' . bin2hex(random_bytes(10)) . '_' . basename($file_path);
    $target_file = $processing_folder . DIRECTORY_SEPARATOR .  $target_file_name;

    if (rename($file_path, $target_file)) {
      $this->log(E::ts("Moved file from %1 to %2 for processing.,", [1 => $file_path, 2 => $target_file]), 'info');
      return $target_file;
    } else {
      throw new \Exception(E::ts("Couldn't claim source '%1'", [1 => $file_path]));
    }
  }

  /**
   * This function marks the resource as processed by moving it into the respective folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function markSourceProcessed(string $file_path)
  {
    // mark the source means moving it to the processed folder
    $processed_folder = $this->getConfigValue('folder/processed');
    $target_file = $processed_folder . DIRECTORY_SEPARATOR . basename($file_path);

    if (rename($file_path, $target_file)) {
      $this->log(E::ts("Moved file from %1 to %2 to mark as processed.,", [1 => $file_path, 2 => $target_file]), 'info');
      return true;
    } else {
      throw new \Exception(E::ts("Couldn't mark source '%1' as processed.", [1 => $file_path]));
    }
  }

  /**
   * This function marks the resource as processed by moving it into the respective folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function markSourceFailed(string $file_path)
  {
    // mark the source means moving it to the processed folder
    $processed_folder = $this->getConfigValue('folder/failed');
    $target_file = $processed_folder . DIRECTORY_SEPARATOR . basename($file_path);

    if (rename($file_path, $target_file)) {
      $this->log(E::ts("Moved file from %1 to %2 to mark as FAILED.,", [1 => $file_path, 2 => $target_file]), 'info');
      return true;
    } else {
      throw new \Exception(E::ts("Couldn't mark source '%1' as FAILED.", [1 => $file_path]));
    }

    // reset the state
    $this->resetState();
  }
}