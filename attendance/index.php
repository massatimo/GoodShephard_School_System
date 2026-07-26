<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$searchDate = trim($_GET['attendance_date'] ?? '');
$classFilter = filter_input(
    INPUT_GET,
    'class_id',
    FILTER_VALIDATE_INT
);

$sql = '
    SELECT
        attendance_registers.id,
        attendance_registers.attendance_date,
        attendance_registers.session,
        attendance_registers.notes,
        attendance_registers.created_at,

        academic_years.year_name,
        terms.term_name,
        classes.class_name,
        streams.stream_name,

        users.full_name AS recorded_by_name,

        COUNT(pupil_attendance.id) AS total_pupils,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status = "Present"
                THEN 1
                ELSE 0
            END
        ) AS total_present,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status = "Absent"
                THEN 1
                ELSE 0
            END
        ) AS total_absent,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status = "Late"
                THEN 1
                ELSE 0
            END
        ) AS total_late,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status = "Excused"
                THEN 1
                ELSE 0
            END
        ) AS total_excused

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

    LEFT JOIN pupil_attendance
        ON pupil_attendance.attendance_register_id =
            attendance_registers.id

    WHERE 1 = 1
';

$parameters = [];

if ($searchDate !== '') {
    $sql .= '
        AND attendance_registers.attendance_date =
            :attendance_date
    ';

    $parameters['attendance_date'] = $searchDate;
}

if ($classFilter) {
    $sql .= '
        AND attendance_registers.class_id = :class_id
    ';

    $parameters['class_id'] = $classFilter;
}

$sql .= '
    GROUP BY attendance_registers.id
    ORDER BY
        attendance_registers.attendance_date DESC,
        attendance_registers.session,
        classes.class_level,
        streams.stream_name
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$registers = $statement->fetchAll();

$classes = $pdo->query(
    "SELECT id, class_name
     FROM classes
     WHERE status = 'Active'
     ORDER BY class_level"
)->fetchAll();

$todayRegistersStatement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM attendance_registers
     WHERE attendance_date = :today'
);

$todayRegistersStatement->execute([
    'today' => date('Y-m-d'),
]);

$todayRegisters = (int) $todayRegistersStatement->fetchColumn();

$todayPresentStatement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM pupil_attendance
     INNER JOIN attendance_registers
        ON attendance_registers.id =
            pupil_attendance.attendance_register_id
     WHERE attendance_registers.attendance_date = :today
       AND pupil_attendance.attendance_status = "Present"'
);

$todayPresentStatement->execute([
    'today' => date('Y-m-d'),
]);

$todayPresent = (int) $todayPresentStatement->fetchColumn();

$todayAbsentStatement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM pupil_attendance
     INNER JOIN attendance_registers
        ON attendance_registers.id =
            pupil_attendance.attendance_register_id
     WHERE attendance_registers.attendance_date = :today
       AND pupil_attendance.attendance_status = "Absent"'
);

$todayAbsentStatement->execute([
    'today' => date('Y-m-d'),
]);

$todayAbsent = (int) $todayAbsentStatement->fetchColumn();

$todayLateStatement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM pupil_attendance
     INNER JOIN attendance_registers
        ON attendance_registers.id =
            pupil_attendance.attendance_register_id
     WHERE attendance_registers.attendance_date = :today
       AND pupil_attendance.attendance_status = "Late"'
);

$todayLateStatement->execute([
    'today' => date('Y-m-d'),
]);

$todayLate = (int) $todayLateStatement->fetchColumn();

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
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
        Attendance | Good Shepherd Primary School
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

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>

                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <section class="module-heading">

            <div>
                <span class="module-label">
                    PUPIL ATTENDANCE
                </span>

                <h2>Attendance Management</h2>

                <p>
                    Record and review daily pupil attendance.
                </p>
            </div>

            <a
                href="take.php"
                class="btn school-primary-btn module-action-button"
            >
                <i class="bi bi-clipboard-check-fill me-2"></i>
                Take Attendance
            </a>

        </section>

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-blue">
                    <div class="stat-card-icon">
                        <i class="bi bi-journal-check"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Today's Registers</span>

                        <strong>
                            <?= number_format($todayRegisters) ?>
                        </strong>

                        <small>
                            Attendance registers recorded
                        </small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-green">
                    <div class="stat-card-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Present Today</span>

                        <strong>
                            <?= number_format($todayPresent) ?>
                        </strong>

                        <small>
                            Pupils marked present
                        </small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-gold">
                    <div class="stat-card-icon">
                        <i class="bi bi-person-x-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Absent Today</span>

                        <strong>
                            <?= number_format($todayAbsent) ?>
                        </strong>

                        <small>
                            Pupils marked absent
                        </small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-purple">
                    <div class="stat-card-icon">
                        <i class="bi bi-clock-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Late Today</span>

                        <strong>
                            <?= number_format($todayLate) ?>
                        </strong>

                        <small>
                            Pupils marked late
                        </small>
                    </div>
                </article>
            </div>

        </section>

        <section class="dashboard-card">

            <div class="dashboard-card-header attendance-list-header">

                <div>
                    <h3>Attendance Registers</h3>

                    <p>
                        Previously recorded class attendance
                    </p>
                </div>

                <form
                    method="GET"
                    action=""
                    class="attendance-filter-form"
                >
                    <input
                        type="date"
                        name="attendance_date"
                        value="<?= htmlspecialchars($searchDate) ?>"
                        class="form-control professional-input"
                    >

                    <select
                        name="class_id"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All classes
                        </option>

                        <?php foreach ($classes as $class): ?>
                            <option
                                value="<?= (int) $class['id'] ?>"
                                <?= (int) $classFilter
                                    === (int) $class['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $class['class_name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button
                        type="submit"
                        class="btn btn-outline-success"
                    >
                        Filter
                    </button>
                </form>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Session</th>
                            <th>Total</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Excused</th>
                            <th>Recorded By</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($registers === []): ?>

                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <i class="bi bi-calendar-check"></i>

                                    <strong>
                                        No attendance records found
                                    </strong>

                                    <span>
                                        Take attendance to create
                                        the first register.
                                    </span>
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($registers as $register): ?>

                            <tr>
                                <td class="fw-semibold">
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

                                <td>
                                    <?= htmlspecialchars(
                                        $register['class_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $register['stream_name']
                                        ?? 'All streams'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="attendance-session-badge">
                                        <?= htmlspecialchars(
                                            $register['session']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= (int) $register['total_pupils'] ?>
                                </td>

                                <td class="attendance-present-text">
                                    <?= (int) $register['total_present'] ?>
                                </td>

                                <td class="attendance-absent-text">
                                    <?= (int) $register['total_absent'] ?>
                                </td>

                                <td class="attendance-late-text">
                                    <?= (int) $register['total_late'] ?>
                                </td>

                                <td>
                                    <?= (int) $register['total_excused'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $register[
                                            'recorded_by_name'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="view.php?id=<?= (int) $register['id'] ?>"
                                        class="table-action-button"
                                        title="View register"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>
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