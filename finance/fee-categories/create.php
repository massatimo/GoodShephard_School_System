<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset(
    $_SESSION['form_errors'],
    $_SESSION['old_input']
);

function feeCategoryOld(
    array $old,
    string $field,
    string $default = ''
): string {
    return htmlspecialchars(
        (string) ($old[$field] ?? $default)
    );
}

function feeCategorySelected(
    array $old,
    string $field,
    string $value,
    string $default = ''
): string {
    $currentValue =
        (string) ($old[$field] ?? $default);

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
        Add Fee Category | Royal Kindergarten and Primary School
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

                <h2>Add Fee Category</h2>

                <p>
                    Create a charge that can later be assigned
                    to classes through fee structures.
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
            action="store.php"
            method="POST"
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
                        <i class="bi bi-tag-fill"></i>
                    </span>

                    <div>
                        <h3>Category Information</h3>

                        <p>
                            Enter the official name, code and
                            description of the charge.
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
                            value="<?= feeCategoryOld(
                                $old,
                                'category_name'
                            ) ?>"
                            class="form-control professional-input"
                            placeholder="Example: Tuition Fees"
                            required
                            autofocus
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
                            value="<?= feeCategoryOld(
                                $old,
                                'category_code'
                            ) ?>"
                            class="form-control professional-input text-uppercase"
                            placeholder="Example: TUI"
                            required
                        >

                        <small class="text-muted">
                            Use a short unique code without spaces.
                        </small>

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
                            placeholder="Describe what this fee category covers"
                        ><?= feeCategoryOld(
                            $old,
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
                            Define whether the charge is mandatory,
                            how often it applies and its initial status.
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
                            <option
                                value="Mandatory"
                                <?= feeCategorySelected(
                                    $old,
                                    'category_type',
                                    'Mandatory',
                                    'Mandatory'
                                ) ?>
                            >
                                Mandatory
                            </option>

                            <option
                                value="Optional"
                                <?= feeCategorySelected(
                                    $old,
                                    'category_type',
                                    'Optional'
                                ) ?>
                            >
                                Optional
                            </option>
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
                                    <?= feeCategorySelected(
                                        $old,
                                        'charge_frequency',
                                        $frequency,
                                        'Termly'
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
                            <option
                                value="Active"
                                <?= feeCategorySelected(
                                    $old,
                                    'status',
                                    'Active',
                                    'Active'
                                ) ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= feeCategorySelected(
                                    $old,
                                    'status',
                                    'Inactive'
                                ) ?>
                            >
                                Inactive
                            </option>
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
                    Save Fee Category
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