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

$academicYearId = filter_var(
    $_POST['academic_year_id'] ?? null,
    FILTER_VALIDATE_INT
);

$termName = trim($_POST['term_name'] ?? '');
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$isCurrent = isset($_POST['is_current']) ? 1 : 0;

$allowedTerms = [
    'Term One',
    'Term Two',
    'Term Three',
];

if (
    !$academicYearId ||
    !in_array($termName, $allowedTerms, true) ||
    $startDate === '' ||
    $endDate === ''
) {
    $_SESSION['error_message'] =
        'Complete all term fields correctly.';

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
            'UPDATE terms SET is_current = 0'
        );
    }

    $statement = $pdo->prepare(
        'INSERT INTO terms (
            academic_year_id,
            term_name,
            start_date,
            end_date,
            is_current,
            status
        ) VALUES (
            :academic_year_id,
            :term_name,
            :start_date,
            :end_date,
            :is_current,
            :status
        )'
    );

    $statement->execute([
        'academic_year_id' => $academicYearId,
        'term_name' => $termName,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'is_current' => $isCurrent,
        'status' => 'Active',
    ]);

    $pdo->commit();

    $_SESSION['success_message'] =
        'School term created successfully.';
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error_message'] =
        'The term may already exist for that academic year.';
}

header('Location: index.php');
exit;