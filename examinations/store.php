<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

if (
    !hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token'] ?? ''
    )
) {
    exit('Invalid request. Refresh the form and try again.');
}

$input = [];

foreach ($_POST as $key => $value) {
    $input[$key] = is_string($value)
        ? trim($value)
        : $value;
}

$examinationTypeId = filter_var(
    $input['examination_type_id'] ?? null,
    FILTER_VALIDATE_INT
);

$academicYearId = filter_var(
    $input['academic_year_id'] ?? null,
    FILTER_VALIDATE_INT
);

$termId = filter_var(
    $input['term_id'] ?? null,
    FILTER_VALIDATE_INT
);

$examinationName =
    trim($input['examination_name'] ?? '');

$startDate = trim($input['start_date'] ?? '');
$endDate = trim($input['end_date'] ?? '');
$status = trim($input['status'] ?? 'Draft');
$description = trim($input['description'] ?? '');

$errors = [];

if ($examinationName === '') {
    $errors[] = 'Examination name is required.';
}

if (!$examinationTypeId) {
    $errors[] = 'Select a valid examination type.';
}

if (!$academicYearId) {
    $errors[] = 'Select a valid academic year.';
}

if (!$termId) {
    $errors[] = 'Select a valid school term.';
}

if (!in_array($status, ['Draft', 'Open'], true)) {
    $errors[] = 'Select a valid examination status.';
}

if (
    $startDate !== '' &&
    $endDate !== '' &&
    $startDate > $endDate
) {
    $errors[] =
        'The examination end date must not be before the start date.';
}

if ($termId && $academicYearId) {
    $termStatement = $pdo->prepare(
        'SELECT id, start_date, end_date
         FROM terms
         WHERE id = :term_id
           AND academic_year_id = :academic_year_id
         LIMIT 1'
    );

    $termStatement->execute([
        'term_id' => $termId,
        'academic_year_id' => $academicYearId,
    ]);

    $term = $termStatement->fetch();

    if (!$term) {
        $errors[] =
            'The selected term does not belong to that academic year.';
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    header('Location: create.php');
    exit;
}

try {
    $statement = $pdo->prepare(
        'INSERT INTO examinations (
            examination_type_id,
            academic_year_id,
            term_id,
            examination_name,
            start_date,
            end_date,
            status,
            description,
            created_by
        ) VALUES (
            :examination_type_id,
            :academic_year_id,
            :term_id,
            :examination_name,
            :start_date,
            :end_date,
            :status,
            :description,
            :created_by
        )'
    );

    $statement->execute([
        'examination_type_id' => $examinationTypeId,
        'academic_year_id' => $academicYearId,
        'term_id' => $termId,
        'examination_name' => $examinationName,
        'start_date' =>
            $startDate !== '' ? $startDate : null,
        'end_date' =>
            $endDate !== '' ? $endDate : null,
        'status' => $status,
        'description' =>
            $description !== '' ? $description : null,
        'created_by' => (int) $_SESSION['user_id'],
    ]);

    $examinationId = (int) $pdo->lastInsertId();

    $_SESSION['success_message'] =
        'The examination was created successfully. ' .
        'You can now assign classes and subjects.';

    header('Location: view.php?id=' . $examinationId);
    exit;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['form_errors'] = [
            'An examination with that name already exists ' .
            'for the selected academic year and term.',
        ];

        $_SESSION['old_input'] = $input;

        header('Location: create.php');
        exit;
    }

    exit(
        '<h2>Examination database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}