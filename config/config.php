<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'submission_portal',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'upload_dir' => __DIR__ . '/../uploads',
    'max_file_size' => 10 * 1024 * 1024, // 10 MB
    'allowed_extensions' => ['ppt', 'pptx', 'doc', 'docx', 'zip', 'py', 'pdf'],
];
