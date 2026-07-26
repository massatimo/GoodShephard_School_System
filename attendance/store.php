<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: take.php');
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

$academicYearId = filter_var(
    $_POST['academic_year_id'] ?? null,
    FILTER_VALIDATE_INT
);

$termId = filter_var(
    $_POST['term_id'] ?? null,
    FILTER_VALIDATE_INT
);

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

$attendanceDate =
    trim($_POST['attendance_date'] ?? '');

$session = trim($_POST['session'] ?? '');

$pupilIds = $_POST['pupil_ids'] ?? [];
$attendanceStatuses = $_POST['attendance_status'] ?? [];
$remarks = $_POST['remarks'] ?? [];

$notes = trim($_POST['notes'] ?? '');

$errors = [];

if (!$academicYearId) {
    $errors[] = 'Select a valid academic year.';
}

if (!$termId) {
    $errors[] = 'Select a valid term.';
}

if (!$classId) {
    $errors[] = 'Select a valid class.';
}

if ($attendanceDate === '') {
    $errors[] = 'Attendance date is required.';
}

if (!in_array($session, ['Morning', 'Afternoon'], true)) {
    $errors[] = 'Select a valid attendance session.';
}

if (!is_array($pupilIds) || $pupilIds === []) {
    $errors[] = 'No pupils were submitted.';
}

$allowedStatuses = [
    'Present',
    'Absent',
    'Late',
    'Excused',
];

foreach ($pupilIds as $pupilId) {
    $pupilId = (int) $pupilId;

    $status = $attendanceStatuses[$pupilId] ?? '';

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] =
            'One or more pupils have an invalid attendance status.';

        break;
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;

    header(
        'Location: take.php?' .
        http_build_query([
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'class_id' => $classId,
            'stream_id' => $streamId,
            'attendance_date' => $attendanceDate,
            'session' => $session,
        ])
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent duplicate attendance register
|--------------------------------------------------------------------------
*/

$duplicateSql = '
    SELECT id
    FROM attendance_registers
    WHERE academic_year_id = :academic_year_id
      AND term_id = :term_id
      AND class_id = :class_id
      AND attendance_date = :attendance_date
      AND session = :session
';

$duplicateParameters = [
    'academic_year_id' => $academicYearId,
    'term_id' => $termId,
    'class_id' => $classId,
    'attendance_date' => $attendanceDate,
    'session' => $session,
];

if ($streamId === null) {
    $duplicateSql .= '
        AND stream_id IS NULL
    ';
} else {
    $duplicateSql .= '
        AND stream_id = :stream_id
    ';

    $duplicateParameters['stream_id'] = $streamId;
}

$duplicateSql .= ' LIMIT 1';

$duplicateStatement = $pdo->prepare($duplicateSql);
$duplicateStatement->execute($duplicateParameters);

if ($duplicateStatement->fetch()) {
    $_SESSION['error_message'] =
        'Attendance has already been recorded for that class, ' .
        'stream, date and session.';

    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $registerStatement = $pdo->prepare(
        'INSERT INTO attendance_registers (
            academic_year_id,
            term_id,
            class_id,
            stream_id,
            attendance_date,
            session,
            recorded_by,
            notes
        ) VALUES (
            :academic_year_id,
            :term_id,
            :class_id,
            :stream_id,
            :attendance_date,
            :session,
            :recorded_by,
            :notes
        )'
    );

    $registerStatement->execute([
        'academic_year_id' => $academicYearId,
        'term_id' => $termId,
        'class_id' => $classId,
        'stream_id' => $streamId,
        'attendance_date' => $attendanceDate,
        'session' => $session,
        'recorded_by' => (int) $_SESSION['user_id'],
        'notes' => $notes !== '' ? $notes : null,
    ]);

    $registerId = (int) $pdo->lastInsertId();

    $attendanceStatement = $pdo->prepare(
        'INSERT INTO pupil_attendance (
            attendance_register_id,
            pupil_id,
            attendance_status,
            remarks
        ) VALUES (
            :attendance_register_id,
            :pupil_id,
            :attendance_status,
            :remarks
        )'
    );

    foreach ($pupilIds as $pupilId) {
        $pupilId = (int) $pupilId;

        $status = $attendanceStatuses[$pupilId];

        $remark = trim(
            (string) ($remarks[$pupilId] ?? '')
        );

        $attendanceStatement->execute([
            'attendance_register_id' => $registerId,
            'pupil_id' => $pupilId,
            'attendance_status' => $status,
            'remarks' => $remark !== '' ? $remark : null,
        ]);
    }

    $pdo->commit();

    $_SESSION['success_message'] =
        'Attendance was recorded successfully.';

    header('Location: view.php?id=' . $registerId);
    exit;
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    exit(
        '<h2>Attendance database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}