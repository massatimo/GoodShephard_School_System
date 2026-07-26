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

$examinationId = filter_var(
    $_POST['examination_id'] ?? null,
    FILTER_VALIDATE_INT
);

$status = trim($_POST['status'] ?? '');

$allowedStatuses = [
    'Draft',
    'Open',
    'Closed',
    'Published',
];

if (
    !$examinationId ||
    !in_array($status, $allowedStatuses, true)
) {
    $_SESSION['error_message'] =
        'Invalid examination status request.';

    header('Location: index.php');
    exit;
}

$statement = $pdo->prepare(
    'UPDATE examinations
     SET
        status = :status,
        updated_at = CURRENT_TIMESTAMP
     WHERE id = :id'
);

$statement->execute([
    'status' => $status,
    'id' => $examinationId,
]);

$_SESSION['success_message'] =
    'The examination status was updated to ' .
    $status . '.';

header('Location: view.php?id=' . $examinationId);
exit;