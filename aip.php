<?php
/*
 * Copyright (C) 2026 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'aip.civix.php';
// phpcs:enable

use CRM_Aip_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function aip_civicrm_config(&$config): void {
  _aip_civix_civicrm_config($config);

  // register lock
  \Civi::lockManager()->register('/^aip-[0-9]+$/', ['CRM_Core_Lock', 'createScopedLock']);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function aip_civicrm_install(): void {
  _aip_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function aip_civicrm_enable(): void {
  _aip_civix_civicrm_enable();
}

function aip_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_Aip_DAO_AipErrorLog'] = [
    'name' => 'AipErrorLog',
    'class' => 'CRM_Aip_DAO_AipErrorLog',
    'table' => 'civicrm_aip_error_log',
  ];
  $entityTypes['CRM_Aip_DAO_AipProcess'] = [
    'name' => 'AipProcess',
    'class' => 'CRM_Aip_DAO_AipProcess',
    'table' => 'civicrm_aip_process',
  ];
}
