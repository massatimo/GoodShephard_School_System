<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$academicYears = $pdo->query(
    'SELECT *
     FROM academic_years
     ORDER BY year_name DESC'
)->fetchAll();

$terms = $pdo->query(
    'SELECT
        terms.*,
        academic_years.year_name
     FROM terms
     INNER JOIN academic_years
        ON academic_years.id = terms.academic_year_id
     ORDER BY academic_years.year_name DESC, terms.id'
)->fetchAll();

$classes = $pdo->query(
    'SELECT *
     FROM classes
     ORDER BY class_level'
)->fetchAll();

$streams = $pdo->query(
    'SELECT
        streams.*,
        classes.class_name
     FROM streams
     INNER JOIN classes
        ON classes.id = streams.class_id
     ORDER BY classes.class_level, streams.stream_name'
)->fetchAll();

$subjects = $pdo->query(
    'SELECT *
     FROM subjects
     ORDER BY subject_name'
)->fetchAll();

$currentYear = $pdo->query(
    'SELECT year_name
     FROM academic_years
     WHERE is_current = 1
     LIMIT 1'
)->fetchColumn();

$currentTerm = $pdo->query(
    'SELECT terms.term_name
     FROM terms
     WHERE is_current = 1
     LIMIT 1'
)->fetchColumn();

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset($_SESSION['success_message'], $_SESSION['error_message']);
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
        Academic Structure | Good Shepherd Primary School
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
                    SCHOOL ACADEMICS
                </span>

                <h2>Academic Structure</h2>

                <p>
                    Manage academic years, terms, classes,
                    streams and subjects.
                </p>
            </div>
        </section>

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

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-green">
                    <div class="stat-card-icon">
                        <i class="bi bi-calendar3"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Current year</span>

                        <strong>
                            <?= htmlspecialchars(
                                $currentYear ?: 'Not set'
                            ) ?>
                        </strong>

                        <small>Active academic year</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-blue">
                    <div class="stat-card-icon">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Current term</span>

                        <strong>
                            <?= htmlspecialchars(
                                $currentTerm ?: 'Not set'
                            ) ?>
                        </strong>

                        <small>Active school term</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-gold">
                    <div class="stat-card-icon">
                        <i class="bi bi-building-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Classes</span>

                        <strong>
                            <?= number_format(count($classes)) ?>
                        </strong>

                        <small>Nursery and primary classes</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-purple">
                    <div class="stat-card-icon">
                        <i class="bi bi-book-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Subjects</span>

                        <strong>
                            <?= number_format(count($subjects)) ?>
                        </strong>

                        <small>Registered subjects</small>
                    </div>
                </article>
            </div>

        </section>

        <ul
            class="nav nav-pills academic-tabs mb-4"
            id="academicTabs"
            role="tablist"
        >
            <li class="nav-item">
                <button
                    class="nav-link active"
                    data-bs-toggle="pill"
                    data-bs-target="#years"
                    type="button"
                >
                    Academic Years
                </button>
            </li>

            <li class="nav-item">
                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#terms"
                    type="button"
                >
                    Terms
                </button>
            </li>

            <li class="nav-item">
                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#classes"
                    type="button"
                >
                    Classes & Streams
                </button>
            </li>

            <li class="nav-item">
                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#subjects"
                    type="button"
                >
                    Subjects
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ACADEMIC YEARS -->

            <div
                class="tab-pane fade show active"
                id="years"
            >
                <div class="row g-4">

                    <div class="col-xl-4">
                        <section class="form-section-card">
                            <div class="form-section-header">
                                <span class="form-section-icon">
                                    <i class="bi bi-calendar-plus"></i>
                                </span>

                                <div>
                                    <h3>Add Academic Year</h3>
                                    <p>Create a new school year</p>
                                </div>
                            </div>

                            <form
                                action="store_year.php"
                                method="POST"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    ) ?>"
                                >

                                <div class="mb-3">
                                    <label class="form-label">
                                        Year name
                                    </label>

                                    <input
                                        type="text"
                                        name="year_name"
                                        class="form-control professional-input"
                                        placeholder="Example: 2027"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Start date
                                    </label>

                                    <input
                                        type="date"
                                        name="start_date"
                                        class="form-control professional-input"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        End date
                                    </label>

                                    <input
                                        type="date"
                                        name="end_date"
                                        class="form-control professional-input"
                                        required
                                    >
                                </div>

                                <div class="form-check form-switch mb-4">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_current"
                                        value="1"
                                        id="currentYear"
                                    >

                                    <label
                                        class="form-check-label"
                                        for="currentYear"
                                    >
                                        Set as current academic year
                                    </label>
                                </div>

                                <button
                                    type="submit"
                                    class="btn school-primary-btn w-100"
                                >
                                    Save Academic Year
                                </button>
                            </form>
                        </section>
                    </div>

                    <div class="col-xl-8">
                        <section class="dashboard-card">
                            <div class="dashboard-card-header">
                                <div>
                                    <h3>Academic Years</h3>
                                    <p>All configured school years</p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Year</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Current</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php foreach ($academicYears as $year): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?= htmlspecialchars(
                                                    $year['year_name']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $year['start_date']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $year['end_date']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= (int) $year['is_current']
                                                    === 1
                                                    ? 'Yes'
                                                    : 'No' ?>
                                            </td>

                                            <td>
                                                <span class="status-badge status-active">
                                                    <?= htmlspecialchars(
                                                        $year['status']
                                                    ) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                </div>
            </div>

            <!-- TERMS -->

            <div
                class="tab-pane fade"
                id="terms"
            >
                <div class="row g-4">

                    <div class="col-xl-4">
                        <section class="form-section-card">
                            <div class="form-section-header">
                                <span class="form-section-icon">
                                    <i class="bi bi-calendar-event"></i>
                                </span>

                                <div>
                                    <h3>Add Term</h3>
                                    <p>Create a term under an academic year</p>
                                </div>
                            </div>

                            <form
                                action="store_term.php"
                                method="POST"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    ) ?>"
                                >

                                <div class="mb-3">
                                    <label class="form-label">
                                        Academic year
                                    </label>

                                    <select
                                        name="academic_year_id"
                                        class="form-select professional-input"
                                        required
                                    >
                                        <option value="">
                                            Select year
                                        </option>

                                        <?php foreach ($academicYears as $year): ?>
                                            <option
                                                value="<?= (int) $year['id'] ?>"
                                            >
                                                <?= htmlspecialchars(
                                                    $year['year_name']
                                                ) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Term name
                                    </label>

                                    <select
                                        name="term_name"
                                        class="form-select professional-input"
                                        required
                                    >
                                        <option value="">Select term</option>
                                        <option>Term One</option>
                                        <option>Term Two</option>
                                        <option>Term Three</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Start date
                                    </label>

                                    <input
                                        type="date"
                                        name="start_date"
                                        class="form-control professional-input"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        End date
                                    </label>

                                    <input
                                        type="date"
                                        name="end_date"
                                        class="form-control professional-input"
                                        required
                                    >
                                </div>

                                <div class="form-check form-switch mb-4">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_current"
                                        value="1"
                                        id="currentTerm"
                                    >

                                    <label
                                        class="form-check-label"
                                        for="currentTerm"
                                    >
                                        Set as current term
                                    </label>
                                </div>

                                <button
                                    type="submit"
                                    class="btn school-primary-btn w-100"
                                >
                                    Save Term
                                </button>
                            </form>
                        </section>
                    </div>

                    <div class="col-xl-8">
                        <section class="dashboard-card">
                            <div class="dashboard-card-header">
                                <div>
                                    <h3>School Terms</h3>
                                    <p>Terms configured under each year</p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Academic Year</th>
                                            <th>Term</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Current</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php foreach ($terms as $term): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars(
                                                    $term['year_name']
                                                ) ?>
                                            </td>

                                            <td class="fw-semibold">
                                                <?= htmlspecialchars(
                                                    $term['term_name']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $term['start_date']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $term['end_date']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= (int) $term['is_current']
                                                    === 1
                                                    ? 'Yes'
                                                    : 'No' ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $term['status']
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                </div>
            </div>

            <!-- CLASSES AND STREAMS -->

            <div
                class="tab-pane fade"
                id="classes"
            >
                <div class="row g-4">

                    <div class="col-xl-6">
                        <section class="dashboard-card">
                            <div class="dashboard-card-header">
                                <div>
                                    <h3>Classes</h3>
                                    <p>Nursery and primary levels</p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Code</th>
                                            <th>Level</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?= htmlspecialchars(
                                                    $class['class_name']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $class['class_code']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= (int) $class['class_level'] ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $class['status']
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="col-xl-6">
                        <section class="dashboard-card">
                            <div class="dashboard-card-header">
                                <div>
                                    <h3>Streams</h3>
                                    <p>Streams assigned to classes</p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Stream</th>
                                            <th>Code</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php foreach ($streams as $stream): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars(
                                                    $stream['class_name']
                                                ) ?>
                                            </td>

                                            <td class="fw-semibold">
                                                <?= htmlspecialchars(
                                                    $stream['stream_name']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $stream['stream_code']
                                                    ?? '—'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $stream['status']
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                </div>
            </div>

            <!-- SUBJECTS -->

            <div
                class="tab-pane fade"
                id="subjects"
            >
                <div class="row g-4">

                    <div class="col-xl-4">
                        <section class="form-section-card">
                            <div class="form-section-header">
                                <span class="form-section-icon">
                                    <i class="bi bi-book-half"></i>
                                </span>

                                <div>
                                    <h3>Add Subject</h3>
                                    <p>Create a school subject</p>
                                </div>
                            </div>

                            <form
                                action="store_subject.php"
                                method="POST"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    ) ?>"
                                >

                                <div class="mb-3">
                                    <label class="form-label">
                                        Subject name
                                    </label>

                                    <input
                                        type="text"
                                        name="subject_name"
                                        class="form-control professional-input"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Subject code
                                    </label>

                                    <input
                                        type="text"
                                        name="subject_code"
                                        class="form-control professional-input"
                                        placeholder="Example: ENG"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Category
                                    </label>

                                    <select
                                        name="subject_category"
                                        class="form-select professional-input"
                                        required
                                    >
                                        <option>Core</option>
                                        <option>Optional</option>
                                        <option>Co-curricular</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        Applicable level
                                    </label>

                                    <select
                                        name="applicable_level"
                                        class="form-select professional-input"
                                        required
                                    >
                                        <option>All</option>
                                        <option>Nursery</option>
                                        <option>Lower Primary</option>
                                        <option>Upper Primary</option>
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    class="btn school-primary-btn w-100"
                                >
                                    Save Subject
                                </button>
                            </form>
                        </section>
                    </div>

                    <div class="col-xl-8">
                        <section class="dashboard-card">
                            <div class="dashboard-card-header">
                                <div>
                                    <h3>Subjects</h3>
                                    <p>Subjects currently offered by the school</p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Code</th>
                                            <th>Category</th>
                                            <th>Level</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?= htmlspecialchars(
                                                    $subject['subject_name']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $subject['subject_code']
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $subject[
                                                        'subject_category'
                                                    ]
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $subject[
                                                        'applicable_level'
                                                    ]
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $subject['status']
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                </div>
            </div>

        </div>

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