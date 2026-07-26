<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$examinationId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$examinationId || $examinationId < 1) {
    $_SESSION['error_message'] =
        'Invalid examination selected.';

    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$statement = $pdo->prepare(
    'SELECT
        examinations.*,

        examination_types.type_name,
        examination_types.type_code,
        examination_types.default_weight,

        academic_years.year_name,
        terms.term_name,

        users.full_name AS created_by_name

     FROM examinations

     INNER JOIN examination_types
        ON examination_types.id =
            examinations.examination_type_id

     INNER JOIN academic_years
        ON academic_years.id =
            examinations.academic_year_id

     INNER JOIN terms
        ON terms.id =
            examinations.term_id

     INNER JOIN users
        ON users.id =
            examinations.created_by

     WHERE examinations.id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $examinationId,
]);

$examination = $statement->fetch();

if (!$examination) {
    $_SESSION['error_message'] =
        'The examination was not found.';

    header('Location: index.php');
    exit;
}

$classStatement = $pdo->prepare(
    'SELECT
        examination_classes.id,
        examination_classes.status,

        classes.class_name,
        classes.class_code,
        classes.class_level,

        streams.stream_name,

        COUNT(
            DISTINCT examination_subjects.id
        ) AS total_subjects,

        COUNT(
            DISTINCT pupil_marks.id
        ) AS total_marks

     FROM examination_classes

     INNER JOIN classes
        ON classes.id =
            examination_classes.class_id

     LEFT JOIN streams
        ON streams.id =
            examination_classes.stream_id

     LEFT JOIN examination_subjects
        ON examination_subjects.examination_class_id =
            examination_classes.id

     LEFT JOIN pupil_marks
        ON pupil_marks.examination_subject_id =
            examination_subjects.id

     WHERE examination_classes.examination_id =
        :examination_id

     GROUP BY examination_classes.id

     ORDER BY
        classes.class_level,
        streams.stream_name'
);

$classStatement->execute([
    'examination_id' => $examinationId,
]);

$examinationClasses = $classStatement->fetchAll();

$totalClasses = count($examinationClasses);

$totalSubjects = array_sum(
    array_map(
        static fn (array $row): int =>
            (int) $row['total_subjects'],
        $examinationClasses
    )
);

$totalMarks = array_sum(
    array_map(
        static fn (array $row): int =>
            (int) $row['total_marks'],
        $examinationClasses
    )
);

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
);

function examViewDate(mixed $date): string
{
    if (!$date) {
        return 'Not set';
    }

    $timestamp = strtotime((string) $date);

    return $timestamp
        ? date('d M Y', $timestamp)
        : htmlspecialchars((string) $date);
}

function examViewStatusClass(string $status): string
{
    return match ($status) {
        'Draft' => 'exam-status-draft',
        'Open' => 'exam-status-open',
        'Closed' => 'exam-status-closed',
        'Published' => 'exam-status-published',
        default => 'exam-status-default',
    };
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
        <?= htmlspecialchars(
            $examination['examination_name']
        ) ?>
        | Good Shepherd Primary School
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
                    EXAMINATION MANAGEMENT
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $examination['examination_name']
                    ) ?>
                </h2>

                <p>
                    <?= htmlspecialchars(
                        $examination['year_name']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $examination['term_name']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $examination['type_name']
                    ) ?>
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
                    href="setup-class.php?examination_id=<?= (int) $examination['id'] ?>"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-building-add me-2"></i>
                    Assign Class
                </a>

            </div>

        </section>

        <section class="examination-summary-banner">

            <div class="examination-summary-main">

                <span class="examination-summary-code">
                    <?= htmlspecialchars(
                        $examination['type_code']
                    ) ?>
                </span>

                <div>
                    <h3>
                        <?= htmlspecialchars(
                            $examination['examination_name']
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $examination['description']
                            ?: 'No description provided.'
                        ) ?>
                    </p>
                </div>

            </div>

            <div class="examination-summary-status">

                <small>Current Status</small>

                <span
                    class="exam-status-badge
                    <?= examViewStatusClass(
                        $examination['status']
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        $examination['status']
                    ) ?>
                </span>

            </div>

        </section>

        <section class="row g-4 my-1">

            <div class="col-sm-6 col-xl-3">
                <article class="attendance-summary-card">

                    <span class="attendance-summary-icon attendance-present">
                        <i class="bi bi-building-fill"></i>
                    </span>

                    <div>
                        <small>Assigned Classes</small>
                        <strong><?= $totalClasses ?></strong>
                    </div>

                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="attendance-summary-card">

                    <span class="attendance-summary-icon attendance-excused">
                        <i class="bi bi-book-fill"></i>
                    </span>

                    <div>
                        <small>Assigned Subjects</small>
                        <strong><?= $totalSubjects ?></strong>
                    </div>

                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="attendance-summary-card">

                    <span class="attendance-summary-icon attendance-late">
                        <i class="bi bi-pencil-square"></i>
                    </span>

                    <div>
                        <small>Marks Entered</small>
                        <strong><?= $totalMarks ?></strong>
                    </div>

                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="attendance-summary-card">

                    <span class="attendance-summary-icon attendance-absent">
                        <i class="bi bi-percent"></i>
                    </span>

                    <div>
                        <small>Default Weight</small>

                        <strong>
                            <?= number_format(
                                (float) $examination[
                                    'default_weight'
                                ],
                                0
                            ) ?>%
                        </strong>
                    </div>

                </article>
            </div>

        </section>

        <section class="profile-table-card my-4">

            <div class="profile-table-header">
                <i class="bi bi-info-circle-fill"></i>

                <div>
                    <h3>Examination Information</h3>
                    <p>Academic period and assessment details</p>
                </div>
            </div>

            <div class="table-responsive">

                <table class="table profile-information-table">
                    <tbody>

                        <tr>
                            <th>Examination Type</th>

                            <td>
                                <?= htmlspecialchars(
                                    $examination['type_name']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Academic Year</th>

                            <td>
                                <?= htmlspecialchars(
                                    $examination['year_name']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Term</th>

                            <td>
                                <?= htmlspecialchars(
                                    $examination['term_name']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Start Date</th>

                            <td>
                                <?= examViewDate(
                                    $examination['start_date']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>End Date</th>

                            <td>
                                <?= examViewDate(
                                    $examination['end_date']
                                ) ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Created By</th>

                            <td>
                                <?= htmlspecialchars(
                                    $examination[
                                        'created_by_name'
                                    ]
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
                    <h3>Assigned Classes</h3>

                    <p>
                        Classes and streams participating
                        in this examination
                    </p>
                </div>

                <a
                    href="setup-class.php?examination_id=<?= (int) $examination['id'] ?>"
                    class="btn btn-outline-success"
                >
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Class
                </a>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Subjects</th>
                            <th>Marks Entered</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($examinationClasses === []): ?>

                        <tr>
                            <td colspan="6">

                                <div class="empty-state">
                                    <i class="bi bi-building-x"></i>

                                    <strong>
                                        No classes assigned
                                    </strong>

                                    <span>
                                        Assign a class and its subjects
                                        before entering marks.
                                    </span>
                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $examinationClasses as $class
                        ): ?>

                            <tr>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $class['class_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $class['stream_name']
                                        ?? 'All streams'
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (int) $class[
                                            'total_subjects'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (int) $class[
                                            'total_marks'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <span class="status-badge status-active">
                                        <?= htmlspecialchars(
                                            $class['status']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="setup-class.php?examination_id=<?= (int) $examination['id'] ?>&examination_class_id=<?= (int) $class['id'] ?>"
                                        class="table-action-button"
                                        title="Manage subjects"
                                    >
                                        <i class="bi bi-gear"></i>
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <section class="profile-bottom-actions">

            <form
                action="change-status.php"
                method="POST"
                class="d-flex gap-2"
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
                    name="examination_id"
                    value="<?= (int) $examination['id'] ?>"
                >

                <select
                    name="status"
                    class="form-select professional-input"
                    required
                >
                    <?php foreach (
                        [
                            'Draft',
                            'Open',
                            'Closed',
                            'Published',
                        ] as $status
                    ): ?>

                        <option
                            value="<?= $status ?>"
                            <?= $examination['status'] === $status
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $status ?>
                        </option>

                    <?php endforeach; ?>
                </select>

                <button
                    type="submit"
                    class="btn btn-outline-success"
                >
                    Update Status
                </button>

            </form>

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