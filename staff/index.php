<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = '
    SELECT
        id,
        staff_number,
        first_name,
        middle_name,
        last_name,
        full_name,
        gender,
        phone,
        email,
        staff_category,
        department,
        designation,
        position,
        employment_type,
        employment_status,
        photo
    FROM staff
    WHERE 1 = 1
';

$parameters = [];

if ($search !== '') {
    $sql .= '
        AND (
            staff_number LIKE :search
            OR first_name LIKE :search
            OR middle_name LIKE :search
            OR last_name LIKE :search
            OR full_name LIKE :search
            OR phone LIKE :search
            OR email LIKE :search
        )
    ';

    $parameters['search'] = '%' . $search . '%';
}

$allowedCategories = [
    'Teaching Staff',
    'Non-Teaching Staff',
    'Administration',
];

if (in_array($category, $allowedCategories, true)) {
    $sql .= '
        AND staff_category = :category
    ';

    $parameters['category'] = $category;
}

$sql .= '
    ORDER BY id DESC
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$staffMembers = $statement->fetchAll();

$totalStaff = (int) $pdo
    ->query('SELECT COUNT(*) FROM staff')
    ->fetchColumn();

$totalTeaching = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM staff
         WHERE staff_category = 'Teaching Staff'"
    )
    ->fetchColumn();

$totalNonTeaching = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM staff
         WHERE staff_category = 'Non-Teaching Staff'"
    )
    ->fetchColumn();

$totalActive = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM staff
         WHERE employment_status = 'Active'"
    )
    ->fetchColumn();

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
);

function staffFullName(array $staff): string
{
    $separateName = trim(
        ($staff['first_name'] ?? '') . ' ' .
        ($staff['middle_name'] ?? '') . ' ' .
        ($staff['last_name'] ?? '')
    );

    if ($separateName !== '') {
        return $separateName;
    }

    return trim((string) ($staff['full_name'] ?? 'Unknown Staff'));
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
        Staff Management | Good Shepherd Primary School
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
                    HUMAN RESOURCE MANAGEMENT
                </span>

                <h2>Staff Management</h2>

                <p>
                    Register and manage teaching, administrative
                    and non-teaching staff.
                </p>
            </div>

            <a
                href="create.php"
                class="btn school-primary-btn module-action-button"
            >
                <i class="bi bi-person-plus-fill me-2"></i>
                Register Staff
            </a>

        </section>

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-blue">
                    <div class="stat-card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Total Staff</span>
                        <strong><?= number_format($totalStaff) ?></strong>
                        <small>All registered personnel</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-green">
                    <div class="stat-card-icon">
                        <i class="bi bi-person-video3"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Teaching Staff</span>
                        <strong><?= number_format($totalTeaching) ?></strong>
                        <small>Registered teachers</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-gold">
                    <div class="stat-card-icon">
                        <i class="bi bi-person-workspace"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Non-Teaching</span>
                        <strong><?= number_format($totalNonTeaching) ?></strong>
                        <small>Support staff</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-purple">
                    <div class="stat-card-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Active Staff</span>
                        <strong><?= number_format($totalActive) ?></strong>
                        <small>Currently active</small>
                    </div>
                </article>
            </div>

        </section>

        <section class="dashboard-card">

            <div class="dashboard-card-header staff-list-header">

                <div>
                    <h3>Staff Register</h3>
                    <p>All registered school personnel</p>
                </div>

                <form
                    method="GET"
                    action=""
                    class="staff-filter-form"
                >
                    <div class="module-search-form">
                        <i class="bi bi-search"></i>

                        <input
                            type="search"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search staff"
                        >
                    </div>

                    <select
                        name="category"
                        class="form-select staff-category-filter"
                        onchange="this.form.submit()"
                    >
                        <option value="">All categories</option>

                        <?php foreach ($allowedCategories as $option): ?>
                            <option
                                value="<?= htmlspecialchars($option) ?>"
                                <?= $category === $option
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($option) ?>
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
                            <th>Staff Member</th>
                            <th>Staff Number</th>
                            <th>Category</th>
                            <th>Designation</th>
                            <th>Phone</th>
                            <th>Employment</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($staffMembers === []): ?>

                        <tr>
                            <td colspan="8">

                                <div class="empty-state">
                                    <i class="bi bi-person-badge"></i>

                                    <strong>
                                        No staff records found
                                    </strong>

                                    <span>
                                        Register the first staff member
                                        to begin.
                                    </span>
                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($staffMembers as $staff): ?>

                            <?php
                            $fullName = staffFullName($staff);

                            $photoPath = null;

                            if (!empty($staff['photo'])) {
                                $candidate =
                                    __DIR__ . '/../' .
                                    ltrim(
                                        (string) $staff['photo'],
                                        '/'
                                    );

                                if (is_file($candidate)) {
                                    $photoPath =
                                        '../' .
                                        ltrim(
                                            (string) $staff['photo'],
                                            '/'
                                        );
                                }
                            }

                            $designation =
                                $staff['designation']
                                ?? $staff['position']
                                ?? 'Not assigned';
                            ?>

                            <tr>

                                <td>
                                    <div class="staff-list-person">

                                        <?php if ($photoPath !== null): ?>

                                            <img
                                                src="<?= htmlspecialchars(
                                                    $photoPath
                                                ) ?>"
                                                alt="<?= htmlspecialchars(
                                                    $fullName
                                                ) ?>"
                                                class="staff-list-photo"
                                            >

                                        <?php else: ?>

                                            <span class="staff-list-avatar">
                                                <?= htmlspecialchars(
                                                    strtoupper(
                                                        substr(
                                                            $fullName,
                                                            0,
                                                            1
                                                        )
                                                    )
                                                ) ?>
                                            </span>

                                        <?php endif; ?>

                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $fullName
                                                ) ?>
                                            </strong>

                                            <small>
                                                <?= htmlspecialchars(
                                                    $staff['email']
                                                    ?: 'No email'
                                                ) ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $staff['staff_number']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $staff['staff_category']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($designation) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $staff['phone']
                                        ?: 'Not provided'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $staff['employment_type']
                                    ) ?>
                                </td>

                                <td>
                                    <span
                                        class="status-badge
                                        <?= $staff['employment_status']
                                            === 'Active'
                                            ? 'status-active'
                                            : 'status-inactive' ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $staff['employment_status']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="view.php?id=<?= (int) $staff['id'] ?>"
                                        class="table-action-button"
                                        title="View staff profile"
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