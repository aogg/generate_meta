<?php


return [
    'host' => 'localhost',
    'dbname' => 'dbname',
    'username' => 'root',
    'password' => 'password',
    'charset' => 'utf8mb4'
    'modelSuffix' => '_model',
    
    'append' => [
        [
            'type' => ['datetime', 'timestamp', 'date'],
            'field' => function ($fieldName){
                return $fieldName . '_client_utc';
            },
            'type_result' => 'string',
        ],
        [
            'type' => ['json'],
            'field' => function ($fieldName){
                return $fieldName . '_arr';
            },
            'type_result' => 'array',
        ],
    ],
];

