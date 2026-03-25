<?php

declare(strict_types=1);
use App\Ingest\FixedFilesystemHandler;
use LaravelIngest\Sources\JsonHandler;
use LaravelIngest\Sources\RemoteDiskHandler;
use LaravelIngest\Sources\UploadHandler;
use LaravelIngest\Sources\UrlHandler;

return [
    'path' => 'api/v1/ingest',
    'domain' => null,

    'middleware' => ['api'],

    'importers' => [
        // 'user-importer' => App\Ingest\UserImporter::class,
    ],

    'chunk_size' => 100,

    'max_show_rows' => env('INGEST_MAX_SHOW_ROWS', 100),

    'queue' => [
        'connection' => env('INGEST_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'name' => env('INGEST_QUEUE_NAME', 'default'),
    ],

    'disk' => env('INGEST_DISK', 'local'),

    'log_rows' => true,

    'prune_days' => 30,

    'handlers' => [
        'upload' => UploadHandler::class,
        'filesystem' => FixedFilesystemHandler::class,
        'ftp' => RemoteDiskHandler::class,
        'sftp' => RemoteDiskHandler::class,
        'url' => UrlHandler::class,
        'json-stream' => JsonHandler::class,
    ],
];
