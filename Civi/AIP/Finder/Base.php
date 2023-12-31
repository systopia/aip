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

use Civi\AIP\AbstractComponent;
use CRM_Aip_ExtensionUtil as E;

/**
 * A FINDER is used to identify new data sources to process
 **/
abstract class Base extends AbstractComponent
{
  /**
   * Ask the finder to find the next data source
   *
   * @return ?string URI
   *   an URI for the following reader to process
   */
  public abstract function findNextSource() : ?string;

  /**
   * Claim this resource for this process,
   *  so it can be exclusively processed
   *
   * @param string $uri
   *   an URI to marked busy/processing
   *
   * @return string $uri
   *   the resulting URI (likely the same)
   */
  public abstract function claimSource(string $uri);

  /**
   * Mark the given resource as 'processing',
   *  so it can be exclusively processed
   *
   * @param string $uri
   *   an URI to marked busy/processing
   */
  public abstract function markSourceProcessed(string $uri);

  /**
   * Mark the given resource as failed
   *
   * @param string $uri
   *   an URI to marked as FAILED
   */
  public abstract function markSourceFailed(string $uri);

  /**
   * Return the type of the given component
   *
   * @return string
   */
  public function getTypeName() : string
  {
    return E::ts("Finder");
  }
}