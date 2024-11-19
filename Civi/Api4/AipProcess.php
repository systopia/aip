<?php
namespace Civi\Api4;

/**
 * AipProcess entity.
 *
 * Provided by the Automated Input Processing extension.
 *
 * @package Civi\Api4
 */
class AipProcess extends Generic\DAOEntity {

  public static function permissions() {
    return [
            'meta' => ['access CiviCRM'],
            'default' => ['administer CiviCRM'],
            'create' => ['administer CiviCRM'],
            'run' => ['administer CiviCRM'],
    ];
  }
}
