<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$allowedRoles = [
    'administrator',
    'headteacher',
    'bursar',
    'teacher',
];

if (
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], $allowedRoles, true)
) {
    session_unset();
    session_destroy();

    header('Location: ../auth/login.php');
    exit;
}