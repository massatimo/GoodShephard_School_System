<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

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

$categoryId = filter_var(
    $_POST['category_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$categoryId || $categoryId < 1) {
    $_SESSION['error_message'] =
        'Invalid fee category selected.';

    header('Location: index.php');
    exit;
}

$statement = $pdo->prepare(
    'SELECT
        category_name,
        status
     FROM fee_categories
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $categoryId,
]);

$category = $statement->fetch();

if (!$category) {
    $_SESSION['error_message'] =
        'The selected fee category was not found.';

    header('Location: index.php');
    exit;
}

$newStatus =
    $category['status'] === 'Active'
        ? 'Inactive'
        : 'Active';

$updateStatement = $pdo->prepare(
    'UPDATE fee_categories
     SET
        status = :status,
        updated_at = CURRENT_TIMESTAMP
     WHERE id = :id'
);

$updateStatement->execute([
    'status' => $newStatus,
    'id' => $categoryId,
]);

$_SESSION['success_message'] =
    htmlspecialchars(
        (string) $category['category_name']
    ) .
    ' was changed to ' .
    $newStatus . '.';

header('Location: index.php');
exit;