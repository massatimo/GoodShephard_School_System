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
    exit(
        'Invalid request. Refresh the form and try again.'
    );
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

$input = [];

foreach ($_POST as $key => $value) {
    $input[$key] = is_string($value)
        ? trim($value)
        : $value;
}

$categoryName =
    trim($input['category_name'] ?? '');

$categoryCode = strtoupper(
    trim($input['category_code'] ?? '')
);

$description =
    trim($input['description'] ?? '');

$categoryType =
    trim($input['category_type'] ?? '');

$chargeFrequency =
    trim($input['charge_frequency'] ?? '');

$status =
    trim($input['status'] ?? '');

$errors = [];

if ($categoryName === '') {
    $errors[] =
        'Category name is required.';
}

if (mb_strlen($categoryName) > 120) {
    $errors[] =
        'Category name cannot exceed 120 characters.';
}

if ($categoryCode === '') {
    $errors[] =
        'Category code is required.';
}

if (
    !preg_match(
        '/^[A-Z0-9_-]+$/',
        $categoryCode
    )
) {
    $errors[] =
        'Category code contains invalid characters.';
}

if (mb_strlen($description) > 500) {
    $errors[] =
        'Description cannot exceed 500 characters.';
}

if (
    !in_array(
        $categoryType,
        ['Mandatory', 'Optional'],
        true
    )
) {
    $errors[] =
        'Select a valid category type.';
}

if (
    !in_array(
        $chargeFrequency,
        [
            'Once',
            'Termly',
            'Annually',
            'Monthly',
        ],
        true
    )
) {
    $errors[] =
        'Select a valid charge frequency.';
}

if (
    !in_array(
        $status,
        ['Active', 'Inactive'],
        true
    )
) {
    $errors[] =
        'Select a valid category status.';
}

/*
|--------------------------------------------------------------------------
| Confirm category exists
|--------------------------------------------------------------------------
*/

$categoryStatement = $pdo->prepare(
    'SELECT id
     FROM fee_categories
     WHERE id = :id
     LIMIT 1'
);

$categoryStatement->execute([
    'id' => $categoryId,
]);

if (!$categoryStatement->fetch()) {
    $_SESSION['error_message'] =
        'The selected fee category was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Check duplicates excluding current category
|--------------------------------------------------------------------------
*/

$duplicateStatement = $pdo->prepare(
    'SELECT id
     FROM fee_categories
     WHERE (
        category_name = :category_name
        OR category_code = :category_code
     )
       AND id <> :id
     LIMIT 1'
);

$duplicateStatement->execute([
    'category_name' => $categoryName,
    'category_code' => $categoryCode,
    'id' => $categoryId,
]);

if ($duplicateStatement->fetch()) {
    $errors[] =
        'Another fee category already uses that name or code.';
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    header(
        'Location: edit.php?id=' .
        $categoryId
    );
    exit;
}

try {
    $statement = $pdo->prepare(
        'UPDATE fee_categories
         SET
            category_name = :category_name,
            category_code = :category_code,
            description = :description,
            category_type = :category_type,
            charge_frequency = :charge_frequency,
            status = :status,
            updated_at = CURRENT_TIMESTAMP
         WHERE id = :id'
    );

    $statement->execute([
        'category_name' => $categoryName,
        'category_code' => $categoryCode,

        'description' =>
            $description !== ''
                ? $description
                : null,

        'category_type' => $categoryType,
        'charge_frequency' => $chargeFrequency,
        'status' => $status,
        'id' => $categoryId,
    ]);

    $_SESSION['success_message'] =
        'The fee category was updated successfully.';

    header('Location: index.php');
    exit;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['form_errors'] = [
            'Another fee category already uses that name or code.',
        ];

        $_SESSION['old_input'] = $input;

        header(
            'Location: edit.php?id=' .
            $categoryId
        );
        exit;
    }

    exit(
        '<h2>Fee category update error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}