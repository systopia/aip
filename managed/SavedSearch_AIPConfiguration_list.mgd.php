<?php

use CRM_Aip_ExtensionUtil as E;

return [
    [
        'name' => 'SavedSearch_AIP_Configuration',
        'entity' => 'SavedSearch',
        'cleanup' => 'always',
        'update' => 'unmodified',
        'params' => [
            'version' => 4,
            'values' => [
                'name' => 'AIP_Configuration',
                'label' => E::ts('AIP Configuration'),
                'api_entity' => 'AipProcess',
                'api_params' => [
                    'version' => 4,
                    'select' => [
                        'id',
                        'name',
                        'is_active',
                        'last_run',
                        'config',
                        'state',
                        'class',
                        'documentation',
                    ],
                    'orderBy' => [],
                    'where' => [],
                    'groupBy' => [],
                    'join' => [],
                    'having' => [],
                ],
            ],
            'match' => [
                'name',
            ],
        ],
    ],
    [
        'name' => 'SavedSearch_AIP_Configuration_SearchDisplay_AIP_Configuration_Table',
        'entity' => 'SearchDisplay',
        'cleanup' => 'always',
        'update' => 'unmodified',
        'params' => [
            'version' => 4,
            'values' => [
                'name' => 'AIP_Configuration_Table',
                'label' => E::ts('AIP Configuration Table'),
                'saved_search_id.name' => 'AIP_Configuration',
                'type' => 'table',
                'settings' => [
                    'description' => NULL,
                    'sort' => [],
                    'limit' => 50,
                    'pager' => [],
                    'placeholder' => 5,
                    'columns' => [
                        [
                            'type' => 'field',
                            'key' => 'id',
                            'dataType' => 'Integer',
                            'label' => E::ts('ID'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'field',
                            'key' => 'name',
                            'dataType' => 'String',
                            'label' => E::ts('Name'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'field',
                            'key' => 'is_active',
                            'dataType' => 'Boolean',
                            'label' => E::ts('is active'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'field',
                            'key' => 'last_run',
                            'dataType' => 'Timestamp',
                            'label' => E::ts('Last execution of this process'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'field',
                            'key' => 'config',
                            'dataType' => 'Text',
                            'label' => E::ts('Process Configuration'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'field',
                            'key' => 'state',
                            'dataType' => 'Text',
                            'label' => E::ts('Process State'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'field',
                            'key' => 'class',
                            'dataType' => 'String',
                            'label' => E::ts('Implementation Class Name'),
                            'sortable' => TRUE,
                        ],
                        [
                            'type' => 'html',
                            'key' => 'documentation',
                            'dataType' => 'Text',
                            'label' => E::ts('Process Documentation'),
                            'sortable' => TRUE,
                        ],
                        [
                            'size' => 'btn-xs',
                            'links' => [
                                [
                                    'path' => 'civicrm/aip_configuration_update#?AipProcess1=[id]',
                                    'icon' => 'fa-external-link',
                                    'text' => E::ts('update'),
                                    'style' => 'default',
                                    'condition' => [],
                                    'task' => '',
                                    'entity' => '',
                                    'action' => '',
                                    'join' => '',
                                    'target' => '',
                                ],
                            ],
                            'type' => 'buttons',
                            'alignment' => 'text-right',
                        ],
                    ],
                    'actions' => TRUE,
                    'classes' => [
                        'table',
                        'table-striped',
                    ],
                    'toolbar' => [
                        [
                            'path' => 'civicrm/aip_configuration_update',
                            'icon' => 'fa-external-link',
                            'text' => E::ts('Add'),
                            'style' => 'default',
                            'condition' => [],
                            'task' => '',
                            'entity' => '',
                            'action' => '',
                            'join' => '',
                            'target' => '',
                        ],
                    ],
                ],
            ],
            'match' => [
                'name',
                'saved_search_id',
            ],
        ],
    ],
];