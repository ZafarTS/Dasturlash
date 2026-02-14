<?php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'zafarts',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'quiz' => [
        'max_score' => 100,
        'latency_compensation_cap_ms' => 150,
    ],
    'realtime' => [
        'ws_host' => '0.0.0.0',
        'ws_port' => 8090,
    ],
];
