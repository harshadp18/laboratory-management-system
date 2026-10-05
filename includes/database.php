<?php

function databaseConnection(): PDO
{
    $configurationFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configurationFile)) {
        throw new RuntimeException('Database configuration is missing. Copy config/database.php.example to config/database.php and enter your local PostgreSQL details.');
    }
    $config = require $configurationFile;
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']);
    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
