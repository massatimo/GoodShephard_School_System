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

$registerId = filter_var(
    $_POST['register_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$registerId || $registerId < 1) {
    $_SESSION['error_message'] =
        'Invalid attendance register selected.';

    header('Location: index.php');
    exit;
}

$registerStatement = $pdo->prepare(
    'SELECT id
     FROM attendance_registers
     WHERE id = :id
     LIMIT 1'
);

$registerStatement->execute([
    'id' => $registerId,
]);

if (!$registerStatement->fetch()) {
    $_SESSION['error_message'] =
        'The attendance register was not found.';

    header('Location: index.php');
    exit;
}

$statuses = $_POST['attendance_status'] ?? [];
$remarks = $_POST['remarks'] ?? [];
$notes = trim($_POST['notes'] ?? '');

$allowedStatuses = [
    'Present',
    'Absent',
    'Late',
    'Excused',
];

$errors = [];

if (!is_array($statuses) || $statuses === []) {
    $errors[] = 'No attendance information was submitted.';
}

foreach ($statuses as $pupilId => $status) {
    if (
        filter_var($pupilId, FILTER_VALIDATE_INT) === false ||
        !in_array($status, $allowedStatuses, true)
    ) {
        $errors[] =
            'One or more attendance records are invalid.';

        break;
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_statuses'] = $statuses;
    $_SESSION['old_remarks'] = $remarks;

    header('Location: edit.php?id=' . $registerId);
    exit;
}

try {
    $pdo->beginTransaction();

    $updateRegister = $pdo->prepare(
        'UPDATE attendance_registers
         SET
            notes = :notes,
            updated_at = CURRENT_TIMESTAMP
         WHERE id = :id'
    );

    $updateRegister->execute([
        'notes' => $notes !== '' ? $notes : null,
        'id' => $registerId,
    ]);

    $updateAttendance = $pdo->prepare(
        'UPDATE pupil_attendance
         SET
            attendance_status = :attendance_status,
            remarks = :remarks,
            updated_at = CURRENT_TIMESTAMP
         WHERE attendance_register_id = :register_id
           AND pupil_id = :pupil_id'
    );

    foreach ($statuses as $pupilId => $status) {
        $pupilId = (int) $pupilId;

        $remark = trim(
            (string) ($remarks[$pupilId] ?? '')
        );

        $updateAttendance->execute([
            'attendance_status' => $status,
            'remarks' => $remark !== '' ? $remark : null,
            'register_id' => $registerId,
            'pupil_id' => $pupilId,
        ]);
    }

    $pdo->commit();

    $_SESSION['success_message'] =
        'The attendance register was updated successfully.';

    header('Location: view.php?id=' . $registerId);
    exit;
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    exit(
        '<h2>Attendance update error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}