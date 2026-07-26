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

$registerStatement = $pdo->prepare(
    'SELECT
        attendance_registers.*,
        academic_years.year_name,
        terms.term_name,
        classes.class_name,
        streams.stream_name,
        users.full_name AS recorded_by_name
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
     INNER JOIN users
        ON users.id =
            attendance_registers.recorded_by
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

$pupilStatement = $pdo->prepare(
    'SELECT
        pupil_attendance.attendance_status,
        pupil_attendance.remarks,

        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender

     FROM pupil_attendance

     INNER JOIN pupils
        ON pupils.id =
            pupil_attendance.pupil_id

     WHERE pupil_attendance.attendance_register_id =
        :attendance_register_id

     ORDER BY
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name'
);

$pupilStatement->execute([
    'attendance_register_id' => $registerId,
]);

$attendanceRecords = $pupilStatement->fetchAll();

$summary = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0,
];

foreach ($attendanceRecords as $record) {
    $status = $record['attendance_status'];

    if (isset($summary[$status])) {
        $summary[$status]++;
    }
}
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
        Attendance Register | Good Shepherd Primary School
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
                    ATTENDANCE REGISTER
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $register['class_name']
                    ) ?>

                    <?= !empty($register['stream_name'])
                        ? ' - ' .
                            htmlspecialchars(
                                $register['stream_name']
                            )
                        : '' ?>
                </h2>

                <p>
                    <?= htmlspecialchars(
                        date(
                            'd M Y',
                            strtotime(
                                $register['attendance_date']
                            )
                        )
                    ) ?>

                    · <?= htmlspecialchars(
                        $register['session']
                    ) ?>

                    Session
                </p>
            </div>

            <div class="profile-heading-actions">

    <a
        href="index.php"
        class="btn btn-outline-secondary"
    >
        <i class="bi bi-arrow-left me-2"></i>
        Back
    </a>

    <a
        href="edit.php?id=<?= (int) $register['id'] ?>"
        class="btn btn-outline-success"
    >
        <i class="bi bi-pencil-square me-2"></i>
        Edit Register
    </a>

    <a
        href="print.php?id=<?= (int) $register['id'] ?>"
        target="_blank"
        class="btn school-primary-btn module-action-button"
    >
        <i class="bi bi-printer-fill me-2"></i>
        Print Register
    </a>

</div>

        </section>
        <?php
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill me-2"></i>

        <?= htmlspecialchars($successMessage) ?>
    </div>

<?php endif; ?>

        <section class="row g-4 mb-4">

            <?php foreach ($summary as $status => $total): ?>

                <div class="col-sm-6 col-xl-3">

                    <article class="attendance-summary-card">

                        <span
                            class="attendance-summary-icon
                            attendance-<?= strtolower(
                                $status
                            ) ?>"
                        >
                            <i class="bi bi-person-fill"></i>
                        </span>

                        <div>
                            <small>
                                <?= htmlspecialchars($status) ?>
                            </small>

                            <strong>
                                <?= number_format($total) ?>
                            </strong>
                        </div>

                    </article>

                </div>

            <?php endforeach; ?>

        </section>

        <section class="profile-table-card mb-4">

            <div class="profile-table-header">
                <i class="bi bi-info-circle-fill"></i>

                <div>
                    <h3>Register Information</h3>

                    <p>
                        Academic period and recording details
                    </p>
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
                            <th>Attendance Date</th>
                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'd M Y',
                                        strtotime(
                                            $register[
                                                'attendance_date'
                                            ]
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

                        <tr>
                            <th>Recorded By</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register[
                                        'recorded_by_name'
                                    ]
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Notes</th>
                            <td>
                                <?= htmlspecialchars(
                                    $register['notes']
                                    ?: 'No notes'
                                ) ?>
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>

        <section class="dashboard-card">

            <div class="dashboard-card-header">

                <div>
                    <h3>Pupil Attendance</h3>

                    <p>
                        <?= number_format(
                            count($attendanceRecords)
                        ) ?>
                        pupils recorded
                    </p>
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
                        $fullName = trim(
                            $record['first_name'] . ' ' .
                            ($record['middle_name'] ?? '') . ' ' .
                            $record['last_name']
                        );
                        ?>

                        <tr>

                            <td><?= $index + 1 ?></td>

                            <td class="fw-semibold">
                                <?= htmlspecialchars(
                                    $record[
                                        'admission_number'
                                    ]
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
                                <span
                                    class="attendance-status-badge
                                    attendance-status-<?= strtolower(
                                        $record[
                                            'attendance_status'
                                        ]
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $record[
                                            'attendance_status'
                                        ]
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $record['remarks']
                                    ?: '—'
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/app.js"></script>

</body>
</html>