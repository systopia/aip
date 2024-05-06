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

use CRM_Aip_ExtensionUtil as E;
use PHPUnit\Exception;

/**
 * A FINDER that monitors a fixed file (online or local) for changes
 *
 * It has the following configuration options:
 *  url            - url of the file
 *  detect_changes - discard the source file if it has already been processed
 *                     (using checksum)
 **/
class StaticUrlFileFinder extends Base
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
    // looks good.
  }

  public function getTypeName() : string
  {
    return E::ts("File Finder");
  }

  /**
   * See if there is a new file in the dropbox
   *
   * @return ?string
   */
  public function findNextSource(): ?string
  {
    $file_url = $this->getConfigValue('url');
    if (empty($file_url)) {
      throw new \Exception("No 'url' set");
    }

    // try to read the file
    try {
      // todo: check if this can process URLs, including credentials
      $data = file_get_contents($file_url);
      $data_checksum = hash('sha256', $data);

      // check if source has changed
      $detect_changes = $this->getConfigValue('detect_changes');
      if ($detect_changes) {
        $previously_processed_checksum = $this->getStateValue('previous_file_checksum');
        if ($data_checksum && $data_checksum == $previously_processed_checksum) {
          $this->log("The source '{$file_url}' had already been processed");
          return null;
        }
      }

      // first: create a local temp file
      $local_file = $this->getStateValue('local_copy');
      if (empty($local_file)) {
        $local_file = tempnam(sys_get_temp_dir(), "aip-" . $this->getProcess()->getID() . '-local-');
        $this->setStateValue('local_copy', $local_file);
      }
      file_put_contents($local_file, $data);
      $this->setStateValue('previous_file_checksum', $data_checksum);

      return $local_file;

    } catch (Exception $ex) {
      $this->log('Error encountered: ' . $ex->getMessage(), 'warn');
      return null;
    }
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
    // nothing to do here
    return $file_path;
  }

  /**
   * This function marks the resource as processed by moving it into the respective folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function markSourceProcessed(string $file_path)
  {
    // nothing to do here
    $this->removeLocalFileCopy();
    return true;
  }

  /**
   * This function marks the resource as processed by moving it into the respective folder
   *
   * @param string $file_path
   *   this should be the file path
   */
  public function markSourceFailed(string $file_path)
  {
    // nothing to do here
    $this->removeLocalFileCopy();
    return true;
  }

  /**
   * Make sure that any local copy of the file is deleted
   * @return void
   */
  public function removeLocalFileCopy()
  {
    $local_copy = $this->getStateValue('local_copy');
    if ($local_copy) {
      $this->setStateValue('local_copy', null);
      if (file_exists($local_copy)) {
        $this->log("Removed local file copy '{$local_copy}'.");
        unlink($local_copy);
      }
    }
  }

}