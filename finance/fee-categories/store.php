<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

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
    exit(
        'Invalid request. Refresh the form and try again.'
    );
}

/*
|--------------------------------------------------------------------------
| Clean submitted input
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

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
        'Category code can only contain uppercase letters, numbers, hyphens and underscores.';
}

if (mb_strlen($categoryCode) > 30) {
    $errors[] =
        'Category code cannot exceed 30 characters.';
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
| Check duplicate name or code
|--------------------------------------------------------------------------
*/

if (
    $categoryName !== '' &&
    $categoryCode !== ''
) {
    $duplicateStatement = $pdo->prepare(
        'SELECT
            category_name,
            category_code
         FROM fee_categories
         WHERE category_name = :category_name
            OR category_code = :category_code
         LIMIT 1'
    );

    $duplicateStatement->execute([
        'category_name' => $categoryName,
        'category_code' => $categoryCode,
    ]);

    $duplicate = $duplicateStatement->fetch();

    if ($duplicate) {
        if (
            strcasecmp(
                (string) $duplicate[
                    'category_name'
                ],
                $categoryName
            ) === 0
        ) {
            $errors[] =
                'A fee category with that name already exists.';
        }

        if (
            strcasecmp(
                (string) $duplicate[
                    'category_code'
                ],
                $categoryCode
            ) === 0
        ) {
            $errors[] =
                'A fee category with that code already exists.';
        }
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    header('Location: create.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Save category
|--------------------------------------------------------------------------
*/

try {
    $statement = $pdo->prepare(
        'INSERT INTO fee_categories (
            category_name,
            category_code,
            description,
            category_type,
            charge_frequency,
            status,
            created_by
        ) VALUES (
            :category_name,
            :category_code,
            :description,
            :category_type,
            :charge_frequency,
            :status,
            :created_by
        )'
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

        'created_by' =>
            isset($_SESSION['user_id'])
                ? (int) $_SESSION['user_id']
                : null,
    ]);

    $_SESSION['success_message'] =
        'The fee category was created successfully.';

    header('Location: index.php');
    exit;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['form_errors'] = [
            'A fee category with the same name or code already exists.',
        ];

        $_SESSION['old_input'] = $input;

        header('Location: create.php');
        exit;
    }

    exit(
        '<h2>Fee category database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}