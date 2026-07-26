<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$registerId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$registerId || $registerId < 1) {
    $_SESSION['error_message'] =
        'Invalid attendance register selected.';

    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Retrieve attendance register
|--------------------------------------------------------------------------
*/

$registerStatement = $pdo->prepare(
    'SELECT
        attendance_registers.*,
        academic_years.year_name,
        terms.term_name,
        classes.class_name,
        streams.stream_name
     FROM attendance_registers
     INNER JOIN academic_years
        ON academic_years.id =
            attendance_registers.academic_year_id
     INNER JOIN terms
        ON terms.id =
            attendance_registers.term_id
     INNER JOIN classes
        ON classes.id =
            attendance_registers.class_id
     LEFT JOIN streams
        ON streams.id =
            attendance_registers.stream_id
     WHERE attendance_registers.id = :id
     LIMIT 1'
);

$registerStatement->execute([
    'id' => $registerId,
]);

$register = $registerStatement->fetch();

if (!$register) {
    $_SESSION['error_message'] =
        'The attendance register was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve pupil attendance records
|--------------------------------------------------------------------------
*/

$pupilStatement = $pdo->prepare(
    'SELECT
        pupil_attendance.id AS attendance_id,
        pupil_attendance.pupil_id,
        pupil_attendance.attendance_status,
        pupil_attendance.remarks,

        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender

     FROM pupil_attendance

     INNER JOIN pupils
        ON pupils.id = pupil_attendance.pupil_id

     WHERE pupil_attendance.attendance_register_id =
        :register_id

     ORDER BY
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name'
);

$pupilStatement->execute([
    'register_id' => $registerId,
]);

$attendanceRecords = $pupilStatement->fetchAll();

$errors = $_SESSION['form_errors'] ?? [];
$oldStatuses = $_SESSION['old_statuses'] ?? [];
$oldRemarks = $_SESSION['old_remarks'] ?? [];

unset(
    $_SESSION['form_errors'],
    $_SESSION['old_statuses'],
    $_SESSION['old_remarks']
);

$allowedStatuses = [
    'Present',
    'Absent',
    'Late',
    'Excused',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Edit Attendance | Good Shepherd Primary School
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="../assets/css/style.css"
        rel="stylesheet"
    >
</head>

<body class="dashboard-page">

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="app-main" id="appMain">

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="app-content">

        <section class="module-heading">

            <div>
                <span class="module-label">
                    PUPIL ATTENDANCE
                </span>

                <h2>Edit Attendance Register</h2>

                <p>
                    <?= htmlspecialchars($register['class_name']) ?>

                    <?= !empty($register['stream_name'])
                        ? ' – ' .
                            htmlspecialchars($register['stream_name'])
                        : '' ?>

                    · <?= htmlspecialchars(
                        date(
                            'd M Y',
                            strtotime($register['attendance_date'])
                        )
                    ) ?>

                    · <?= htmlspecialchars($register['session']) ?>
                </p>
            </div>

            <a
                href="view.php?id=<?= (int) $register['id'] ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Register
            </a>

        </section>

        <?php if ($errors !== []): ?>

            <div class="alert alert-danger">

                <strong>
                    Correct the following:
                </strong>

                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>

            </div>

        <?php endif; ?>

        <section class="profile-table-card mb-4">

            <div class="profile-table-header">
                <i class="bi bi-info-circle-fill"></i>

                <div>
                    <h3>Register Information</h3>
                    <p>Academic period and session details</p>
                </div>
            </div>

            <div class="table-responsive">

                <table class="table profile-information-table">

                    <tbody>
                        <tr>
                            <th>Academic Year</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register['year_name']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Term</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register['term_name']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Class</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register['class_name']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Stream</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register['stream_name']
                                    ?? 'All streams'
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Date</th>
                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'd M Y',
                                        strtotime(
                                            $register['attendance_date']
                                        )
                                    )
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Session</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register['session']
                                ) ?>
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>

        <form
            action="update.php"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token']
                ) ?>"
            >

            <input
                type="hidden"
                name="register_id"
                value="<?= (int) $register['id'] ?>"
            >

            <section class="dashboard-card">

                <div class="dashboard-card-header attendance-mark-header">

                    <div>
                        <h3>Update Pupil Attendance</h3>

                        <p>
                            <?= number_format(
                                count($attendanceRecords)
                            ) ?>
                            pupils recorded
                        </p>
                    </div>

                    <div class="attendance-quick-actions">

                        <button
                            type="button"
                            class="btn btn-outline-success btn-sm"
                            id="markAllPresent"
                        >
                            Mark All Present
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            id="markAllAbsent"
                        >
                            Mark All Absent
                        </button>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table dashboard-table align-middle">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Admission Number</th>
                                <th>Pupil Name</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach (
                            $attendanceRecords as $index => $record
                        ): ?>

                            <?php
                            $pupilId = (int) $record['pupil_id'];

                            $fullName = trim(
                                $record['first_name'] . ' ' .
                                ($record['middle_name'] ?? '') . ' ' .
                                $record['last_name']
                            );

                            $currentStatus =
                                $oldStatuses[$pupilId]
                                ?? $record['attendance_status'];

                            $currentRemark =
                                $oldRemarks[$pupilId]
                                ?? $record['remarks']
                                ?? '';
                            ?>

                            <tr>

                                <td><?= $index + 1 ?></td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $record['admission_number']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($fullName) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $record['gender']
                                    ) ?>
                                </td>

                                <td>

                                    <select
                                        name="attendance_status[<?= $pupilId ?>]"
                                        class="form-select attendance-status-select"
                                        required
                                    >

                                        <?php foreach (
                                            $allowedStatuses as $status
                                        ): ?>

                                            <option
                                                value="<?= htmlspecialchars(
                                                    $status
                                                ) ?>"
                                                <?= $currentStatus === $status
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= htmlspecialchars(
                                                    $status
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                                <td>

                                    <input
                                        type="text"
                                        name="remarks[<?= $pupilId ?>]"
                                        class="form-control attendance-remark-input"
                                        value="<?= htmlspecialchars(
                                            (string) $currentRemark
                                        ) ?>"
                                        placeholder="Optional remark"
                                    >

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </section>

            <section class="form-section-card mt-4">

                <label class="form-label">
                    General Register Notes
                </label>

                <textarea
                    name="notes"
                    class="form-control professional-input"
                    rows="2"
                ><?= htmlspecialchars(
                    (string) ($register['notes'] ?? '')
                ) ?></textarea>

            </section>

            <div class="form-submit-bar">

                <a
                    href="view.php?id=<?= (int) $register['id'] ?>"
                    class="btn btn-light"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const statusSelects = document.querySelectorAll(
        '.attendance-status-select'
    );

    document
        .getElementById('markAllPresent')
        ?.addEventListener('click', () => {
            statusSelects.forEach((select) => {
                select.value = 'Present';
            });
        });

    document
        .getElementById('markAllAbsent')
        ?.addEventListener('click', () => {
            statusSelects.forEach((select) => {
                select.value = 'Absent';
            });
        });
});
</script>

</body>
</html>