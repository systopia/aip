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
use CRM_Aip_ExtensionUtil as E;
use \Civi as Civi;

class Api3 extends Base
{
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
    // we're going to do three steps:
    // 1) map the parameters
    $call_parameters = $record;
    $call_parameters = $this->mapCallParameters($call_parameters);

    // 2) filter and prepare the parameters
    $call_parameters = $this->filterCallParameters($call_parameters);
    $call_parameters = $this->trimCallParameters($call_parameters);

    // 3) compile the API parameters
    $entity = $this->getConfigValue('api_entity');
    $action = $this->getConfigValue('api_action');
    $hardcoded_values = (array) $this->getConfigValue('api_values');
    $call_parameters = array_merge($call_parameters, $hardcoded_values);
    $call_hash = sha1(json_encode($call_parameters));

    // 4) Run the API call
    $this->log("Call API {$entity}.{$action} with parameters hash {$call_hash}", 'debug');
    $result = \civicrm_api3($entity, $action, $call_parameters);
    if($this->getConfigValue('log/apicall') == '1') {
      $this->log("Call API {$entity}.{$action} with parameters: ".var_export($call_parameters,true), 'debug');
      $this->log("Call API {$entity}.{$action} response: ".var_export($result,true), 'debug');
    }

    parent::processRecord($record);
  }

  /**
   * Filter the input parameters down to the ones
   *
   * @param array $parameters
   *   the current call parameters
   *
   * @return array
   */
  protected function filterCallParameters(array $parameters) : array
  {
    // restrict record to allowed parameters
    $positive_parameter_list = $this->getConfigValue('positive_parameter_list');
    if (is_array($positive_parameter_list)) {
      foreach ($parameters as $field_name => $field_value) {
        if (!in_array($field_name, $positive_parameter_list)) {
          unset($parameters[$field_name]);
        }
      }
    }

    // remove disallowed parameters
    $negative_parameter_list = $this->getConfigValue('negative_parameter_list');
    if (is_array($negative_parameter_list)) {
      foreach ($negative_parameter_list as $field_name) {
        unset($parameters[$field_name]);
      }
    }

    return $parameters;
  }

  /**
   * Filter the input parameters down to the ones
   *
   * @param array $parameters
   *   the current call parameters
   *
   * @return array
   */
  protected function mapCallParameters(array $parameters) : array
  {
    // restrict record to allowed parameters
    $parameter_mapping = $this->getConfigValue('parameter_mapping');
    if (is_array($parameter_mapping)) {
      foreach ($parameter_mapping as $old_field_name => $new_field_name) {
        if ($old_field_name == $new_field_name) continue;

        if (!is_null($this->getArrayValue($parameters,$old_field_name))) {
          $parameters[$new_field_name] = $this->getArrayValue($parameters,$old_field_name);
          unset($parameters[$old_field_name]);
        }
      }
    }

    // remove disallowed parameters
    $negative_parameter_list = $this->getConfigValue('negative_parameter_list');
    if (is_array($negative_parameter_list)) {
      foreach ($negative_parameter_list as $field_name) {
        unset($parameters[$field_name]);
      }
    }

    return $parameters;
  }

  /**
   * Trim the input parameters - either all of them, or selected columns
   *
   * @param array $parameters
   *   the current call parameters
   *
   * @return array
   */
  protected function trimCallParameters(array $parameters) : array
  {
    // trim/truncate parameters
    $parameter_trimming = $this->getConfigValue('trim_parameters');
    if ($parameter_trimming == 'all') {
      foreach ($parameters as $key => &$value) {
        $value = trim($value);
      }
    } elseif (is_array($parameter_trimming)) {
      foreach ($parameter_trimming as $key) {
        if (isset($parameters[$key])) {
          $parameters[$key] = trim($parameters[$key]);
        }
      }
    }
    return $parameters;
  }

  /**
   * Return the type of the given component
   *
   * @return string
   */
  public function getTypeName() : string
  {
    return E::ts("APIv3 Processor");
  }
}