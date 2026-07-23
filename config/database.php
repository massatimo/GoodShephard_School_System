<?php

declare(strict_types=1);

$databaseHost = '127.0.0.1';
$databasePort = '3306';
$databaseName = 'goodshepherd_school';
$databaseUser = 'root';
$databasePassword = '';

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $databaseHost,
    $databasePort,
    $databaseName
);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        $dsn,
        $databaseUser,
        $databasePassword,
        $options
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());

    exit(
        'The system could not connect to the database. ' .
        'Confirm that MySQL is running and check the database settings.'
    );
}