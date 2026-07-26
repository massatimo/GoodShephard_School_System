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

$staffId = filter_var(
    $input['staff_id'] ?? null,
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

$classId = filter_var(
    $input['class_id'] ?? null,
    FILTER_VALIDATE_INT
);

$streamId = ($input['stream_id'] ?? '') !== ''
    ? filter_var(
        $input['stream_id'],
        FILTER_VALIDATE_INT
    )
    : null;

$subjectId = ($input['subject_id'] ?? '') !== ''
    ? filter_var(
        $input['subject_id'],
        FILTER_VALIDATE_INT
    )
    : null;

$isClassTeacher =
    isset($_POST['is_class_teacher']) ? 1 : 0;

$errors = [];

if (!$staffId) {
    $errors[] = 'Select a valid teacher.';
}

if (!$academicYearId) {
    $errors[] = 'Select a valid academic year.';
}

if (!$termId) {
    $errors[] = 'Select a valid school term.';
}

if (!$classId) {
    $errors[] = 'Select a valid class.';
}

if (!$subjectId && $isClassTeacher !== 1) {
    $errors[] =
        'Select a subject or assign the teacher as a class teacher.';
}

/*
|--------------------------------------------------------------------------
| Confirm that the staff member is an active teacher
|--------------------------------------------------------------------------
*/

if ($staffId) {
    $teacherStatement = $pdo->prepare(
        "SELECT id
         FROM staff
         WHERE id = :id
           AND staff_category = 'Teaching Staff'
           AND employment_status = 'Active'
         LIMIT 1"
    );

    $teacherStatement->execute([
        'id' => $staffId,
    ]);

    if (!$teacherStatement->fetch()) {
        $errors[] =
            'The selected staff member is not an active teacher.';
    }
}

/*
|--------------------------------------------------------------------------
| Confirm term belongs to selected academic year
|--------------------------------------------------------------------------
*/

if ($termId && $academicYearId) {
    $termStatement = $pdo->prepare(
        'SELECT id
         FROM terms
         WHERE id = :term_id
           AND academic_year_id = :academic_year_id
         LIMIT 1'
    );

    $termStatement->execute([
        'term_id' => $termId,
        'academic_year_id' => $academicYearId,
    ]);

    if (!$termStatement->fetch()) {
        $errors[] =
            'The selected term does not belong to that academic year.';
    }
}

/*
|--------------------------------------------------------------------------
| Confirm stream belongs to selected class
|--------------------------------------------------------------------------
*/

if ($streamId && $classId) {
    $streamStatement = $pdo->prepare(
        'SELECT id
         FROM streams
         WHERE id = :stream_id
           AND class_id = :class_id
         LIMIT 1'
    );

    $streamStatement->execute([
        'stream_id' => $streamId,
        'class_id' => $classId,
    ]);

    if (!$streamStatement->fetch()) {
        $errors[] =
            'The selected stream does not belong to that class.';
    }
}

/*
|--------------------------------------------------------------------------
| Prevent two class teachers for the same class and stream
|--------------------------------------------------------------------------
*/

if (
    $isClassTeacher === 1 &&
    $academicYearId &&
    $termId &&
    $classId
) {
    $classTeacherSql = '
        SELECT
            teacher_allocations.id,
            staff.first_name,
            staff.last_name,
            staff.full_name
        FROM teacher_allocations
        INNER JOIN staff
            ON staff.id = teacher_allocations.staff_id
        WHERE teacher_allocations.academic_year_id =
            :academic_year_id
          AND teacher_allocations.term_id = :term_id
          AND teacher_allocations.class_id = :class_id
          AND teacher_allocations.is_class_teacher = 1
          AND teacher_allocations.status = "Active"
    ';

    $classTeacherParameters = [
        'academic_year_id' => $academicYearId,
        'term_id' => $termId,
        'class_id' => $classId,
    ];

    if ($streamId === null) {
        $classTeacherSql .= '
            AND teacher_allocations.stream_id IS NULL
        ';
    } else {
        $classTeacherSql .= '
            AND teacher_allocations.stream_id = :stream_id
        ';

        $classTeacherParameters['stream_id'] = $streamId;
    }

    $classTeacherSql .= ' LIMIT 1';

    $classTeacherStatement =
        $pdo->prepare($classTeacherSql);

    $classTeacherStatement->execute(
        $classTeacherParameters
    );

    $existingClassTeacher =
        $classTeacherStatement->fetch();

    if ($existingClassTeacher) {
        $existingName = trim(
            ($existingClassTeacher['first_name'] ?? '') . ' ' .
            ($existingClassTeacher['last_name'] ?? '')
        );

        if ($existingName === '') {
            $existingName =
                $existingClassTeacher['full_name']
                ?? 'another teacher';
        }

        $errors[] =
            $existingName .
            ' is already assigned as the class teacher.';
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    if ($isClassTeacher === 1) {
        $_SESSION['old_input']['is_class_teacher'] = '1';
    }

    header('Location: create.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Insert the assignment
|--------------------------------------------------------------------------
*/

try {
    $statement = $pdo->prepare(
        'INSERT INTO teacher_allocations (
            staff_id,
            academic_year_id,
            term_id,
            class_id,
            stream_id,
            subject_id,
            is_class_teacher,
            status
        ) VALUES (
            :staff_id,
            :academic_year_id,
            :term_id,
            :class_id,
            :stream_id,
            :subject_id,
            :is_class_teacher,
            :status
        )'
    );

    $statement->execute([
        'staff_id' => $staffId,
        'academic_year_id' => $academicYearId,
        'term_id' => $termId,
        'class_id' => $classId,
        'stream_id' => $streamId,
        'subject_id' => $subjectId,
        'is_class_teacher' => $isClassTeacher,
        'status' => 'Active',
    ]);

    $_SESSION['success_message'] =
        'The teacher allocation was saved successfully.';

    header('Location: index.php');
    exit;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['error_message'] =
            'That teacher assignment already exists.';
    } else {
        $_SESSION['error_message'] =
            'The teacher allocation could not be saved.';
    }

    header('Location: index.php');
    exit;
}