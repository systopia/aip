<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from aip/xml/schema/CRM/Aip/ErrorLog.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:84d44986a19242cc9dcb97efc989f06e)
 */
use CRM_Aip_ExtensionUtil as E;

/**
 * Database access object for the ErrorLog entity.
 */
class CRM_Aip_DAO_ErrorLog extends CRM_Core_DAO {
  const EXT = E::LONG_NAME;
  const TABLE_ADDED = '';

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_aip_error_log';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = FALSE;

  /**
   * Process ID
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $id;

  /**
   * FK to AIP Process
   *
   * @var int|string|null
   *   (SQL type: int unsigned)
   *   Note that values will be retrieved from the database as a string.
   */
  public $process_id;

  /**
   * Timestamp when the error was recorded
   *
   * @var string|null
   *   (SQL type: datetime)
   *   Note that values will be retrieved from the database as a string.
   */
  public $error_timestamp;

  /**
   * The error message that was recorded with the processing failure
   *
   * @var string|null
   *   (SQL type: text)
   *   Note that values will be retrieved from the database as a string.
   */
  public $error_message;

  /**
   * Data set (record) to be processed
   *
   * @var string|null
   *   (SQL type: text)
   *   Note that values will be retrieved from the database as a string.
   */
  public $data;

  /**
   * Is this process enabled for scheduled runs?
   *
   * @var bool|string|null
   *   (SQL type: tinyint)
   *   Note that values will be retrieved from the database as a string.
   */
  public $is_resolved;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_aip_error_log';
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? E::ts('Error Logs') : E::ts('Error Log');
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'aip_error_log_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('Process ID'),
          'required' => TRUE,
          'where' => 'civicrm_aip_error_log.id',
          'table_name' => 'civicrm_aip_error_log',
          'entity' => 'ErrorLog',
          'bao' => 'CRM_Aip_DAO_ErrorLog',
          'localizable' => 0,
          'readonly' => TRUE,
          'add' => NULL,
        ],
        'aip_error_log_process_id' => [
          'name' => 'process_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => E::ts('FK to AIP Process'),
          'where' => 'civicrm_aip_error_log.process_id',
          'table_name' => 'civicrm_aip_error_log',
          'entity' => 'ErrorLog',
          'bao' => 'CRM_Aip_DAO_ErrorLog',
          'localizable' => 0,
          'html' => [
            'type' => 'EntityRef',
          ],
          'pseudoconstant' => [
            'table' => 'civicrm_aip_process',
            'keyColumn' => 'id',
            'labelColumn' => 'name',
            'prefetch' => 'false',
          ],
          'add' => NULL,
        ],
        'aip_error_log_error_timestamp' => [
          'name' => 'error_timestamp',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Last execution of this process'),
          'description' => E::ts('Timestamp when the error was recorded'),
          'import' => TRUE,
          'where' => 'civicrm_aip_error_log.error_timestamp',
          'export' => TRUE,
          'table_name' => 'civicrm_aip_error_log',
          'entity' => 'ErrorLog',
          'bao' => 'CRM_Aip_DAO_ErrorLog',
          'localizable' => 0,
          'html' => [
            'type' => 'Select Date',
            'formatType' => 'activityDateTime',
          ],
          'add' => NULL,
        ],
        'aip_error_log_error_message' => [
          'name' => 'error_message',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Error Message'),
          'description' => E::ts('The error message that was recorded with the processing failure'),
          'where' => 'civicrm_aip_error_log.error_message',
          'table_name' => 'civicrm_aip_error_log',
          'entity' => 'ErrorLog',
          'bao' => 'CRM_Aip_DAO_ErrorLog',
          'localizable' => 1,
          'add' => NULL,
        ],
        'aip_error_log_error_data' => [
          'name' => 'data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Data/Record'),
          'description' => E::ts('Data set (record) to be processed'),
          'where' => 'civicrm_aip_error_log.data',
          'table_name' => 'civicrm_aip_error_log',
          'entity' => 'ErrorLog',
          'bao' => 'CRM_Aip_DAO_ErrorLog',
          'localizable' => 1,
          'serialize' => self::SERIALIZE_JSON,
          'add' => NULL,
        ],
        'aip_error_log_is_resolved' => [
          'name' => 'is_resolved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => E::ts('Is Resolved'),
          'description' => E::ts('Is this process enabled for scheduled runs?'),
          'where' => 'civicrm_aip_error_log.is_resolved',
          'default' => '0',
          'table_name' => 'civicrm_aip_error_log',
          'entity' => 'ErrorLog',
          'bao' => 'CRM_Aip_DAO_ErrorLog',
          'localizable' => 0,
          'html' => [
            'type' => 'CheckBox',
          ],
          'add' => NULL,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'aip_error_log', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'aip_error_log', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
