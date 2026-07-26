<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$academicYearFilter = filter_input(
    INPUT_GET,
    'academic_year_id',
    FILTER_VALIDATE_INT
);

$termFilter = filter_input(
    INPUT_GET,
    'term_id',
    FILTER_VALIDATE_INT
);

$statusFilter = trim($_GET['status'] ?? '');

$allowedStatuses = [
    'Draft',
    'Open',
    'Closed',
    'Published',
];

$sql = '
    SELECT
        examinations.id,
        examinations.examination_name,
        examinations.start_date,
        examinations.end_date,
        examinations.status,
        examinations.description,
        examinations.created_at,

        examination_types.type_name,
        examination_types.type_code,

        academic_years.year_name,
        terms.term_name,

        users.full_name AS created_by_name,

        COUNT(
            DISTINCT examination_classes.id
        ) AS total_classes,

        COUNT(
            DISTINCT examination_subjects.id
        ) AS total_subjects,

        COUNT(
            DISTINCT pupil_marks.id
        ) AS total_marks

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

    LEFT JOIN examination_classes
        ON examination_classes.examination_id =
            examinations.id

    LEFT JOIN examination_subjects
        ON examination_subjects.examination_class_id =
            examination_classes.id

    LEFT JOIN pupil_marks
        ON pupil_marks.examination_subject_id =
            examination_subjects.id

    WHERE 1 = 1
';

$parameters = [];

if ($academicYearFilter) {
    $sql .= '
        AND examinations.academic_year_id =
            :academic_year_id
    ';

    $parameters['academic_year_id'] =
        $academicYearFilter;
}

if ($termFilter) {
    $sql .= '
        AND examinations.term_id = :term_id
    ';

    $parameters['term_id'] = $termFilter;
}

if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= '
        AND examinations.status = :status
    ';

    $parameters['status'] = $statusFilter;
}

$sql .= '
    GROUP BY examinations.id

    ORDER BY
        academic_years.year_name DESC,
        terms.id DESC,
        examinations.start_date DESC,
        examinations.id DESC
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$examinations = $statement->fetchAll();

$academicYears = $pdo->query(
    'SELECT
        id,
        year_name,
        is_current
     FROM academic_years
     ORDER BY year_name DESC'
)->fetchAll();

$terms = $pdo->query(
    'SELECT
        id,
        academic_year_id,
        term_name,
        is_current
     FROM terms
     ORDER BY academic_year_id DESC, id'
)->fetchAll();

$totalExaminations = (int) $pdo
    ->query('SELECT COUNT(*) FROM examinations')
    ->fetchColumn();

$openExaminations = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM examinations
         WHERE status = 'Open'"
    )
    ->fetchColumn();

$publishedExaminations = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM examinations
         WHERE status = 'Published'"
    )
    ->fetchColumn();

$totalMarks = (int) $pdo
    ->query('SELECT COUNT(*) FROM pupil_marks')
    ->fetchColumn();

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
);

function examinationStatusClass(string $status): string
{
    return match ($status) {
        'Draft' => 'exam-status-draft',
        'Open' => 'exam-status-open',
        'Closed' => 'exam-status-closed',
        'Published' => 'exam-status-published',
        default => 'exam-status-default',
    };
}

function examinationDate(
    mixed $date,
    string $fallback = 'Not set'
): string {
    if ($date === null || trim((string) $date) === '') {
        return $fallback;
    }

    $timestamp = strtotime((string) $date);

    return $timestamp
        ? date('d M Y', $timestamp)
        : htmlspecialchars((string) $date);
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
        Examinations | Good Shepherd Primary School
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
                    ACADEMIC ASSESSMENT
                </span>

                <h2>Examinations</h2>

                <p>
                    Create examinations, assign classes and manage marks.
                </p>
            </div>

            <a
                href="create.php"
                class="btn school-primary-btn module-action-button"
            >
                <i class="bi bi-plus-circle-fill me-2"></i>
                Create Examination
            </a>

        </section>

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-blue">

                    <div class="stat-card-icon">
                        <i class="bi bi-clipboard-data-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Total Examinations</span>

                        <strong>
                            <?= number_format($totalExaminations) ?>
                        </strong>

                        <small>
                            All examination records
                        </small>
                    </div>

                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-green">

                    <div class="stat-card-icon">
                        <i class="bi bi-unlock-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Open Examinations</span>

                        <strong>
                            <?= number_format($openExaminations) ?>
                        </strong>

                        <small>
                            Available for marks entry
                        </small>
                    </div>

                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-gold">

                    <div class="stat-card-icon">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Published</span>

                        <strong>
                            <?= number_format(
                                $publishedExaminations
                            ) ?>
                        </strong>

                        <small>
                            Results available
                        </small>
                    </div>

                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-purple">

                    <div class="stat-card-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Marks Entered</span>

                        <strong>
                            <?= number_format($totalMarks) ?>
                        </strong>

                        <small>
                            Individual pupil marks
                        </small>
                    </div>

                </article>
            </div>

        </section>

        <section class="dashboard-card">

            <div class="dashboard-card-header examination-list-header">

                <div>
                    <h3>Examination Register</h3>

                    <p>
                        All configured school examinations
                    </p>
                </div>

                <form
                    method="GET"
                    action=""
                    class="examination-filter-form"
                >

                    <select
                        name="academic_year_id"
                        id="academicYearId"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All academic years
                        </option>

                        <?php foreach ($academicYears as $year): ?>
                            <option
                                value="<?= (int) $year['id'] ?>"
                                <?= (int) $academicYearFilter ===
                                    (int) $year['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $year['year_name']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select
                        name="term_id"
                        id="termId"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All terms
                        </option>

                        <?php foreach ($terms as $term): ?>
                            <option
                                value="<?= (int) $term['id'] ?>"
                                data-year-id="<?= (int) $term[
                                    'academic_year_id'
                                ] ?>"
                                <?= (int) $termFilter ===
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

                    <select
                        name="status"
                        class="form-select professional-input"
                    >
                        <option value="">All statuses</option>

                        <?php foreach ($allowedStatuses as $status): ?>
                            <option
                                value="<?= htmlspecialchars($status) ?>"
                                <?= $statusFilter === $status
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($status) ?>
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
                            <th>Examination</th>
                            <th>Type</th>
                            <th>Academic Period</th>
                            <th>Dates</th>
                            <th>Classes</th>
                            <th>Subjects</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($examinations === []): ?>

                        <tr>
                            <td colspan="9">

                                <div class="empty-state">
                                    <i class="bi bi-clipboard-x"></i>

                                    <strong>
                                        No examinations found
                                    </strong>

                                    <span>
                                        Create the first examination
                                        to begin assessment management.
                                    </span>
                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($examinations as $exam): ?>

                            <tr>

                                <td>
                                    <div class="examination-name-cell">

                                        <span class="examination-code">
                                            <?= htmlspecialchars(
                                                $exam['type_code']
                                            ) ?>
                                        </span>

                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $exam[
                                                        'examination_name'
                                                    ]
                                                ) ?>
                                            </strong>

                                            <small>
                                                Created by
                                                <?= htmlspecialchars(
                                                    $exam[
                                                        'created_by_name'
                                                    ]
                                                ) ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $exam['type_name']
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $exam['year_name']
                                        ) ?>
                                    </strong>

                                    <small class="d-block text-muted">
                                        <?= htmlspecialchars(
                                            $exam['term_name']
                                        ) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= examinationDate(
                                        $exam['start_date']
                                    ) ?>

                                    <small class="d-block text-muted">
                                        to
                                        <?= examinationDate(
                                            $exam['end_date']
                                        ) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= number_format(
                                        (int) $exam['total_classes']
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (int) $exam['total_subjects']
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (int) $exam['total_marks']
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="exam-status-badge
                                        <?= examinationStatusClass(
                                            $exam['status']
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $exam['status']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="view.php?id=<?= (int) $exam['id'] ?>"
                                        class="table-action-button"
                                        title="Manage examination"
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const yearSelect =
        document.getElementById('academicYearId');

    const termSelect =
        document.getElementById('termId');

    if (!yearSelect || !termSelect) {
        return;
    }

    const termOptions = Array.from(termSelect.options);

    function filterTerms() {
        const selectedYear = yearSelect.value;

        termOptions.forEach((option) => {
            if (option.value === '') {
                option.hidden = false;
                return;
            }

            option.hidden =
                selectedYear !== '' &&
                option.dataset.yearId !== selectedYear;
        });

        const selectedOption =
            termSelect.options[termSelect.selectedIndex];

        if (
            selectedOption &&
            selectedOption.value !== '' &&
            selectedYear !== '' &&
            selectedOption.dataset.yearId !== selectedYear
        ) {
            termSelect.value = '';
        }
    }

    yearSelect.addEventListener('change', filterTerms);
    filterTerms();
});
</script>

</body>
</html>