<?php

use CRM_Aip_ExtensionUtil as E;

return [
    [
        'name' => 'SavedSearch_AIP_Error_Logs',
        'entity' => 'SavedSearch',
        'cleanup' => 'always',
        'update' => 'unmodified',
        'params' => [
            'version' => 4,
            'values' => [
                'name' => 'AIP_Error_Logs',
                'label' => E::ts('AIP Error Logs'),
                'api_entity' => 'AipErrorLog',
                'api_params' => [
                    'version' => 4,
                    'select' => [
                        'process_id:label',
                        'data',
                        'error_message',
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
];