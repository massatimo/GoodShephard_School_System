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

$subjectName = trim($_POST['subject_name'] ?? '');
$subjectCode = strtoupper(
    trim($_POST['subject_code'] ?? '')
);
$subjectCategory = trim(
    $_POST['subject_category'] ?? ''
);
$applicableLevel = trim(
    $_POST['applicable_level'] ?? ''
);

$allowedCategories = [
    'Core',
    'Optional',
    'Co-curricular',
];

$allowedLevels = [
    'Nursery',
    'Lower Primary',
    'Upper Primary',
    'All',
];

if (
    $subjectName === '' ||
    $subjectCode === '' ||
    !in_array(
        $subjectCategory,
        $allowedCategories,
        true
    ) ||
    !in_array(
        $applicableLevel,
        $allowedLevels,
        true
    )
) {
    $_SESSION['error_message'] =
        'Complete all subject fields correctly.';

    header('Location: index.php');
    exit;
}

try {
    $statement = $pdo->prepare(
        'INSERT INTO subjects (
            subject_name,
            subject_code,
            subject_category,
            applicable_level,
            status
        ) VALUES (
            :subject_name,
            :subject_code,
            :subject_category,
            :applicable_level,
            :status
        )'
    );

    $statement->execute([
        'subject_name' => $subjectName,
        'subject_code' => $subjectCode,
        'subject_category' => $subjectCategory,
        'applicable_level' => $applicableLevel,
        'status' => 'Active',
    ]);

    $_SESSION['success_message'] =
        'Subject created successfully.';
} catch (PDOException $exception) {
    $_SESSION['error_message'] =
        'The subject code may already be in use.';
}

header('Location: index.php');
exit;