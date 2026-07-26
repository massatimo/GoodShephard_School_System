<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

$attendanceDate =
    trim($_GET['attendance_date'] ?? date('Y-m-d'));

$session = trim($_GET['session'] ?? 'Morning');

$allowedSessions = [
    'Morning',
    'Afternoon',
];

if (!in_array($session, $allowedSessions, true)) {
    $session = 'Morning';
}

$academicYears = $pdo->query(
    "SELECT id, year_name, is_current
     FROM academic_years
     WHERE status = 'Active'
     ORDER BY year_name DESC"
)->fetchAll();

$terms = $pdo->query(
    "SELECT
        id,
        academic_year_id,
        term_name,
        is_current
     FROM terms
     WHERE status = 'Active'
     ORDER BY academic_year_id DESC, id"
)->fetchAll();

$classes = $pdo->query(
    "SELECT id, class_name
     FROM classes
     WHERE status = 'Active'
     ORDER BY class_level"
)->fetchAll();

$streams = $pdo->query(
    "SELECT id, class_id, stream_name
     FROM streams
     WHERE status = 'Active'
     ORDER BY stream_name"
)->fetchAll();

$pupils = [];

if ($classId) {
    $pupilSql = '
        SELECT
            id,
            admission_number,
            first_name,
            middle_name,
            last_name,
            gender
        FROM pupils
        WHERE class_id = :class_id
          AND pupil_status = "Active"
    ';

    $pupilParameters = [
        'class_id' => $classId,
    ];

    if ($streamId) {
        $pupilSql .= '
            AND stream_id = :stream_id
        ';

        $pupilParameters['stream_id'] = $streamId;
    }

    $pupilSql .= '
        ORDER BY first_name, middle_name, last_name
    ';

    $pupilStatement = $pdo->prepare($pupilSql);
    $pupilStatement->execute($pupilParameters);

    $pupils = $pupilStatement->fetchAll();
}

$errors = $_SESSION['form_errors'] ?? [];

unset($_SESSION['form_errors']);
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
        Take Attendance | Good Shepherd Primary School
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

                <h2>Take Attendance</h2>

                <p>
                    Select a class and mark each pupil's attendance.
                </p>
            </div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Attendance
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

        <section class="form-section-card">

            <div class="form-section-header">

                <span class="form-section-icon">
                    <i class="bi bi-funnel-fill"></i>
                </span>

                <div>
                    <h3>Select Attendance Register</h3>

                    <p>
                        Choose the academic period, class and session
                    </p>
                </div>

            </div>

            <form
                method="GET"
                action=""
                class="row g-4"
            >

                <div class="col-md-4">
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
                            Select academic year
                        </option>

                        <?php foreach ($academicYears as $year): ?>

                            <option
                                value="<?= (int) $year['id'] ?>"
                                <?= (int) $academicYearId
                                    === (int) $year['id']
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

                <div class="col-md-4">
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
                                <?= (int) $termId
                                    === (int) $term['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $term['term_name']
                                ) ?>

                                <?= (int) $term['is_current'] === 1
                                    ? ' (Current)'
                                    : '' ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        Attendance Date *
                    </label>

                    <input
                        type="date"
                        name="attendance_date"
                        class="form-control professional-input"
                        value="<?= htmlspecialchars(
                            $attendanceDate
                        ) ?>"
                        required
                    >
                </div>

                <div class="col-md-4">
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
                                <?= (int) $classId
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
                </div>

                <div class="col-md-4">
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
                                <?= (int) $streamId
                                    === (int) $stream['id']
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
                        Session *
                    </label>

                    <select
                        name="session"
                        class="form-select professional-input"
                        required
                    >
                        <option
                            value="Morning"
                            <?= $session === 'Morning'
                                ? 'selected'
                                : '' ?>
                        >
                            Morning
                        </option>

                        <option
                            value="Afternoon"
                            <?= $session === 'Afternoon'
                                ? 'selected'
                                : '' ?>
                        >
                            Afternoon
                        </option>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button
                        type="submit"
                        class="btn school-primary-btn module-action-button"
                    >
                        <i class="bi bi-people-fill me-2"></i>
                        Load Pupils
                    </button>
                </div>

            </form>

        </section>

        <?php if ($classId): ?>

            <form
                action="store.php"
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
                    name="academic_year_id"
                    value="<?= (int) $academicYearId ?>"
                >

                <input
                    type="hidden"
                    name="term_id"
                    value="<?= (int) $termId ?>"
                >

                <input
                    type="hidden"
                    name="class_id"
                    value="<?= (int) $classId ?>"
                >

                <input
                    type="hidden"
                    name="stream_id"
                    value="<?= $streamId
                        ? (int) $streamId
                        : '' ?>"
                >

                <input
                    type="hidden"
                    name="attendance_date"
                    value="<?= htmlspecialchars(
                        $attendanceDate
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="session"
                    value="<?= htmlspecialchars($session) ?>"
                >

                <section class="dashboard-card">

                    <div class="dashboard-card-header attendance-mark-header">

                        <div>
                            <h3>Pupil Attendance Register</h3>

                            <p>
                                <?= number_format(count($pupils)) ?>
                                active pupils loaded
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
                                    <th>Attendance Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($pupils === []): ?>

                                <tr>
                                    <td colspan="6">

                                        <div class="empty-state">
                                            <i class="bi bi-people"></i>

                                            <strong>
                                                No active pupils found
                                            </strong>

                                            <span>
                                                Confirm that pupils are
                                                assigned to this class and stream.
                                            </span>
                                        </div>

                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($pupils as $index => $pupil): ?>

                                    <?php
                                    $fullName = trim(
                                        $pupil['first_name'] . ' ' .
                                        ($pupil['middle_name'] ?? '') . ' ' .
                                        $pupil['last_name']
                                    );
                                    ?>

                                    <tr>

                                        <td>
                                            <?= $index + 1 ?>
                                        </td>

                                        <td class="fw-semibold">
                                            <?= htmlspecialchars(
                                                $pupil[
                                                    'admission_number'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($fullName) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $pupil['gender']
                                            ) ?>
                                        </td>

                                        <td>
                                            <input
                                                type="hidden"
                                                name="pupil_ids[]"
                                                value="<?= (int) $pupil['id'] ?>"
                                            >

                                            <select
                                                name="attendance_status[<?= (int) $pupil['id'] ?>]"
                                                class="form-select attendance-status-select"
                                            >
                                                <option value="Present">
                                                    Present
                                                </option>

                                                <option value="Absent">
                                                    Absent
                                                </option>

                                                <option value="Late">
                                                    Late
                                                </option>

                                                <option value="Excused">
                                                    Excused
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                name="remarks[<?= (int) $pupil['id'] ?>]"
                                                class="form-control attendance-remark-input"
                                                placeholder="Optional remark"
                                            >
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </section>

                <?php if ($pupils !== []): ?>

                    <section class="form-section-card mt-4">

                        <label class="form-label">
                            General Register Notes
                        </label>

                        <textarea
                            name="notes"
                            class="form-control professional-input"
                            rows="2"
                            placeholder="Optional general attendance note"
                        ></textarea>

                    </section>

                    <div class="form-submit-bar">

                        <a
                            href="index.php"
                            class="btn btn-light"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn school-primary-btn module-action-button"
                        >
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Save Attendance
                        </button>

                    </div>

                <?php endif; ?>

            </form>

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

        yearSelect.addEventListener('change', filterTerms);
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

        classSelect.addEventListener('change', filterStreams);
        filterStreams();
    }

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