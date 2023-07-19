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

use CRM_Aip_ExtensionUtil as E;

/**
 * Generic infrastructure for component
 **/
abstract class AbstractComponent
{
  /**
   * @var Process the process this component belongs to
   */
  protected Process $process;

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

  /** @var array file_name => handle  */
  protected static $log_files = [];

  public function __construct() {
  }

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
    // by default, we're ready :)
  }

  /**
   * Get config option
   *
   * @param string $path
   *   a variable name, or a '/' separated path to it
   *
   * @return mixed
   */
  public function getConfigValue(string $path, $default = null)
  {
    $value = $this->getArrayValue($this->configuration, $path);
    return $value ?? $default;
  }

  /**
   * Get a value from the component's state
   *
   * @param string $path
   *   a variable name, or a '/' separated path to it
   *
   * @return mixed
   */
  public function getStateValue(string $path, $default = null)
  {
    $value = $this->getArrayValue($this->state, $path);
    return $value ?? $default;
  }

  /**
   * Set a value in the component's state
   *
   * @param string $path
   *   a variable name, or a '/' separated path to it
   *
   * @param mixed $value
   *   the new value
   *
   * @return mixed
   *   the previous value
   */
  public function setStateValue(string $path, $value)
  {
    return $this->setArrayValue($this->state, $path, $value);
  }

  /**
   * Set a value in the component's configuration
   *
   * @param string $path
   *   a variable name, or a '/' separated path to it
   *
   * @param mixed $value
   *   the new value
   *
   * @return mixed
   *   the previous value
   */
  public function setConfigValue(string $path, $value)
  {
    return $this->setArrayValue($this->configuration, $path, $value);
  }


  /**
   * Reset the state of this module
   *
   * @return void
   */
  public function resetState()
  {
    // anything? $this->state = [];?
  }

  /**
   * Get the process this component belongs to
   *
   * @return Process
   */
  public function getProcess()
  {
    return $this->process;
  }

  /**
   * Get value from an recursive array with the given path
   *
   * @param array $array
   *   the vault
   *
   * @param string $path
   *   a variable name, or a '/' separated path to it
   *
   * @return mixed
   */
  public function getArrayValue(array $array, string $path)
  {
    // look for the value in the path
    $path = explode('/', $path);
    foreach ($path as $index => $key) {
      $array = $array[$key] ?? null;
      if ($index == (count($path) - 1)) {
        return $array;
      }
    }

    return null;
  }

  /**
   * Set value from an recursive array with the given path
   *
   * @param array $array
   *   the vault
   *
   * @param string $path
   *   a variable name, or a '/' separated path to it
   *
   * @return mixed
   *   the previously used value
   */
  public function setArrayValue(array &$array, string $path, $value)
  {
    // get the current value
    $previous_value = $this->getArrayValue($array, $path);

    // iterate through the path
    $path = explode('/', $path);
    foreach ($path as $index => $key) {
      if ($index == (count($path) - 1)) {
        // this is the element we're looking for
        $array[$key] = $value;
        break;

      } else {
        if (!isset($array[$key])) {
          $array[$key] = [];
        }
        $array = &$array[$key];
      }
    }

    return $previous_value;
  }

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
   * Get the component's type
   *
   * @return string
   */
  public abstract function getTypeName();

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
   *   log level, one of debug, info, warning, error
   *
   * @return void
   */
  public function log($message, $log_level = 'debug')
  {
    // find out if we should log this.
    $min_log_level = strtolower($this->getConfigValue('log/level', 'debug'));

    // todo: add timestamp and process ID to log
    // todo: harmonise logging
    switch ($log_level) {
      case 'debug':
        $this->writeLogMessage($message, $log_level);
        break;

      default:
      case 'info':
        if (in_array($min_log_level, ['info', 'warning', 'error'])) {
          $this->writeLogMessage($message, $log_level);
        }
        break;

      case 'warning':
        if (in_array($min_log_level, ['warning', 'error'])) {
          $this->writeLogMessage($message, $log_level);
        }
        break;

      case 'error':
        if (in_array($min_log_level, ['error'])) {
          $this->writeLogMessage($message, $log_level);
        }
        break;
    }
  }

  /**
   * Write a log message to the given log sink
   *
   * @param string $message
   *    the log message
   *
   * @param $log_level
   *    the log level
   *
   * @return void
   */
  protected function writeLogMessage(string $message, $log_level)
  {
    $log_file = $this->getConfigValue('log/file');
    if (empty($log_file)) {
      // log to CiviCRM standard log
      switch ($log_level) {
        case 'debug':
          \Civi::log("AIP")->debug($message);
          break;

        default:
        case 'info':
          \Civi::log("AIP")->info($message);
          break;

        case 'warning':
          \Civi::log("AIP")->warning($message);
          break;

        case 'error':
          \Civi::log("AIP")->error($message);
          break;
      }

    } else {
      // log to separate log file
      if (!isset(AbstractComponent::$log_files[$log_file])) {
        AbstractComponent::$log_files[$log_file] = fopen($log_file, "a");
      }

      $log_file_handle = AbstractComponent::$log_files[$log_file];
      fwrite($log_file_handle, date('[Y-m-d H:i:s]'));
      $process_id = $this->getProcess()->getID();
      if ($process_id) {
        fwrite($log_file_handle, "[P{$process_id}]");
      }
      fwrite($log_file_handle, ' ');
      fwrite($log_file_handle, $message);
      fwrite($log_file_handle, "\n");
    }
  }

  /**
   * Log messages to the CiviCRM default log
   *
   * @param string $message
   *   the log message
   *
   * @param string $log_level
   *   log level, one of debug, info, warning, error
   *
   * @return void
   */
  protected function logToCiviLog($message, $log_level = 'debug')
  {
    $max_log_level = $this->getConfigValue('log/level', 'debug');

    // todo: add timestamp and process ID to log
    // todo: harmonise logging
    switch ($log_level) {
      case 'info':
        if (in_array($max_log_level, ['debug', 'info'])) {
          \Civi::log()->info($message);
        }
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

  /**
   * Raise an exception
   *
   * @param $message
   *   message
   *
   * @throws \Exception
   *   the requested exception
   *
   * @return void
   */
  public function raiseException($message)
  {
    throw new \Exception(E::ts("[%1(:%2)] %3", [
            1 => $this->getTypeName(),
            2 => $this->getProcess()->getID(),
            3 => $message]));
  }

  /**
   * Serialise state
   *
   * @return array
   *   serialised state
   */
  public function serialise() : array
  {
    return [
        'class_name' => get_class($this),
        'config'     => $this->configuration,
        'state'      => $this->state
    ];
  }
}