<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$categoryType = trim($_GET['category_type'] ?? '');
$frequency = trim($_GET['frequency'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedTypes = [
    'Mandatory',
    'Optional',
];

$allowedFrequencies = [
    'Once',
    'Termly',
    'Annually',
    'Monthly',
];

$allowedStatuses = [
    'Active',
    'Inactive',
];

/*
|--------------------------------------------------------------------------
| Retrieve fee categories
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        fee_categories.id,
        fee_categories.category_name,
        fee_categories.category_code,
        fee_categories.description,
        fee_categories.category_type,
        fee_categories.charge_frequency,
        fee_categories.status,
        fee_categories.created_at,

        COUNT(
            DISTINCT fee_structures.id
        ) AS total_structures

    FROM fee_categories

    LEFT JOIN fee_structures
        ON fee_structures.fee_category_id =
            fee_categories.id

    WHERE 1 = 1
';

$parameters = [];

if ($search !== '') {
    $sql .= '
        AND (
            fee_categories.category_name
                LIKE :search_name

            OR fee_categories.category_code
                LIKE :search_code

            OR fee_categories.description
                LIKE :search_description
        )
    ';

    $searchValue = '%' . $search . '%';
    $parameters['search_name'] = $searchValue;
    $parameters['search_code'] = $searchValue;
    $parameters['search_description'] = $searchValue;
}

if (
    in_array(
        $categoryType,
        $allowedTypes,
        true
    )
) {
    $sql .= '
        AND fee_categories.category_type =
            :category_type
    ';

    $parameters['category_type'] =
        $categoryType;
}

if (
    in_array(
        $frequency,
        $allowedFrequencies,
        true
    )
) {
    $sql .= '
        AND fee_categories.charge_frequency =
            :charge_frequency
    ';

    $parameters['charge_frequency'] =
        $frequency;
}

if (
    in_array(
        $status,
        $allowedStatuses,
        true
    )
) {
    $sql .= '
        AND fee_categories.status =
            :status
    ';

    $parameters['status'] =
        $status;
}

$sql .= '
    GROUP BY fee_categories.id

    ORDER BY
        fee_categories.category_name
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$feeCategories = $statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Summary statistics
|--------------------------------------------------------------------------
*/

$summaryStatement = $pdo->query(
    'SELECT
        COUNT(*) AS total_categories,

        SUM(
            CASE
                WHEN status = "Active"
                THEN 1
                ELSE 0
            END
        ) AS active_categories,

        SUM(
            CASE
                WHEN category_type = "Mandatory"
                THEN 1
                ELSE 0
            END
        ) AS mandatory_categories,

        SUM(
            CASE
                WHEN category_type = "Optional"
                THEN 1
                ELSE 0
            END
        ) AS optional_categories

     FROM fee_categories'
);

$summary = $summaryStatement->fetch() ?: [];

/*
|--------------------------------------------------------------------------
| Session messages
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION['success_message'] ?? '';

$errorMessage =
    $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
);
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
        Fee Categories | Royal Kindergarten and Primary School
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
        href="../../assets/css/style.css"
        rel="stylesheet"
    >
</head>

<body class="dashboard-page">

<?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="app-main" id="appMain">

    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

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
                    FINANCE SETUP
                </span>

                <h2>Fee Categories</h2>

                <p>
                    Define the different charges used when creating
                    school fee structures and pupil invoices.
                </p>
            </div>

            <div class="profile-heading-actions">

                <a
                    href="../index.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Finance Dashboard
                </a>

                <a
                    href="create.php"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-plus-circle-fill me-2"></i>
                    Add Fee Category
                </a>

            </div>

        </section>

        <!-- SUMMARY CARDS -->

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-blue">
                        <i class="bi bi-tags-fill"></i>
                    </span>

                    <div>
                        <small>Total Categories</small>

                        <strong>
                            <?= number_format(
                                (int) (
                                    $summary[
                                        'total_categories'
                                    ] ?? 0
                                )
                            ) ?>
                        </strong>

                        <span>All configured charges</span>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-green">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>

                    <div>
                        <small>Active Categories</small>

                        <strong>
                            <?= number_format(
                                (int) (
                                    $summary[
                                        'active_categories'
                                    ] ?? 0
                                )
                            ) ?>
                        </strong>

                        <span>Available for fee structures</span>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-gold">
                        <i class="bi bi-shield-check"></i>
                    </span>

                    <div>
                        <small>Mandatory</small>

                        <strong>
                            <?= number_format(
                                (int) (
                                    $summary[
                                        'mandatory_categories'
                                    ] ?? 0
                                )
                            ) ?>
                        </strong>

                        <span>Required pupil charges</span>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-red">
                        <i class="bi bi-toggle-on"></i>
                    </span>

                    <div>
                        <small>Optional</small>

                        <strong>
                            <?= number_format(
                                (int) (
                                    $summary[
                                        'optional_categories'
                                    ] ?? 0
                                )
                            ) ?>
                        </strong>

                        <span>Selectable pupil charges</span>
                    </div>

                </article>

            </div>

        </section>

        <!-- FILTERS -->

        <section class="form-section-card">

            <div class="form-section-header">

                <span class="form-section-icon">
                    <i class="bi bi-funnel-fill"></i>
                </span>

                <div>
                    <h3>Search and Filter</h3>

                    <p>
                        Find fee categories by name, code,
                        type, frequency or status.
                    </p>
                </div>

            </div>

            <form
                method="GET"
                action=""
                class="row g-4"
            >

                <div class="col-lg-4">

                    <label class="form-label">
                        Search Category
                    </label>

                    <input
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        class="form-control professional-input"
                        placeholder="Name, code or description"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Category Type
                    </label>

                    <select
                        name="category_type"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All types
                        </option>

                        <?php foreach (
                            $allowedTypes as $type
                        ): ?>

                            <option
                                value="<?= htmlspecialchars($type) ?>"
                                <?= $categoryType === $type
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($type) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Frequency
                    </label>

                    <select
                        name="frequency"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All frequencies
                        </option>

                        <?php foreach (
                            $allowedFrequencies as
                            $frequencyOption
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $frequencyOption
                                ) ?>"
                                <?= $frequency ===
                                    $frequencyOption
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $frequencyOption
                                ) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select professional-input"
                    >
                        <option value="">
                            All statuses
                        </option>

                        <?php foreach (
                            $allowedStatuses as
                            $statusOption
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $statusOption
                                ) ?>"
                                <?= $status === $statusOption
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $statusOption
                                ) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                </div>

                <div class="col-md-2 d-flex align-items-end">

                    <div class="d-flex gap-2 w-100">

                        <button
                            type="submit"
                            class="btn school-primary-btn flex-grow-1"
                        >
                            Filter
                        </button>

                        <a
                            href="index.php"
                            class="btn btn-light"
                            title="Clear filters"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>

                    </div>

                </div>

            </form>

        </section>

        <!-- CATEGORY TABLE -->

        <section class="dashboard-card">

            <div class="dashboard-card-header">

                <div>
                    <h3>Fee Category Register</h3>

                    <p>
                        Categories available for school charges
                        and invoice generation.
                    </p>
                </div>

                <span class="status-badge status-active">
                    <?= number_format(
                        count($feeCategories)
                    ) ?>
                    records
                </span>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Frequency</th>
                            <th>Fee Structures</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($feeCategories === []): ?>

                        <tr>
                            <td colspan="9">

                                <div class="empty-state">

                                    <i class="bi bi-tags"></i>

                                    <strong>
                                        No fee categories found
                                    </strong>

                                    <span>
                                        Add the first category or
                                        adjust the selected filters.
                                    </span>

                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $feeCategories as
                            $index => $category
                        ): ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $category[
                                                'category_name'
                                            ]
                                        ) ?>
                                    </strong>

                                    <?php if (
                                        !empty(
                                            $category[
                                                'description'
                                            ]
                                        )
                                    ): ?>

                                        <small class="d-block text-muted">
                                            <?= htmlspecialchars(
                                                mb_strimwidth(
                                                    $category[
                                                        'description'
                                                    ],
                                                    0,
                                                    70,
                                                    '...'
                                                )
                                            ) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <span class="finance-code-badge">
                                        <?= htmlspecialchars(
                                            $category[
                                                'category_code'
                                            ]
                                        ) ?>
                                    </span>
                                </td>

                                <td>

                                    <?php if (
                                        $category[
                                            'category_type'
                                        ] === 'Mandatory'
                                    ): ?>

                                        <span class="status-badge status-active">
                                            Mandatory
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-pending">
                                            Optional
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $category[
                                            'charge_frequency'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= number_format(
                                            (int) $category[
                                                'total_structures'
                                            ]
                                        ) ?>
                                    </strong>
                                </td>

                                <td>

                                    <?php if (
                                        $category['status']
                                        === 'Active'
                                    ): ?>

                                        <span class="status-badge status-active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $category[
                                                    'created_at'
                                                ]
                                            )
                                        )
                                    ) ?>
                                </td>

                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="edit.php?id=<?= (int) $category['id'] ?>"
                                            class="table-action-button"
                                            title="Edit fee category"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form
                                            action="change-status.php"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="
                                                return confirm(
                                                    'Change this fee category status?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                    $_SESSION[
                                                        'csrf_token'
                                                    ]
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?= (int) $category['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="table-action-button"
                                                title="Change category status"
                                            >
                                                <i
                                                    class="bi <?= $category[
                                                        'status'
                                                    ] === 'Active'
                                                        ? 'bi-toggle-on'
                                                        : 'bi-toggle-off' ?>"
                                                ></i>
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../../assets/js/app.js"></script>

</body>
</html>
