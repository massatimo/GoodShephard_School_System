<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$allocationId = filter_var(
    $_POST['allocation_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$allocationId || $allocationId < 1) {
    $_SESSION['error_message'] =
        'Invalid teacher allocation selected.';

    header('Location: index.php');
    exit;
}

$statement = $pdo->prepare(
    'DELETE FROM teacher_allocations
     WHERE id = :id'
);

$statement->execute([
    'id' => $allocationId,
]);

$_SESSION['success_message'] =
    'The teacher allocation was removed successfully.';

header('Location: index.php');
exit;