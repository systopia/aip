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

/**
 * Generic infrastructure for component
 **/
class AbstractComponent
{
  /**
   * @var array $configuration
   *   this component's configuration
   */
  protected array $configuration = [];

  /**
   * @var array $state
   *   this component's current state
   */
  protected array $state = [];

  /**
   * Get the component's configuration
   *
   * @return array
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }

  /**
   * Set the component's configuration, e.g. when instantiated
   *
   * @param $configuration array
   *   the given configuration
   */
  public function setConfiguration($configuration)
  {
    $this->configuration = $configuration;
  }

  /**
   * Get the link to form with components
   *
   * @return null|string
   *   URL to the configuration editor or NULL if no configuration available
   */
  public function getConfigurationEditorURL()
  {
    return null;
  }

  /**
   * Log messages
   *
   * @param string $message
   *   the log message
   *
   * @param string $level
   *   log level, one of debug, info, warning
   *
   * @return void
   */
  public function log($message, $level = 'debug')
  {
    switch ($level) {
      case 'info':
        \Civi::log()->info($message);
        break;

      case 'warning':
        \Civi::log()->warning($message);
        break;

      default:
      case 'debug':
        \Civi::log()->debug($message);
        break;
    }
  }
}