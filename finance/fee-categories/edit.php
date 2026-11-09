<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$categoryId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$categoryId || $categoryId < 1) {
    $_SESSION['error_message'] =
        'Invalid fee category selected.';

    header('Location: index.php');
    exit;
}

$statement = $pdo->prepare(
    'SELECT *
     FROM fee_categories
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $categoryId,
]);

$category = $statement->fetch();

if (!$category) {
    $_SESSION['error_message'] =
        'The selected fee category was not found.';

    header('Location: index.php');
    exit;
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset(
    $_SESSION['form_errors'],
    $_SESSION['old_input']
);

function editFeeCategoryValue(
    array $old,
    array $category,
    string $field
): string {
    return htmlspecialchars(
        (string) (
            $old[$field]
            ?? $category[$field]
            ?? ''
        )
    );
}

function editFeeCategorySelected(
    array $old,
    array $category,
    string $field,
    string $value
): string {
    $currentValue =
        (string) (
            $old[$field]
            ?? $category[$field]
            ?? ''
        );

    return $currentValue === $value
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
        Edit Fee Category | Royal Kindergarten and Primary School
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

        <section class="module-heading">

            <div>
                <span class="module-label">
                    FINANCE SETUP
                </span>

                <h2>Edit Fee Category</h2>

                <p>
                    Update the category information and
                    charging rules.
                </p>
            </div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Categories
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

        <form
            action="update.php"
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
                name="category_id"
                value="<?= (int) $category['id'] ?>"
            >

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-pencil-square"></i>
                    </span>

                    <div>
                        <h3>Category Information</h3>

                        <p>
                            Edit the official fee name, code
                            and description.
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-7">

                        <label class="form-label">
                            Category Name *
                        </label>

                        <input
                            type="text"
                            name="category_name"
                            maxlength="120"
                            value="<?= editFeeCategoryValue(
                                $old,
                                $category,
                                'category_name'
                            ) ?>"
                            class="form-control professional-input"
                            required
                        >

                    </div>

                    <div class="col-md-5">

                        <label class="form-label">
                            Category Code *
                        </label>

                        <input
                            type="text"
                            name="category_code"
                            id="categoryCode"
                            maxlength="30"
                            value="<?= editFeeCategoryValue(
                                $old,
                                $category,
                                'category_code'
                            ) ?>"
                            class="form-control professional-input text-uppercase"
                            required
                        >

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                            maxlength="500"
                            class="form-control professional-input"
                        ><?= editFeeCategoryValue(
                            $old,
                            $category,
                            'description'
                        ) ?></textarea>

                    </div>

                </div>

            </section>

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-sliders"></i>
                    </span>

                    <div>
                        <h3>Charging Rules</h3>

                        <p>
                            Update category type, charge
                            frequency and availability.
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-4">

                        <label class="form-label">
                            Category Type *
                        </label>

                        <select
                            name="category_type"
                            class="form-select professional-input"
                            required
                        >

                            <?php foreach (
                                [
                                    'Mandatory',
                                    'Optional',
                                ] as $type
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $type
                                    ) ?>"
                                    <?= editFeeCategorySelected(
                                        $old,
                                        $category,
                                        'category_type',
                                        $type
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $type
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Charge Frequency *
                        </label>

                        <select
                            name="charge_frequency"
                            class="form-select professional-input"
                            required
                        >

                            <?php foreach (
                                [
                                    'Once',
                                    'Termly',
                                    'Annually',
                                    'Monthly',
                                ] as $frequency
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $frequency
                                    ) ?>"
                                    <?= editFeeCategorySelected(
                                        $old,
                                        $category,
                                        'charge_frequency',
                                        $frequency
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $frequency
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Status *
                        </label>

                        <select
                            name="status"
                            class="form-select professional-input"
                            required
                        >

                            <?php foreach (
                                [
                                    'Active',
                                    'Inactive',
                                ] as $status
                            ): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        $status
                                    ) ?>"
                                    <?= editFeeCategorySelected(
                                        $old,
                                        $category,
                                        'status',
                                        $status
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $status
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

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
                    Update Fee Category
                </button>

            </div>

        </form>

    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../../assets/js/app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categoryCode =
        document.getElementById('categoryCode');

    categoryCode?.addEventListener('input', () => {
        categoryCode.value =
            categoryCode.value
                .toUpperCase()
                .replace(/[^A-Z0-9_-]/g, '');
    });
});
</script>

</body>
</html>