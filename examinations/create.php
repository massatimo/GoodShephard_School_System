<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$examinationTypes = $pdo->query(
    "SELECT
        id,
        type_name,
        type_code,
        default_weight
     FROM examination_types
     WHERE status = 'Active'
     ORDER BY type_name"
)->fetchAll();

$academicYears = $pdo->query(
    "SELECT
        id,
        year_name,
        is_current
     FROM academic_years
     WHERE status = 'Active'
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
     WHERE status = 'Active'
     ORDER BY academic_year_id DESC, id"
)->fetchAll();

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['old_input']);

function examOld(
    array $old,
    string $field,
    string $default = ''
): string {
    return htmlspecialchars(
        (string) ($old[$field] ?? $default)
    );
}

function examSelected(
    array $old,
    string $field,
    string|int $value
): string {
    return (string) ($old[$field] ?? '')
        === (string) $value
        ? 'selected'
        : '';
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
        Create Examination | Good Shepherd Primary School
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
                    ACADEMIC ASSESSMENT
                </span>

                <h2>Create Examination</h2>

                <p>
                    Define an examination for a specific academic
                    year and school term.
                </p>
            </div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Examinations
            </a>

        </section>

        <?php if ($errors !== []): ?>

            <div class="alert alert-danger">

                <strong>
                    Correct the following information:
                </strong>

                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>

            </div>

        <?php endif; ?>

        <form action="store.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token']
                ) ?>"
            >

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-clipboard-data-fill"></i>
                    </span>

                    <div>
                        <h3>Examination Information</h3>

                        <p>
                            Enter the assessment name and examination type
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-6">

                        <label class="form-label">
                            Examination Name *
                        </label>

                        <input
                            type="text"
                            name="examination_name"
                            class="form-control professional-input"
                            value="<?= examOld(
                                $old,
                                'examination_name'
                            ) ?>"
                            placeholder="Example: Term Two End of Term Examination"
                            required
                        >

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Examination Type *
                        </label>

                        <select
                            name="examination_type_id"
                            class="form-select professional-input"
                            required
                        >
                            <option value="">
                                Select examination type
                            </option>

                            <?php foreach (
                                $examinationTypes as $type
                            ): ?>

                                <option
                                    value="<?= (int) $type['id'] ?>"
                                    <?= examSelected(
                                        $old,
                                        'examination_type_id',
                                        $type['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $type['type_name']
                                    ) ?>

                                    (<?= htmlspecialchars(
                                        $type['type_code']
                                    ) ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </section>

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-calendar-event-fill"></i>
                    </span>

                    <div>
                        <h3>Academic Period</h3>

                        <p>
                            Select the academic year, term and exam dates
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-6">

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
                                    <?= examSelected(
                                        $old,
                                        'academic_year_id',
                                        $year['id']
                                    ) ?>
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

                    <div class="col-md-6">

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
                                    data-start-date="<?= htmlspecialchars(
                                        $term['start_date']
                                    ) ?>"
                                    data-end-date="<?= htmlspecialchars(
                                        $term['end_date']
                                    ) ?>"
                                    <?= examSelected(
                                        $old,
                                        'term_id',
                                        $term['id']
                                    ) ?>
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

                    <div class="col-md-6">

                        <label class="form-label">
                            Start Date
                        </label>

                        <input
                            type="date"
                            name="start_date"
                            id="startDate"
                            class="form-control professional-input"
                            value="<?= examOld(
                                $old,
                                'start_date'
                            ) ?>"
                        >

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            End Date
                        </label>

                        <input
                            type="date"
                            name="end_date"
                            id="endDate"
                            class="form-control professional-input"
                            value="<?= examOld(
                                $old,
                                'end_date'
                            ) ?>"
                        >

                    </div>

                </div>

            </section>

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-gear-fill"></i>
                    </span>

                    <div>
                        <h3>Status and Description</h3>

                        <p>
                            Set the examination’s initial status
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-4">

                        <label class="form-label">
                            Initial Status *
                        </label>

                        <select
                            name="status"
                            class="form-select professional-input"
                            required
                        >
                            <option
                                value="Draft"
                                <?= examSelected(
                                    $old,
                                    'status',
                                    'Draft'
                                ) ?>
                            >
                                Draft
                            </option>

                            <option
                                value="Open"
                                <?= examSelected(
                                    $old,
                                    'status',
                                    'Open'
                                ) ?>
                            >
                                Open
                            </option>
                        </select>

                        <small class="text-muted">
                            Draft examinations cannot yet receive marks.
                        </small>

                    </div>

                    <div class="col-md-8">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control professional-input"
                            rows="3"
                            placeholder="Optional examination description"
                        ><?= examOld(
                            $old,
                            'description'
                        ) ?></textarea>

                    </div>

                </div>

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
                    Create Examination
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
    const yearSelect =
        document.getElementById('academicYearId');

    const termSelect =
        document.getElementById('termId');

    const startDate =
        document.getElementById('startDate');

    const endDate =
        document.getElementById('endDate');

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

        const selectedOption =
            termSelect.options[termSelect.selectedIndex];

        if (
            selectedOption &&
            selectedOption.value !== '' &&
            selectedOption.dataset.yearId !== selectedYear
        ) {
            termSelect.value = '';
        }
    }

    function applyTermDates() {
        const option =
            termSelect.options[termSelect.selectedIndex];

        if (!option || option.value === '') {
            return;
        }

        if (!startDate.value) {
            startDate.value = option.dataset.startDate || '';
        }

        if (!endDate.value) {
            endDate.value = option.dataset.endDate || '';
        }
    }

    yearSelect.addEventListener('change', filterTerms);
    termSelect.addEventListener('change', applyTermDates);

    filterTerms();
});
</script>

</body>
</html>