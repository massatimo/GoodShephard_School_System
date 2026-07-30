<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

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
    exit('Invalid request. Refresh the page and try again.');
}

$action = trim($_POST['action'] ?? '');

$examinationId = filter_var(
    $_POST['examination_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$examinationId) {
    $_SESSION['error_message'] =
        'Invalid examination selected.';

    header('Location: index.php');
    exit;
}

$examStatement = $pdo->prepare(
    'SELECT id, status
     FROM examinations
     WHERE id = :id
     LIMIT 1'
);

$examStatement->execute([
    'id' => $examinationId,
]);

$examination = $examStatement->fetch();

if (!$examination) {
    $_SESSION['error_message'] =
        'The examination was not found.';

    header('Location: index.php');
    exit;
}

if ($examination['status'] === 'Published') {
    $_SESSION['error_message'] =
        'Published examinations cannot be changed.';

    header(
        'Location: view.php?id=' . $examinationId
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Create class with several subjects
|--------------------------------------------------------------------------
*/

if ($action === 'create_class') {
    $classId = filter_var(
        $_POST['class_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $streamId = ($_POST['stream_id'] ?? '') !== ''
        ? filter_var(
            $_POST['stream_id'],
            FILTER_VALIDATE_INT
        )
        : null;

    $subjectIds = $_POST['subject_ids'] ?? [];

    $maximumMark = filter_var(
        $_POST['maximum_mark'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $passMark = filter_var(
        $_POST['pass_mark'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $weightPercentage = filter_var(
        $_POST['weight_percentage'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $errors = [];

    if (!$classId) {
        $errors[] = 'Select a valid class.';
    }

    if (
        !is_array($subjectIds) ||
        $subjectIds === []
    ) {
        $errors[] = 'Select at least one subject.';
    }

    if ($maximumMark === false || $maximumMark <= 0) {
        $errors[] =
            'Maximum mark must be greater than zero.';
    }

    if (
        $passMark === false ||
        $passMark < 0 ||
        (
            $maximumMark !== false &&
            $passMark > $maximumMark
        )
    ) {
        $errors[] =
            'Pass mark must be between zero and the maximum mark.';
    }

    if (
        $weightPercentage === false ||
        $weightPercentage <= 0 ||
        $weightPercentage > 100
    ) {
        $errors[] =
            'Weight percentage must be between 0.01 and 100.';
    }

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
    | Prevent duplicate class and stream assignment
    |--------------------------------------------------------------------------
    */

    if ($classId) {
        $duplicateSql = '
            SELECT id
            FROM examination_classes
            WHERE examination_id = :examination_id
              AND class_id = :class_id
        ';

        $duplicateParameters = [
            'examination_id' => $examinationId,
            'class_id' => $classId,
        ];

        if ($streamId === null) {
            $duplicateSql .= '
                AND stream_id IS NULL
            ';
        } else {
            $duplicateSql .= '
                AND stream_id = :stream_id
            ';

            $duplicateParameters['stream_id'] =
                $streamId;
        }

        $duplicateSql .= ' LIMIT 1';

        $duplicateStatement =
            $pdo->prepare($duplicateSql);

        $duplicateStatement->execute(
            $duplicateParameters
        );

        if ($duplicateStatement->fetch()) {
            $errors[] =
                'That class and stream are already assigned ' .
                'to this examination.';
        }
    }

    $cleanSubjectIds = [];

    foreach ($subjectIds as $subjectId) {
        $subjectId = filter_var(
            $subjectId,
            FILTER_VALIDATE_INT
        );

        if ($subjectId) {
            $cleanSubjectIds[] = (int) $subjectId;
        }
    }

    $cleanSubjectIds = array_values(
        array_unique($cleanSubjectIds)
    );

    if ($cleanSubjectIds === []) {
        $errors[] = 'Select valid examination subjects.';
    }

    if ($errors !== []) {
        $_SESSION['form_errors'] = $errors;

        $_SESSION['old_input'] = [
            'class_id' => $classId,
            'stream_id' => $streamId,
            'subject_ids' => $cleanSubjectIds,
            'maximum_mark' => $maximumMark,
            'pass_mark' => $passMark,
            'weight_percentage' =>
                $weightPercentage,
        ];

        header(
            'Location: setup-class.php?' .
            http_build_query([
                'examination_id' => $examinationId,
            ])
        );
        exit;
    }

    try {
        $pdo->beginTransaction();

        $classStatement = $pdo->prepare(
            'INSERT INTO examination_classes (
                examination_id,
                class_id,
                stream_id,
                status
            ) VALUES (
                :examination_id,
                :class_id,
                :stream_id,
                :status
            )'
        );

        $classStatement->execute([
            'examination_id' => $examinationId,
            'class_id' => $classId,
            'stream_id' => $streamId,
            'status' => 'Active',
        ]);

        $examinationClassId =
            (int) $pdo->lastInsertId();

        $subjectStatement = $pdo->prepare(
            'INSERT INTO examination_subjects (
                examination_class_id,
                subject_id,
                maximum_mark,
                pass_mark,
                weight_percentage,
                status
            ) VALUES (
                :examination_class_id,
                :subject_id,
                :maximum_mark,
                :pass_mark,
                :weight_percentage,
                :status
            )'
        );

        foreach ($cleanSubjectIds as $subjectId) {
            $subjectStatement->execute([
                'examination_class_id' =>
                    $examinationClassId,
                'subject_id' => $subjectId,
                'maximum_mark' => $maximumMark,
                'pass_mark' => $passMark,
                'weight_percentage' =>
                    $weightPercentage,
                'status' => 'Active',
            ]);
        }

        $pdo->commit();

        $_SESSION['success_message'] =
            'The examination class and subjects ' .
            'were assigned successfully.';

        header(
            'Location: setup-class.php?' .
            http_build_query([
                'examination_id' => $examinationId,
                'examination_class_id' =>
                    $examinationClassId,
            ])
        );
        exit;
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        exit(
            '<h2>Examination setup error</h2><pre>' .
            htmlspecialchars($exception->getMessage()) .
            '</pre>'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Add one subject to an existing examination class
|--------------------------------------------------------------------------
*/

if ($action === 'add_subject') {
    $examinationClassId = filter_var(
        $_POST['examination_class_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $subjectId = filter_var(
        $_POST['subject_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $maximumMark = filter_var(
        $_POST['maximum_mark'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $passMark = filter_var(
        $_POST['pass_mark'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $weightPercentage = filter_var(
        $_POST['weight_percentage'] ?? null,
        FILTER_VALIDATE_FLOAT
    );

    $errors = [];

    if (!$examinationClassId) {
        $errors[] =
            'Invalid examination class selected.';
    }

    if (!$subjectId) {
        $errors[] = 'Select a valid subject.';
    }

    if ($maximumMark === false || $maximumMark <= 0) {
        $errors[] =
            'Maximum mark must be greater than zero.';
    }

    if (
        $passMark === false ||
        $passMark < 0 ||
        (
            $maximumMark !== false &&
            $passMark > $maximumMark
        )
    ) {
        $errors[] =
            'Pass mark must not exceed the maximum mark.';
    }

    if (
        $weightPercentage === false ||
        $weightPercentage <= 0 ||
        $weightPercentage > 100
    ) {
        $errors[] =
            'Weight percentage must be between 0.01 and 100.';
    }

    if ($examinationClassId) {
        $classCheck = $pdo->prepare(
            'SELECT id
             FROM examination_classes
             WHERE id = :id
               AND examination_id = :examination_id
             LIMIT 1'
        );

        $classCheck->execute([
            'id' => $examinationClassId,
            'examination_id' => $examinationId,
        ]);

        if (!$classCheck->fetch()) {
            $errors[] =
                'The examination class does not belong ' .
                'to this examination.';
        }
    }

    if ($errors !== []) {
        $_SESSION['form_errors'] = $errors;

        header(
            'Location: setup-class.php?' .
            http_build_query([
                'examination_id' => $examinationId,
                'examination_class_id' =>
                    $examinationClassId,
            ])
        );
        exit;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO examination_subjects (
                examination_class_id,
                subject_id,
                maximum_mark,
                pass_mark,
                weight_percentage,
                status
            ) VALUES (
                :examination_class_id,
                :subject_id,
                :maximum_mark,
                :pass_mark,
                :weight_percentage,
                :status
            )'
        );

        $statement->execute([
            'examination_class_id' =>
                $examinationClassId,
            'subject_id' => $subjectId,
            'maximum_mark' => $maximumMark,
            'pass_mark' => $passMark,
            'weight_percentage' =>
                $weightPercentage,
            'status' => 'Active',
        ]);

        $_SESSION['success_message'] =
            'The subject was added successfully.';
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            $_SESSION['error_message'] =
                'That subject is already assigned to this class.';
        } else {
            $_SESSION['error_message'] =
                'The subject could not be added.';
        }
    }

    header(
        'Location: setup-class.php?' .
        http_build_query([
            'examination_id' => $examinationId,
            'examination_class_id' =>
                $examinationClassId,
        ])
    );
    exit;
}

$_SESSION['error_message'] =
    'Invalid examination setup action.';

header('Location: view.php?id=' . $examinationId);
exit;