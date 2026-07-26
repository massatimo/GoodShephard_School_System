<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$pupilId = filter_input(
    INPUT_GET,
    'pupil_id',
    FILTER_VALIDATE_INT
);

$academicYearId = filter_input(
    INPUT_GET,
    'academic_year_id',
    FILTER_VALIDATE_INT
);

$termId = filter_input(
    INPUT_GET,
    'term_id',
    FILTER_VALIDATE_INT
);

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

if (!$pupilId || !$academicYearId || !$termId) {
    header('Location: reports.php');
    exit;
}

$pupilStatement = $pdo->prepare(
    'SELECT
        pupils.*,
        classes.class_name,
        streams.stream_name
     FROM pupils
     LEFT JOIN classes
        ON classes.id = pupils.class_id
     LEFT JOIN streams
        ON streams.id = pupils.stream_id
     WHERE pupils.id = :id
     LIMIT 1'
);

$pupilStatement->execute([
    'id' => $pupilId,
]);

$pupil = $pupilStatement->fetch();

if (!$pupil) {
    exit('Pupil not found.');
}

$attendanceStatement = $pdo->prepare(
    'SELECT
        attendance_registers.attendance_date,
        attendance_registers.session,
        attendance_registers.notes,

        pupil_attendance.attendance_status,
        pupil_attendance.remarks,

        academic_years.year_name,
        terms.term_name

     FROM pupil_attendance

     INNER JOIN attendance_registers
        ON attendance_registers.id =
            pupil_attendance.attendance_register_id

     INNER JOIN academic_years
        ON academic_years.id =
            attendance_registers.academic_year_id

     INNER JOIN terms
        ON terms.id =
            attendance_registers.term_id

     WHERE pupil_attendance.pupil_id = :pupil_id
       AND attendance_registers.academic_year_id =
            :academic_year_id
       AND attendance_registers.term_id = :term_id
       AND attendance_registers.attendance_date
            BETWEEN :date_from AND :date_to

     ORDER BY
        attendance_registers.attendance_date DESC,
        attendance_registers.session'
);

$attendanceStatement->execute([
    'pupil_id' => $pupilId,
    'academic_year_id' => $academicYearId,
    'term_id' => $termId,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
]);

$records = $attendanceStatement->fetchAll();

$summary = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0,
];

foreach ($records as $record) {
    $status = $record['attendance_status'];

    if (isset($summary[$status])) {
        $summary[$status]++;
    }
}

$totalRecords = count($records);

$attendanceRate =
    $totalRecords > 0
        ? (($summary['Present'] + $summary['Late']) /
            $totalRecords) * 100
        : 0;

$fullName = trim(
    ($pupil['first_name'] ?? '') . ' ' .
    ($pupil['middle_name'] ?? '') . ' ' .
    ($pupil['last_name'] ?? '')
);
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
        Pupil Attendance Report | Good Shepherd Primary School
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
                    INDIVIDUAL ATTENDANCE
                </span>

                <h2><?= htmlspecialchars($fullName) ?></h2>

                <p>
                    <?= htmlspecialchars(
                        $pupil['admission_number']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $pupil['class_name']
                        ?? 'No class'
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $pupil['stream_name']
                        ?? 'No stream'
                    ) ?>
                </p>
            </div>

            <a
                href="javascript:history.back()"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Report
            </a>

        </section>

        <section class="row g-4 mb-4">

            <?php foreach ($summary as $status => $total): ?>

                <div class="col-sm-6 col-xl-3">

                    <article class="attendance-summary-card">

                        <span
                            class="attendance-summary-icon
                            attendance-<?= strtolower($status) ?>"
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

        <section class="attendance-rate-card">

            <div>
                <span>Overall Attendance Rate</span>

                <strong>
                    <?= number_format(
                        $attendanceRate,
                        1
                    ) ?>%
                </strong>
            </div>

            <div class="attendance-rate-progress">
                <span
                    style="width:
                    <?= min(100, $attendanceRate) ?>%;"
                ></span>
            </div>

            <small>
                Present and late attendance entries are counted
                as attended sessions.
            </small>

        </section>

        <section class="dashboard-card mt-4">

            <div class="dashboard-card-header">

                <div>
                    <h3>Attendance History</h3>

                    <p>
                        <?= htmlspecialchars(
                            date(
                                'd M Y',
                                strtotime($dateFrom)
                            )
                        ) ?>
                        to
                        <?= htmlspecialchars(
                            date(
                                'd M Y',
                                strtotime($dateTo)
                            )
                        ) ?>
                    </p>
                </div>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Session</th>
                            <th>Status</th>
                            <th>Pupil Remark</th>
                            <th>Register Note</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($records === []): ?>

                        <tr>
                            <td colspan="6">

                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>

                                    <strong>
                                        No attendance records
                                    </strong>

                                    <span>
                                        No attendance was found for
                                        this reporting period.
                                    </span>
                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($records as $index => $record): ?>

                            <tr>

                                <td><?= $index + 1 ?></td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $record[
                                                    'attendance_date'
                                                ]
                                            )
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $record['session']
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

                                <td>
                                    <?= htmlspecialchars(
                                        $record['notes']
                                        ?: '—'
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

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