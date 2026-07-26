<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT
        teacher_allocations.id,
        teacher_allocations.is_class_teacher,
        teacher_allocations.status,

        staff.staff_number,
        staff.first_name,
        staff.middle_name,
        staff.last_name,
        staff.full_name,
        staff.designation,

        academic_years.year_name,
        terms.term_name,

        classes.class_name,
        streams.stream_name,

        subjects.subject_name,
        subjects.subject_code

    FROM teacher_allocations

    INNER JOIN staff
        ON staff.id = teacher_allocations.staff_id

    INNER JOIN academic_years
        ON academic_years.id =
            teacher_allocations.academic_year_id

    INNER JOIN terms
        ON terms.id = teacher_allocations.term_id

    INNER JOIN classes
        ON classes.id = teacher_allocations.class_id

    LEFT JOIN streams
        ON streams.id = teacher_allocations.stream_id

    LEFT JOIN subjects
        ON subjects.id = teacher_allocations.subject_id

    WHERE 1 = 1
';

$parameters = [];

if ($search !== '') {
    $sql .= '
        AND (
            staff.first_name LIKE :search
            OR staff.middle_name LIKE :search
            OR staff.last_name LIKE :search
            OR staff.full_name LIKE :search
            OR staff.staff_number LIKE :search
            OR classes.class_name LIKE :search
            OR streams.stream_name LIKE :search
            OR subjects.subject_name LIKE :search
        )
    ';

    $parameters['search'] = '%' . $search . '%';
}

$sql .= '
    ORDER BY
        academic_years.year_name DESC,
        terms.id DESC,
        classes.class_level,
        streams.stream_name,
        staff.first_name
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$allocations = $statement->fetchAll();

$totalAllocations = (int) $pdo
    ->query(
        'SELECT COUNT(*)
         FROM teacher_allocations'
    )
    ->fetchColumn();

$totalClassTeachers = (int) $pdo
    ->query(
        'SELECT COUNT(*)
         FROM teacher_allocations
         WHERE is_class_teacher = 1'
    )
    ->fetchColumn();

$totalTeachersAllocated = (int) $pdo
    ->query(
        'SELECT COUNT(DISTINCT staff_id)
         FROM teacher_allocations'
    )
    ->fetchColumn();

$totalSubjectsAllocated = (int) $pdo
    ->query(
        'SELECT COUNT(*)
         FROM teacher_allocations
         WHERE subject_id IS NOT NULL'
    )
    ->fetchColumn();

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
);

function allocationTeacherName(array $allocation): string
{
    $name = trim(
        ($allocation['first_name'] ?? '') . ' ' .
        ($allocation['middle_name'] ?? '') . ' ' .
        ($allocation['last_name'] ?? '')
    );

    if ($name !== '') {
        return $name;
    }

    return trim(
        (string) (
            $allocation['full_name']
            ?? 'Unknown Teacher'
        )
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
        Teacher Allocation | Good Shepherd Primary School
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
                    ACADEMIC MANAGEMENT
                </span>

                <h2>Teacher Allocation</h2>

                <p>
                    Assign teachers to classes, streams and subjects.
                </p>
            </div>

            <a
                href="create.php"
                class="btn school-primary-btn module-action-button"
            >
                <i class="bi bi-person-plus-fill me-2"></i>
                New Allocation
            </a>

        </section>

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">

                <article class="stat-card stat-card-blue">

                    <div class="stat-card-icon">
                        <i class="bi bi-list-check"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Total Allocations</span>

                        <strong>
                            <?= number_format($totalAllocations) ?>
                        </strong>

                        <small>
                            All teacher assignments
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
                        <span>Allocated Teachers</span>

                        <strong>
                            <?= number_format(
                                $totalTeachersAllocated
                            ) ?>
                        </strong>

                        <small>
                            Teachers with assignments
                        </small>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="stat-card stat-card-gold">

                    <div class="stat-card-icon">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Class Teachers</span>

                        <strong>
                            <?= number_format(
                                $totalClassTeachers
                            ) ?>
                        </strong>

                        <small>
                            Assigned class teachers
                        </small>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="stat-card stat-card-purple">

                    <div class="stat-card-icon">
                        <i class="bi bi-book-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Subject Allocations</span>

                        <strong>
                            <?= number_format(
                                $totalSubjectsAllocated
                            ) ?>
                        </strong>

                        <small>
                            Teaching assignments
                        </small>
                    </div>

                </article>

            </div>

        </section>

        <section class="dashboard-card">

            <div class="dashboard-card-header">

                <div>
                    <h3>Teacher Assignments</h3>

                    <p>
                        Current class and subject allocations
                    </p>
                </div>

                <form
                    method="GET"
                    action=""
                    class="module-search-form"
                >
                    <i class="bi bi-search"></i>

                    <input
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search teacher, class or subject"
                    >
                </form>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Year</th>
                            <th>Term</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Subject</th>
                            <th>Class Teacher</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($allocations === []): ?>

                        <tr>
                            <td colspan="9">

                                <div class="empty-state">
                                    <i class="bi bi-person-video3"></i>

                                    <strong>
                                        No teacher allocations found
                                    </strong>

                                    <span>
                                        Create the first teacher assignment.
                                    </span>
                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($allocations as $allocation): ?>

                            <?php
                            $teacherName =
                                allocationTeacherName($allocation);
                            ?>

                            <tr>

                                <td>
                                    <div class="allocation-teacher">

                                        <span class="allocation-avatar">
                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    substr(
                                                        $teacherName,
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>
                                        </span>

                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $teacherName
                                                ) ?>
                                            </strong>

                                            <small>
                                                <?= htmlspecialchars(
                                                    $allocation[
                                                        'staff_number'
                                                    ]
                                                ) ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $allocation['year_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $allocation['term_name']
                                    ) ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $allocation['class_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $allocation[
                                            'stream_name'
                                        ] ?? 'All streams'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        !empty(
                                            $allocation['subject_name']
                                        )
                                    ): ?>

                                        <span class="subject-allocation-badge">
                                            <?= htmlspecialchars(
                                                $allocation[
                                                    'subject_name'
                                                ]
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No subject
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (
                                        (int) $allocation[
                                            'is_class_teacher'
                                        ] === 1
                                    ): ?>

                                        <span class="class-teacher-badge">
                                            <i class="bi bi-check-circle-fill"></i>
                                            Yes
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status-badge status-active">
                                        <?= htmlspecialchars(
                                            $allocation['status']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <form
                                        action="delete.php"
                                        method="POST"
                                        onsubmit="
                                            return confirm(
                                                'Remove this teacher allocation?'
                                            );
                                        "
                                    >
                                        <input
                                            type="hidden"
                                            name="allocation_id"
                                            value="<?= (int) $allocation['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="table-delete-button"
                                            title="Remove allocation"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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