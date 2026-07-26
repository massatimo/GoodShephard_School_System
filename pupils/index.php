<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$search = trim($_GET['search'] ?? '');

$sql = '
    SELECT
        pupils.id,
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender,
        pupils.orphan_status,
        pupils.pupil_status,
        classes.class_name,
        streams.stream_name
    FROM pupils
    LEFT JOIN classes
        ON classes.id = pupils.class_id
    LEFT JOIN streams
        ON streams.id = pupils.stream_id
';

$parameters = [];

if ($search !== '') {
    $sql .= '
        WHERE pupils.admission_number LIKE :search
           OR pupils.first_name LIKE :search
           OR pupils.middle_name LIKE :search
           OR pupils.last_name LIKE :search
    ';

    $parameters['search'] = '%' . $search . '%';
}

$sql .= '
    ORDER BY pupils.id DESC
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$pupils = $statement->fetchAll();

$totalPupils = (int) $pdo
    ->query('SELECT COUNT(*) FROM pupils')
    ->fetchColumn();

$totalBoys = (int) $pdo
    ->query("SELECT COUNT(*) FROM pupils WHERE gender = 'Male'")
    ->fetchColumn();

$totalGirls = (int) $pdo
    ->query("SELECT COUNT(*) FROM pupils WHERE gender = 'Female'")
    ->fetchColumn();

$totalOrphans = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM pupils
         WHERE orphan_status <> 'Not Orphan'"
    )
    ->fetchColumn();

$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Pupils | Good Shepherd Primary School</title>

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

        <section class="module-heading">
            <div>
                <span class="module-label">
                    PUPIL MANAGEMENT
                </span>

                <h2>Pupils</h2>

                <p>
                    Register, search and manage pupil information.
                </p>
            </div>

            <a
                href="create.php"
                class="btn school-primary-btn module-action-button"
            >
                <i class="bi bi-person-plus-fill me-2"></i>
                Register Pupil
            </a>
        </section>

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-blue">
                    <div class="stat-card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Total pupils</span>
                        <strong><?= number_format($totalPupils) ?></strong>
                        <small>Registered pupils</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-green">
                    <div class="stat-card-icon">
                        <i class="bi bi-gender-male"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Boys</span>
                        <strong><?= number_format($totalBoys) ?></strong>
                        <small>Male pupils</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-purple">
                    <div class="stat-card-icon">
                        <i class="bi bi-gender-female"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Girls</span>
                        <strong><?= number_format($totalGirls) ?></strong>
                        <small>Female pupils</small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-gold">
                    <div class="stat-card-icon">
                        <i class="bi bi-heart-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Orphans</span>
                        <strong><?= number_format($totalOrphans) ?></strong>
                        <small>Single and double orphans</small>
                    </div>
                </article>
            </div>

        </section>

        <section class="dashboard-card">

            <div class="dashboard-card-header">
                <div>
                    <h3>Pupil register</h3>
                    <p>All pupils currently recorded in the system</p>
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
                        placeholder="Search name or admission number"
                    >
                </form>
            </div>

            <div class="table-responsive">
                <table class="table dashboard-table align-middle">
                    <thead>
                        <tr>
                            <th>Admission No.</th>
                            <th>Pupil</th>
                            <th>Gender</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Orphan status</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($pupils === []): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>

                                    <strong>
                                        No pupils registered
                                    </strong>

                                    <span>
                                        Register the first pupil to begin.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pupils as $pupil): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $pupil['admission_number']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        trim(
                                            $pupil['first_name'] . ' ' .
                                            ($pupil['middle_name'] ?? '') . ' ' .
                                            $pupil['last_name']
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($pupil['gender']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $pupil['class_name'] ?? 'Not assigned'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $pupil['stream_name'] ?? '—'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $pupil['orphan_status']
                                    ) ?>
                                </td>

                                <td>
                                    <span class="status-badge status-active">
                                        <?= htmlspecialchars(
                                            $pupil['pupil_status']
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                   <a
    href="view.php?id=<?= (int) $pupil['id'] ?>"
    class="table-action-button"
    title="View pupil"
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