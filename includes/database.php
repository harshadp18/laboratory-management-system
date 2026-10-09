<?php

function databaseConnection(): PDO
{
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl !== false && trim($databaseUrl) !== '') {
        $connection = parse_url($databaseUrl);
        if ($connection === false || empty($connection['host']) || empty($connection['user'])
            || !isset($connection['pass']) || empty($connection['path'])) {
            throw new RuntimeException('DATABASE_URL is invalid.');
        }
        if (!in_array($connection['scheme'] ?? '', ['postgres', 'postgresql'], true)) {
            throw new RuntimeException('DATABASE_URL must use the PostgreSQL scheme.');
        }

        $databaseName = rawurldecode(ltrim($connection['path'], '/'));
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $connection['host'],
            $connection['port'] ?? 5432,
            $databaseName
        );
        parse_str($connection['query'] ?? '', $queryOptions);
        if (!empty($queryOptions['sslmode'])) {
            $dsn .= ';sslmode=' . $queryOptions['sslmode'];
        }

        return new PDO($dsn, rawurldecode($connection['user']), rawurldecode($connection['pass']), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    $configurationFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configurationFile)) {
        throw new RuntimeException('Database configuration is missing. Set DATABASE_URL or copy config/database.php.example to config/database.php for local use.');
    }
    $config = require $configurationFile;
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']);
    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
