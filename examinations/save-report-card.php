<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validate CSRF token
|--------------------------------------------------------------------------
*/

if (
    !hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token'] ?? ''
    )
) {
    exit(
        'Invalid request. Refresh the page and try again.'
    );
}

/*
|--------------------------------------------------------------------------
| Validate identifiers
|--------------------------------------------------------------------------
*/

$examinationId = filter_var(
    $_POST['examination_id'] ?? null,
    FILTER_VALIDATE_INT
);

$examinationClassId = filter_var(
    $_POST['examination_class_id'] ?? null,
    FILTER_VALIDATE_INT
);

$pupilId = filter_var(
    $_POST['pupil_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    !$examinationId ||
    !$examinationClassId ||
    !$pupilId
) {
    $_SESSION['error_message'] =
        'Invalid report-card information.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validate report values
|--------------------------------------------------------------------------
*/

$daysOpen = filter_var(
    $_POST['days_open'] ?? null,
    FILTER_VALIDATE_INT
);

$daysPresent = filter_var(
    $_POST['days_present'] ?? null,
    FILTER_VALIDATE_INT
);

$classTeacherComment = trim(
    (string) (
        $_POST['class_teacher_comment'] ?? ''
    )
);

$headTeacherComment = trim(
    (string) (
        $_POST['head_teacher_comment'] ?? ''
    )
);

$promotionStatus = trim(
    (string) (
        $_POST['promotion_status'] ?? ''
    )
);

$nextClassId =
    ($_POST['next_class_id'] ?? '') !== ''
        ? filter_var(
            $_POST['next_class_id'],
            FILTER_VALIDATE_INT
        )
        : null;

$allowedPromotionStatuses = [
    'Pending',
    'Promoted',
    'Repeat',
];

$errors = [];

if ($daysOpen === false || $daysOpen < 0) {
    $errors[] =
        'Days open must be zero or greater.';
}

if (
    $daysPresent === false ||
    $daysPresent < 0
) {
    $errors[] =
        'Days present must be zero or greater.';
}

if (
    $daysOpen !== false &&
    $daysPresent !== false &&
    $daysPresent > $daysOpen
) {
    $errors[] =
        'Days present cannot exceed days open.';
}

if (
    !in_array(
        $promotionStatus,
        $allowedPromotionStatuses,
        true
    )
) {
    $errors[] =
        'Select a valid promotion status.';
}

if (
    $promotionStatus === 'Promoted' &&
    !$nextClassId
) {
    $errors[] =
        'Select the class the pupil is promoted to.';
}

if (
    mb_strlen($classTeacherComment) > 1000
) {
    $errors[] =
        'Class teacher comment is too long.';
}

if (
    mb_strlen($headTeacherComment) > 1000
) {
    $errors[] =
        'Head teacher comment is too long.';
}

/*
|--------------------------------------------------------------------------
| Confirm examination class and pupil relationship
|--------------------------------------------------------------------------
*/

$relationshipStatement = $pdo->prepare(
    'SELECT
        examination_classes.id

     FROM examination_classes

     INNER JOIN examinations
        ON examinations.id =
            examination_classes.examination_id

     INNER JOIN pupils
        ON pupils.class_id =
            examination_classes.class_id

     WHERE examination_classes.id =
        :examination_class_id

       AND examinations.id =
        :examination_id

       AND pupils.id =
        :pupil_id

       AND (
            examination_classes.stream_id IS NULL
            OR pupils.stream_id =
                examination_classes.stream_id
       )

     LIMIT 1'
);

$relationshipStatement->execute([
    'examination_class_id' =>
        $examinationClassId,
    'examination_id' =>
        $examinationId,
    'pupil_id' =>
        $pupilId,
]);

if (!$relationshipStatement->fetch()) {
    $errors[] =
        'The pupil does not belong to the selected examination class.';
}

/*
|--------------------------------------------------------------------------
| Validate next class
|--------------------------------------------------------------------------
*/

if ($nextClassId) {
    $nextClassStatement = $pdo->prepare(
        "SELECT id
         FROM classes
         WHERE id = :id
           AND status = 'Active'
         LIMIT 1"
    );

    $nextClassStatement->execute([
        'id' => $nextClassId,
    ]);

    if (!$nextClassStatement->fetch()) {
        $errors[] =
            'The selected next class is invalid.';
    }
}

if ($errors !== []) {
    $_SESSION['error_message'] =
        implode(' ', $errors);

    header(
        'Location: pupil-result.php?' .
        http_build_query([
            'examination_id' =>
                $examinationId,
            'examination_class_id' =>
                $examinationClassId,
            'pupil_id' =>
                $pupilId,
        ])
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Save report-card information
|--------------------------------------------------------------------------
*/

try {
    $statement = $pdo->prepare(
        'INSERT INTO report_card_comments (
            examination_class_id,
            pupil_id,
            days_open,
            days_present,
            class_teacher_comment,
            head_teacher_comment,
            promotion_status,
            next_class_id,
            created_by
        ) VALUES (
            :examination_class_id,
            :pupil_id,
            :days_open,
            :days_present,
            :class_teacher_comment,
            :head_teacher_comment,
            :promotion_status,
            :next_class_id,
            :created_by
        )

        ON DUPLICATE KEY UPDATE
            days_open =
                VALUES(days_open),

            days_present =
                VALUES(days_present),

            class_teacher_comment =
                VALUES(class_teacher_comment),

            head_teacher_comment =
                VALUES(head_teacher_comment),

            promotion_status =
                VALUES(promotion_status),

            next_class_id =
                VALUES(next_class_id),

            created_by =
                VALUES(created_by),

            updated_at =
                CURRENT_TIMESTAMP'
    );

    $statement->execute([
        'examination_class_id' =>
            $examinationClassId,

        'pupil_id' =>
            $pupilId,

        'days_open' =>
            $daysOpen,

        'days_present' =>
            $daysPresent,

        'class_teacher_comment' =>
            $classTeacherComment !== ''
                ? $classTeacherComment
                : null,

        'head_teacher_comment' =>
            $headTeacherComment !== ''
                ? $headTeacherComment
                : null,

        'promotion_status' =>
            $promotionStatus,

        'next_class_id' =>
            $promotionStatus === 'Promoted'
                ? $nextClassId
                : null,

        'created_by' =>
            isset($_SESSION['user_id'])
                ? (int) $_SESSION['user_id']
                : null,
    ]);

    $_SESSION['success_message'] =
        'The pupil report information was saved successfully.';
} catch (PDOException $exception) {
    $_SESSION['error_message'] =
        'The report information could not be saved.';

    /*
     * During development, temporarily use:
     *
     * $_SESSION["error_message"] =
     *     $exception->getMessage();
     */
}

header(
    'Location: pupil-result.php?' .
    http_build_query([
        'examination_id' =>
            $examinationId,
        'examination_class_id' =>
            $examinationClassId,
        'pupil_id' =>
            $pupilId,
    ])
);

exit;