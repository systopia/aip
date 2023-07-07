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

/**
 * A FINDER is used to identify new data sources to process
 **/
abstract class Base extends AbstractComponent
{
  /**
   * Ask the finder to find the next data source
   *
   * @return string URI
   *   an URI for the following reader to process
   */
  public abstract function findNextSource() : string;

  /**
   * Mark the given resource as 'processing',
   *  so it can be exclusively processed
   *
   * @param string $uri
   *   an URI to marked busy/processing
   */
  public abstract function markSourceProcessing(string $uri);

  /**
   * Mark the given resource as 'processing',
   *  so it can be exclusively processed
   *
   * @param string $uri
   *   an URI to marked busy/processing
   */
  public abstract function markSourceProcessed(string $uri);

  /**
   * Mark the given resource as 'processing',
   *  so it can be exclusively processed
   *
   * @param string $uri
   *   an URI to marked busy/processing
   *
   * @return bool
   *   true, if this source can be handled by this Finder
   */
  public abstract function canHandleSource(string $uri);

}