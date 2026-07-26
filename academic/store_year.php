<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (
    !hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token'] ?? ''
    )
) {
    exit('Invalid request.');
}

$yearName = trim($_POST['year_name'] ?? '');
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$isCurrent = isset($_POST['is_current']) ? 1 : 0;

if ($yearName === '' || $startDate === '' || $endDate === '') {
    $_SESSION['error_message'] =
        'Complete all academic year fields.';

    header('Location: index.php');
    exit;
}

if ($startDate >= $endDate) {
    $_SESSION['error_message'] =
        'The end date must be later than the start date.';

    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    if ($isCurrent === 1) {
        $pdo->exec(
            'UPDATE academic_years SET is_current = 0'
        );
    }

    $statement = $pdo->prepare(
        'INSERT INTO academic_years (
            year_name,
            start_date,
            end_date,
            is_current,
            status
        ) VALUES (
            :year_name,
            :start_date,
            :end_date,
            :is_current,
            :status
        )'
    );

    $statement->execute([
        'year_name' => $yearName,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'is_current' => $isCurrent,
        'status' => 'Active',
    ]);

    $pdo->commit();

    $_SESSION['success_message'] =
        'Academic year created successfully.';
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_message'] =
        'The academic year could not be created.';
}

header('Location: index.php');
exit;