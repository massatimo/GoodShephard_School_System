<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Read report filters
|--------------------------------------------------------------------------
*/

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

$classId = filter_input(
    INPUT_GET,
    'class_id',
    FILTER_VALIDATE_INT
);

$streamId = filter_input(
    INPUT_GET,
    'stream_id',
    FILTER_VALIDATE_INT
);

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

/*
|--------------------------------------------------------------------------
| Load academic structures
|--------------------------------------------------------------------------
*/

$academicYears = $pdo->query(
    "SELECT
        id,
        year_name,
        is_current
     FROM academic_years
     ORDER BY year_name DESC"
)->fetchAll();

$terms = $pdo->query(
    "SELECT
        id,
        academic_year_id,
        term_name,
        start_date,
        end_date,
        is_current
     FROM terms
     ORDER BY academic_year_id DESC, id"
)->fetchAll();

$classes = $pdo->query(
    "SELECT
        id,
        class_name,
        class_level
     FROM classes
     WHERE status = 'Active'
     ORDER BY class_level"
)->fetchAll();

$streams = $pdo->query(
    "SELECT
        id,
        class_id,
        stream_name
     FROM streams
     WHERE status = 'Active'
     ORDER BY stream_name"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Automatically select current year and term
|--------------------------------------------------------------------------
*/

if (!$academicYearId) {
    foreach ($academicYears as $year) {
        if ((int) $year['is_current'] === 1) {
            $academicYearId = (int) $year['id'];
            break;
        }
    }
}

if (!$termId) {
    foreach ($terms as $term) {
        if (
            (int) $term['is_current'] === 1 &&
            (int) $term['academic_year_id'] ===
                (int) $academicYearId
        ) {
            $termId = (int) $term['id'];

            if ($dateFrom === '') {
                $dateFrom = $term['start_date'];
            }

            if ($dateTo === '') {
                $dateTo = min(
                    $term['end_date'],
                    date('Y-m-d')
                );
            }

            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Validate dates
|--------------------------------------------------------------------------
*/

if ($dateFrom === '') {
    $dateFrom = date('Y-m-01');
}

if ($dateTo === '') {
    $dateTo = date('Y-m-d');
}

/*
|--------------------------------------------------------------------------
| Generate pupil attendance report
|--------------------------------------------------------------------------
*/

$reportRows = [];
$totalRegisters = 0;

if ($academicYearId && $termId && $classId) {
    $registerCountSql = '
        SELECT COUNT(*)
        FROM attendance_registers
        WHERE academic_year_id = :academic_year_id
          AND term_id = :term_id
          AND class_id = :class_id
          AND attendance_date BETWEEN :date_from AND :date_to
    ';

    $registerCountParameters = [
        'academic_year_id' => $academicYearId,
        'term_id' => $termId,
        'class_id' => $classId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ];

    if ($streamId) {
        $registerCountSql .= '
            AND stream_id = :stream_id
        ';

        $registerCountParameters['stream_id'] = $streamId;
    }

    $registerCountStatement =
        $pdo->prepare($registerCountSql);

    $registerCountStatement->execute(
        $registerCountParameters
    );

    $totalRegisters =
        (int) $registerCountStatement->fetchColumn();

    $reportSql = '
        SELECT
            pupils.id,
            pupils.admission_number,
            pupils.first_name,
            pupils.middle_name,
            pupils.last_name,
            pupils.gender,

            COUNT(pupil_attendance.id) AS attendance_entries,

            SUM(
                CASE
                    WHEN pupil_attendance.attendance_status =
                        "Present"
                    THEN 1
                    ELSE 0
                END
            ) AS total_present,

            SUM(
                CASE
                    WHEN pupil_attendance.attendance_status =
                        "Absent"
                    THEN 1
                    ELSE 0
                END
            ) AS total_absent,

            SUM(
                CASE
                    WHEN pupil_attendance.attendance_status =
                        "Late"
                    THEN 1
                    ELSE 0
                END
            ) AS total_late,

            SUM(
                CASE
                    WHEN pupil_attendance.attendance_status =
                        "Excused"
                    THEN 1
                    ELSE 0
                END
            ) AS total_excused

        FROM pupils

        LEFT JOIN pupil_attendance
            ON pupil_attendance.pupil_id = pupils.id

        LEFT JOIN attendance_registers
            ON attendance_registers.id =
                pupil_attendance.attendance_register_id
           AND attendance_registers.academic_year_id =
                :academic_year_id
           AND attendance_registers.term_id = :term_id
           AND attendance_registers.class_id = :class_id
           AND attendance_registers.attendance_date
                BETWEEN :date_from AND :date_to
    ';

    $reportParameters = [
        'academic_year_id' => $academicYearId,
        'term_id' => $termId,
        'class_id' => $classId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ];

    if ($streamId) {
        $reportSql .= '
            AND attendance_registers.stream_id = :register_stream_id
        ';

        $reportParameters['register_stream_id'] =
            $streamId;
    }

    $reportSql .= '
        WHERE pupils.class_id = :pupil_class_id
          AND pupils.pupil_status = "Active"
    ';

    $reportParameters['pupil_class_id'] = $classId;

    if ($streamId) {
        $reportSql .= '
            AND pupils.stream_id = :pupil_stream_id
        ';

        $reportParameters['pupil_stream_id'] =
            $streamId;
    }

    $reportSql .= '
        GROUP BY pupils.id
        ORDER BY
            pupils.first_name,
            pupils.middle_name,
            pupils.last_name
    ';

    $reportStatement = $pdo->prepare($reportSql);

    $reportStatement->execute($reportParameters);

    $reportRows = $reportStatement->fetchAll();
}

/*
|--------------------------------------------------------------------------
| Calculate overall totals
|--------------------------------------------------------------------------
*/

$overallPresent = 0;
$overallAbsent = 0;
$overallLate = 0;
$overallExcused = 0;
$totalAttendanceEntries = 0;

foreach ($reportRows as &$row) {
    $present = (int) $row['total_present'];
    $absent = (int) $row['total_absent'];
    $late = (int) $row['total_late'];
    $excused = (int) $row['total_excused'];

    $recordedEntries =
        $present + $absent + $late + $excused;

    $attendancePercentage =
        $recordedEntries > 0
            ? (($present + $late) / $recordedEntries) * 100
            : 0;

    $row['attendance_percentage'] =
        round($attendancePercentage, 1);

    $overallPresent += $present;
    $overallAbsent += $absent;
    $overallLate += $late;
    $overallExcused += $excused;
    $totalAttendanceEntries += $recordedEntries;
}

unset($row);

$overallPercentage =
    $totalAttendanceEntries > 0
        ? (($overallPresent + $overallLate) /
            $totalAttendanceEntries) * 100
        : 0;

/*
|--------------------------------------------------------------------------
| Selected labels
|--------------------------------------------------------------------------
*/

$selectedClassName = 'Not selected';
$selectedStreamName = 'All streams';
$selectedYearName = 'Not selected';
$selectedTermName = 'Not selected';

foreach ($classes as $class) {
    if ((int) $class['id'] === (int) $classId) {
        $selectedClassName = $class['class_name'];
        break;
    }
}

foreach ($streams as $stream) {
    if ((int) $stream['id'] === (int) $streamId) {
        $selectedStreamName = $stream['stream_name'];
        break;
    }
}

foreach ($academicYears as $year) {
    if (
        (int) $year['id'] ===
        (int) $academicYearId
    ) {
        $selectedYearName = $year['year_name'];
        break;
    }
}

foreach ($terms as $term) {
    if ((int) $term['id'] === (int) $termId) {
        $selectedTermName = $term['term_name'];
        break;
    }
}

function attendancePercentageClass(
    float $percentage
): string {
    if ($percentage >= 90) {
        return 'attendance-rate-excellent';
    }

    if ($percentage >= 75) {
        return 'attendance-rate-good';
    }

    if ($percentage >= 60) {
        return 'attendance-rate-warning';
    }

    return 'attendance-rate-poor';
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
        Attendance Reports | Good Shepherd Primary School
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
                    ATTENDANCE ANALYTICS
                </span>

                <h2>Attendance Reports</h2>

                <p>
                    Review pupil attendance totals, percentages
                    and class performance.
                </p>
            </div>

            <?php if ($classId): ?>

                <a
                    href="print-report.php?<?= htmlspecialchars(
                        http_build_query([
                            'academic_year_id' =>
                                $academicYearId,
                            'term_id' => $termId,
                            'class_id' => $classId,
                            'stream_id' => $streamId,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                        ])
                    ) ?>"
                    target="_blank"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-printer-fill me-2"></i>
                    Print Report
                </a>

            <?php endif; ?>

        </section>

        <!-- FILTERS -->

        <section class="form-section-card">

            <div class="form-section-header">

                <span class="form-section-icon">
                    <i class="bi bi-funnel-fill"></i>
                </span>

                <div>
                    <h3>Report Filters</h3>

                    <p>
                        Select the class and reporting period
                    </p>
                </div>

            </div>

            <form method="GET" action="" class="row g-4">

                <div class="col-md-3">

                    <label class="form-label">
                        Academic Year *
                    </label>

                    <select
                        name="academic_year_id"
                        id="academicYearId"
                        class="form-select professional-input"
                        required
                    >
                        <option value="">
                            Select year
                        </option>

                        <?php foreach ($academicYears as $year): ?>

                            <option
                                value="<?= (int) $year['id'] ?>"
                                <?= (int) $academicYearId ===
                                    (int) $year['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $year['year_name']
                                ) ?>

                                <?= (int) $year['is_current'] === 1
                                    ? ' (Current)'
                                    : '' ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Term *
                    </label>

                    <select
                        name="term_id"
                        id="termId"
                        class="form-select professional-input"
                        required
                    >
                        <option value="">
                            Select term
                        </option>

                        <?php foreach ($terms as $term): ?>

                            <option
                                value="<?= (int) $term['id'] ?>"
                                data-year-id="<?= (int) $term[
                                    'academic_year_id'
                                ] ?>"
                                <?= (int) $termId ===
                                    (int) $term['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $term['term_name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Class *
                    </label>

                    <select
                        name="class_id"
                        id="classId"
                        class="form-select professional-input"
                        required
                    >
                        <option value="">
                            Select class
                        </option>

                        <?php foreach ($classes as $class): ?>

                            <option
                                value="<?= (int) $class['id'] ?>"
                                <?= (int) $classId ===
                                    (int) $class['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $class['class_name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Stream
                    </label>

                    <select
                        name="stream_id"
                        id="streamId"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All streams
                        </option>

                        <?php foreach ($streams as $stream): ?>

                            <option
                                value="<?= (int) $stream['id'] ?>"
                                data-class-id="<?= (int) $stream[
                                    'class_id'
                                ] ?>"
                                <?= (int) $streamId ===
                                    (int) $stream['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $stream['stream_name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Date From *
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        class="form-control professional-input"
                        value="<?= htmlspecialchars($dateFrom) ?>"
                        required
                    >

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Date To *
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        class="form-control professional-input"
                        value="<?= htmlspecialchars($dateTo) ?>"
                        required
                    >

                </div>

                <div class="col-md-4 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn school-primary-btn w-100"
                    >
                        <i class="bi bi-bar-chart-fill me-2"></i>
                        Generate Report
                    </button>

                </div>

            </form>

        </section>

        <?php if ($classId): ?>

            <!-- REPORT HEADING -->

            <section class="attendance-report-heading">

                <div>
                    <span>Attendance Report</span>

                    <h3>
                        <?= htmlspecialchars($selectedClassName) ?>

                        <?= $selectedStreamName !== 'All streams'
                            ? ' – ' .
                                htmlspecialchars(
                                    $selectedStreamName
                                )
                            : '' ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($selectedYearName) ?>
                        ·
                        <?= htmlspecialchars($selectedTermName) ?>
                        ·
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

                <div class="attendance-register-count">
                    <small>Registers Recorded</small>

                    <strong>
                        <?= number_format($totalRegisters) ?>
                    </strong>
                </div>

            </section>

            <!-- SUMMARY CARDS -->

            <section class="row g-4 mb-4">

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-present">
                            <i class="bi bi-person-check-fill"></i>
                        </span>

                        <div>
                            <small>Present</small>

                            <strong>
                                <?= number_format(
                                    $overallPresent
                                ) ?>
                            </strong>
                        </div>

                    </article>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-absent">
                            <i class="bi bi-person-x-fill"></i>
                        </span>

                        <div>
                            <small>Absent</small>

                            <strong>
                                <?= number_format(
                                    $overallAbsent
                                ) ?>
                            </strong>
                        </div>

                    </article>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-late">
                            <i class="bi bi-clock-fill"></i>
                        </span>

                        <div>
                            <small>Late</small>

                            <strong>
                                <?= number_format(
                                    $overallLate
                                ) ?>
                            </strong>
                        </div>

                    </article>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-excused">
                            <i class="bi bi-shield-check"></i>
                        </span>

                        <div>
                            <small>Attendance Rate</small>

                            <strong>
                                <?= number_format(
                                    $overallPercentage,
                                    1
                                ) ?>%
                            </strong>
                        </div>

                    </article>
                </div>

            </section>

            <!-- PUPIL REPORT TABLE -->

            <section class="dashboard-card">

                <div class="dashboard-card-header">

                    <div>
                        <h3>Pupil Attendance Summary</h3>

                        <p>
                            <?= number_format(count($reportRows)) ?>
                            active pupils
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
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Excused</th>
                                <th>Attendance Rate</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($reportRows === []): ?>

                            <tr>
                                <td colspan="10">

                                    <div class="empty-state">
                                        <i class="bi bi-bar-chart"></i>

                                        <strong>
                                            No attendance information
                                        </strong>

                                        <span>
                                            No pupils or attendance records
                                            matched the selected filters.
                                        </span>
                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $reportRows as $index => $row
                            ): ?>

                                <?php
                                $fullName = trim(
                                    $row['first_name'] . ' ' .
                                    ($row['middle_name'] ?? '') . ' ' .
                                    $row['last_name']
                                );

                                $percentage =
                                    (float) $row[
                                        'attendance_percentage'
                                    ];
                                ?>

                                <tr>

                                    <td><?= $index + 1 ?></td>

                                    <td class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $row[
                                                'admission_number'
                                            ]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $fullName
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['gender']
                                        ) ?>
                                    </td>

                                    <td class="attendance-present-text">
                                        <?= (int) $row[
                                            'total_present'
                                        ] ?>
                                    </td>

                                    <td class="attendance-absent-text">
                                        <?= (int) $row[
                                            'total_absent'
                                        ] ?>
                                    </td>

                                    <td class="attendance-late-text">
                                        <?= (int) $row[
                                            'total_late'
                                        ] ?>
                                    </td>

                                    <td>
                                        <?= (int) $row[
                                            'total_excused'
                                        ] ?>
                                    </td>

                                    <td>
                                        <span
                                            class="attendance-rate-badge
                                            <?= attendancePercentageClass(
                                                $percentage
                                            ) ?>"
                                        >
                                            <?= number_format(
                                                $percentage,
                                                1
                                            ) ?>%
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            href="pupil-report.php?<?= htmlspecialchars(
                                                http_build_query([
                                                    'pupil_id' =>
                                                        $row['id'],
                                                    'academic_year_id' =>
                                                        $academicYearId,
                                                    'term_id' =>
                                                        $termId,
                                                    'date_from' =>
                                                        $dateFrom,
                                                    'date_to' =>
                                                        $dateTo,
                                                ])
                                            ) ?>"
                                            class="table-action-button"
                                            title="View pupil attendance"
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

        <?php endif; ?>

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
    const yearSelect =
        document.getElementById('academicYearId');

    const termSelect =
        document.getElementById('termId');

    const classSelect =
        document.getElementById('classId');

    const streamSelect =
        document.getElementById('streamId');

    if (yearSelect && termSelect) {
        const termOptions = Array.from(termSelect.options);

        function filterTerms() {
            const selectedYear = yearSelect.value;

            termOptions.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden =
                    option.dataset.yearId !== selectedYear;
            });
        }

        yearSelect.addEventListener(
            'change',
            filterTerms
        );

        filterTerms();
    }

    if (classSelect && streamSelect) {
        const streamOptions = Array.from(streamSelect.options);

        function filterStreams() {
            const selectedClass = classSelect.value;

            streamOptions.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden =
                    option.dataset.classId !== selectedClass;
            });
        }

        classSelect.addEventListener(
            'change',
            filterStreams
        );

        filterStreams();
    }
});
</script>

</body>
</html>