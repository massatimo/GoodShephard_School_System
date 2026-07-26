<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Retrieve teaching staff
|--------------------------------------------------------------------------
*/

$teachers = $pdo->query(
    "SELECT
        id,
        staff_number,
        first_name,
        middle_name,
        last_name,
        full_name,
        designation
     FROM staff
     WHERE staff_category = 'Teaching Staff'
       AND employment_status = 'Active'
     ORDER BY first_name, last_name"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Retrieve academic structures
|--------------------------------------------------------------------------
*/

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
        is_current
     FROM terms
     WHERE status = 'Active'
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

$subjects = $pdo->query(
    "SELECT
        id,
        subject_name,
        subject_code,
        applicable_level
     FROM subjects
     WHERE status = 'Active'
     ORDER BY subject_name"
)->fetchAll();

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['old_input']);

function allocationOld(
    array $old,
    string $field,
    string $default = ''
): string {
    return htmlspecialchars(
        (string) ($old[$field] ?? $default)
    );
}

function allocationSelected(
    array $old,
    string $field,
    string|int $value
): string {
    return (string) ($old[$field] ?? '')
        === (string) $value
        ? 'selected'
        : '';
}

function teacherOptionName(array $teacher): string
{
    $name = trim(
        ($teacher['first_name'] ?? '') . ' ' .
        ($teacher['middle_name'] ?? '') . ' ' .
        ($teacher['last_name'] ?? '')
    );

    if ($name !== '') {
        return $name;
    }

    return trim(
        (string) ($teacher['full_name'] ?? 'Unknown Teacher')
    );
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
        New Teacher Allocation | Good Shepherd Primary School
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
                    ACADEMIC MANAGEMENT
                </span>

                <h2>New Teacher Allocation</h2>

                <p>
                    Assign a teacher to a class, stream and subject.
                </p>
            </div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Allocations
            </a>

        </section>

        <?php if ($errors !== []): ?>

            <div class="alert alert-danger">

                <strong>
                    Correct the following information:
                </strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <?php if ($teachers === []): ?>

            <div class="alert alert-warning">

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                No active teaching staff have been registered.

                <a
                    href="../staff/create.php"
                    class="alert-link"
                >
                    Register a teacher first.
                </a>

            </div>

        <?php endif; ?>

        <form
            action="store.php"
            method="POST"
            class="teacher-allocation-form"
        >

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
                        <i class="bi bi-person-video3"></i>
                    </span>

                    <div>
                        <h3>Teacher and Academic Period</h3>

                        <p>
                            Select the teacher, academic year and term
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-6">

                        <label class="form-label">
                            Teacher *
                        </label>

                        <select
                            name="staff_id"
                            class="form-select professional-input"
                            required
                        >
                            <option value="">
                                Select teacher
                            </option>

                            <?php foreach ($teachers as $teacher): ?>

                                <?php
                                $teacherName =
                                    teacherOptionName($teacher);
                                ?>

                                <option
                                    value="<?= (int) $teacher['id'] ?>"
                                    <?= allocationSelected(
                                        $old,
                                        'staff_id',
                                        $teacher['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $teacherName
                                    ) ?>

                                    — <?= htmlspecialchars(
                                        $teacher['staff_number']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Academic year *
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
                                    <?= allocationSelected(
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
                                    <?= allocationSelected(
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

                </div>

            </section>

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-building-fill"></i>
                    </span>

                    <div>
                        <h3>Class and Stream</h3>

                        <p>
                            Select where the teacher will be assigned
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-6">

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
                                    <?= allocationSelected(
                                        $old,
                                        'class_id',
                                        $class['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $class['class_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Stream
                        </label>

                        <select
                            name="stream_id"
                            id="streamId"
                            class="form-select professional-input"
                        >
                            <option value="">
                                All streams / No stream
                            </option>

                            <?php foreach ($streams as $stream): ?>

                                <option
                                    value="<?= (int) $stream['id'] ?>"
                                    data-class-id="<?= (int) $stream[
                                        'class_id'
                                    ] ?>"
                                    <?= allocationSelected(
                                        $old,
                                        'stream_id',
                                        $stream['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $stream['stream_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </section>

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-book-fill"></i>
                    </span>

                    <div>
                        <h3>Subject and Responsibility</h3>

                        <p>
                            Select a subject or assign class-teacher duty
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-8">

                        <label class="form-label">
                            Subject
                        </label>

                        <select
                            name="subject_id"
                            class="form-select professional-input"
                        >
                            <option value="">
                                No subject — class teacher only
                            </option>

                            <?php foreach ($subjects as $subject): ?>

                                <option
                                    value="<?= (int) $subject['id'] ?>"
                                    <?= allocationSelected(
                                        $old,
                                        'subject_id',
                                        $subject['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $subject['subject_name']
                                    ) ?>

                                    (<?= htmlspecialchars(
                                        $subject['subject_code']
                                    ) ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="text-muted">
                            Leave empty when assigning only
                            class-teacher responsibility.
                        </small>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label d-block">
                            Class Teacher Responsibility
                        </label>

                        <div class="allocation-switch-box">

                            <div class="form-check form-switch">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_class_teacher"
                                    id="isClassTeacher"
                                    value="1"
                                    <?= isset(
                                        $old['is_class_teacher']
                                    ) ? 'checked' : '' ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="isClassTeacher"
                                >
                                    Assign as class teacher
                                </label>

                            </div>

                            <small>
                                The teacher will be responsible for
                                the selected class and stream.
                            </small>

                        </div>

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
                    <?= $teachers === [] ? 'disabled' : '' ?>
                >
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Save Allocation
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
    const academicYearSelect =
        document.getElementById('academicYearId');

    const termSelect =
        document.getElementById('termId');

    const classSelect =
        document.getElementById('classId');

    const streamSelect =
        document.getElementById('streamId');

    if (academicYearSelect && termSelect) {
        const termOptions = Array.from(termSelect.options);

        function filterTerms() {
            const selectedYear = academicYearSelect.value;

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

        academicYearSelect.addEventListener(
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

            const selectedOption =
                streamSelect.options[streamSelect.selectedIndex];

            if (
                selectedOption &&
                selectedOption.value !== '' &&
                selectedOption.dataset.classId !== selectedClass
            ) {
                streamSelect.value = '';
            }
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