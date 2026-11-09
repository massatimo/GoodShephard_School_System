<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Current date and default filters
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

$academicYearId = filter_input(
    INPUT_GET,
    'academic_year_id',
    FILTER_VALIDATE_INT
);

$termId = filter_input(
    INPUT_GET,
    'term_id',
    FILTER_VALIDATE_INT
);

/*
|--------------------------------------------------------------------------
| Load academic years and terms
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

/*
|--------------------------------------------------------------------------
| Automatically use current academic year
|--------------------------------------------------------------------------
*/

if (!$academicYearId) {
    foreach ($academicYears as $academicYear) {
        if ((int) $academicYear['is_current'] === 1) {
            $academicYearId =
                (int) $academicYear['id'];

            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Automatically use current term
|--------------------------------------------------------------------------
*/

if (!$termId) {
    foreach ($terms as $term) {
        if (
            (int) $term['is_current'] === 1 &&
            (
                !$academicYearId ||
                (int) $term['academic_year_id'] ===
                    (int) $academicYearId
            )
        ) {
            $termId = (int) $term['id'];
            break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Reusable financial filters
|--------------------------------------------------------------------------
*/

$invoiceWhere = '
    WHERE pupil_invoices.invoice_status <> "Cancelled"
';

$invoiceParameters = [];

if ($academicYearId) {
    $invoiceWhere .= '
        AND pupil_invoices.academic_year_id =
            :academic_year_id
    ';

    $invoiceParameters['academic_year_id'] =
        $academicYearId;
}

if ($termId) {
    $invoiceWhere .= '
        AND pupil_invoices.term_id =
            :term_id
    ';

    $invoiceParameters['term_id'] =
        $termId;
}

$paymentWhere = '
    WHERE pupil_payments.payment_status = "Completed"
';

$paymentParameters = [];

if ($academicYearId) {
    $paymentWhere .= '
        AND pupil_payments.academic_year_id =
            :academic_year_id
    ';

    $paymentParameters['academic_year_id'] =
        $academicYearId;
}

if ($termId) {
    $paymentWhere .= '
        AND pupil_payments.term_id =
            :term_id
    ';

    $paymentParameters['term_id'] =
        $termId;
}

/*
|--------------------------------------------------------------------------
| Today's collections
|--------------------------------------------------------------------------
*/

$todayCollectionStatement = $pdo->prepare(
    'SELECT COALESCE(
        SUM(amount_received),
        0
     )
     FROM pupil_payments
     WHERE payment_status = "Completed"
       AND payment_date = :payment_date'
);

$todayCollectionStatement->execute([
    'payment_date' => $today,
]);

$todayCollections =
    (float) $todayCollectionStatement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Total invoiced
|--------------------------------------------------------------------------
*/

$totalInvoicedStatement = $pdo->prepare(
    'SELECT COALESCE(
        SUM(pupil_invoices.total_amount),
        0
     )
     FROM pupil_invoices
     ' . $invoiceWhere
);

$totalInvoicedStatement->execute(
    $invoiceParameters
);

$totalInvoiced =
    (float) $totalInvoicedStatement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Total payments received
|--------------------------------------------------------------------------
*/

$totalPaymentsStatement = $pdo->prepare(
    'SELECT COALESCE(
        SUM(pupil_payments.amount_received),
        0
     )
     FROM pupil_payments
     ' . $paymentWhere
);

$totalPaymentsStatement->execute(
    $paymentParameters
);

$totalPayments =
    (float) $totalPaymentsStatement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Outstanding balances
|--------------------------------------------------------------------------
*/

$outstandingBalanceStatement = $pdo->prepare(
    'SELECT COALESCE(
        SUM(pupil_invoices.balance),
        0
     )
     FROM pupil_invoices
     ' . $invoiceWhere . '
       AND pupil_invoices.balance > 0'
);

$outstandingBalanceStatement->execute(
    $invoiceParameters
);

$outstandingBalance =
    (float) $outstandingBalanceStatement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Credit balances
|--------------------------------------------------------------------------
*/

$creditBalanceStatement = $pdo->prepare(
    'SELECT COALESCE(
        SUM(ABS(pupil_invoices.balance)),
        0
     )
     FROM pupil_invoices
     ' . $invoiceWhere . '
       AND pupil_invoices.balance < 0'
);

$creditBalanceStatement->execute(
    $invoiceParameters
);

$creditBalance =
    (float) $creditBalanceStatement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Invoice counters
|--------------------------------------------------------------------------
*/

$invoiceCountStatement = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_invoices,

        SUM(
            CASE
                WHEN invoice_status = "Unpaid"
                THEN 1
                ELSE 0
            END
        ) AS unpaid_invoices,

        SUM(
            CASE
                WHEN invoice_status = "Partially Paid"
                THEN 1
                ELSE 0
            END
        ) AS partial_invoices,

        SUM(
            CASE
                WHEN invoice_status = "Paid"
                THEN 1
                ELSE 0
            END
        ) AS paid_invoices

     FROM pupil_invoices
     ' . $invoiceWhere
);

$invoiceCountStatement->execute(
    $invoiceParameters
);

$invoiceSummary =
    $invoiceCountStatement->fetch() ?: [];

/*
|--------------------------------------------------------------------------
| Finance setup counters
|--------------------------------------------------------------------------
*/

$totalCategories = (int) $pdo
    ->query(
        "SELECT COUNT(*)
         FROM fee_categories
         WHERE status = 'Active'"
    )
    ->fetchColumn();

$totalStructuresStatement = $pdo->prepare(
    'SELECT COUNT(*)
     FROM fee_structures
     WHERE status = "Active"
       AND (
            :academic_year_id IS NULL
            OR academic_year_id =
                :academic_year_id_filter
       )
       AND (
            :term_id IS NULL
            OR term_id =
                :term_id_filter
       )'
);

$totalStructuresStatement->execute([
    'academic_year_id' =>
        $academicYearId ?: null,

    'academic_year_id_filter' =>
        $academicYearId ?: null,

    'term_id' =>
        $termId ?: null,

    'term_id_filter' =>
        $termId ?: null,
]);

$totalStructures =
    (int) $totalStructuresStatement->fetchColumn();

/*
|--------------------------------------------------------------------------
| Recent payments
|--------------------------------------------------------------------------
*/

$recentPaymentsStatement = $pdo->prepare(
    'SELECT
        pupil_payments.id,
        pupil_payments.payment_number,
        pupil_payments.receipt_number,
        pupil_payments.payment_date,
        pupil_payments.amount_received,
        pupil_payments.payer_name,
        pupil_payments.transaction_reference,

        payment_methods.method_name,

        pupils.id AS pupil_id,
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,

        classes.class_name

     FROM pupil_payments

     INNER JOIN pupils
        ON pupils.id =
            pupil_payments.pupil_id

     LEFT JOIN classes
        ON classes.id =
            pupils.class_id

     INNER JOIN payment_methods
        ON payment_methods.id =
            pupil_payments.payment_method_id

     ' . $paymentWhere . '

     ORDER BY
        pupil_payments.payment_date DESC,
        pupil_payments.id DESC

     LIMIT 8'
);

$recentPaymentsStatement->execute(
    $paymentParameters
);

$recentPayments =
    $recentPaymentsStatement->fetchAll();

/*
|--------------------------------------------------------------------------
| Outstanding pupil balances
|--------------------------------------------------------------------------
*/

$largestBalancesStatement = $pdo->prepare(
    'SELECT
        pupils.id AS pupil_id,
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,

        classes.class_name,
        streams.stream_name,

        SUM(pupil_invoices.total_amount)
            AS total_charged,

        SUM(pupil_invoices.amount_paid)
            AS total_paid,

        SUM(pupil_invoices.balance)
            AS total_balance

     FROM pupil_invoices

     INNER JOIN pupils
        ON pupils.id =
            pupil_invoices.pupil_id

     LEFT JOIN classes
        ON classes.id =
            pupil_invoices.class_id

     LEFT JOIN streams
        ON streams.id =
            pupils.stream_id

     ' . $invoiceWhere . '

     GROUP BY
        pupils.id,
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        classes.class_name,
        streams.stream_name

     HAVING total_balance > 0

     ORDER BY total_balance DESC

     LIMIT 8'
);

$largestBalancesStatement->execute(
    $invoiceParameters
);

$largestBalances =
    $largestBalancesStatement->fetchAll();

/*
|--------------------------------------------------------------------------
| Collections by payment method
|--------------------------------------------------------------------------
*/

$paymentMethodStatement = $pdo->prepare(
    'SELECT
        payment_methods.method_name,

        COUNT(pupil_payments.id)
            AS transaction_count,

        COALESCE(
            SUM(pupil_payments.amount_received),
            0
        ) AS total_amount

     FROM payment_methods

     LEFT JOIN pupil_payments
        ON pupil_payments.payment_method_id =
            payment_methods.id

       AND pupil_payments.payment_status =
            "Completed"

       ' .
       (
           $academicYearId
               ? 'AND pupil_payments.academic_year_id =
                    :academic_year_id'
               : ''
       ) .
       (
           $termId
               ? ' AND pupil_payments.term_id =
                    :term_id'
               : ''
       ) . '

     WHERE payment_methods.status = "Active"

     GROUP BY
        payment_methods.id,
        payment_methods.method_name

     ORDER BY total_amount DESC'
);

$methodParameters = [];

if ($academicYearId) {
    $methodParameters['academic_year_id'] =
        $academicYearId;
}

if ($termId) {
    $methodParameters['term_id'] =
        $termId;
}

$paymentMethodStatement->execute(
    $methodParameters
);

$paymentMethodSummary =
    $paymentMethodStatement->fetchAll();

/*
|--------------------------------------------------------------------------
| Collection percentage
|--------------------------------------------------------------------------
*/

$collectionRate =
    $totalInvoiced > 0
        ? ($totalPayments / $totalInvoiced) * 100
        : 0;

$collectionRate =
    min(100, max(0, $collectionRate));

/*
|--------------------------------------------------------------------------
| Selected filter labels
|--------------------------------------------------------------------------
*/

$selectedYearName = 'All Academic Years';
$selectedTermName = 'All Terms';

foreach ($academicYears as $academicYear) {
    if (
        (int) $academicYear['id'] ===
        (int) $academicYearId
    ) {
        $selectedYearName =
            $academicYear['year_name'];

        break;
    }
}

foreach ($terms as $term) {
    if ((int) $term['id'] === (int) $termId) {
        $selectedTermName =
            $term['term_name'];

        break;
    }
}

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

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function financeMoney(
    float|int|string|null $amount
): string {
    return 'UGX ' . number_format(
        (float) $amount,
        0
    );
}

function financeInvoiceStatusClass(
    string $status
): string {
    return match ($status) {
        'Paid' =>
            'finance-status-paid',

        'Partially Paid' =>
            'finance-status-partial',

        'Unpaid' =>
            'finance-status-unpaid',

        'Draft' =>
            'finance-status-draft',

        default =>
            'finance-status-default',
    };
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
        Finance Dashboard | Royal Kindergarten and Primary School
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

<body class="dashboard-page finance-dashboard-page">

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
                    SCHOOL FINANCE
                </span>

                <h2>Fees and Finance</h2>

                <p>
                    Manage school fees, invoices, payments,
                    balances and financial reports.
                </p>
            </div>

            <div class="profile-heading-actions">

                <a
                    href="payments/create.php"
                    class="btn btn-outline-success"
                >
                    <i class="bi bi-cash-coin me-2"></i>
                    Receive Payment
                </a>

                <a
                    href="invoices/generate.php"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-receipt-cutoff me-2"></i>
                    Generate Invoices
                </a>

            </div>

        </section>

        <!-- ACADEMIC PERIOD FILTER -->

        <section class="finance-period-card">

            <div>

                <span class="finance-period-icon">
                    <i class="bi bi-calendar3"></i>
                </span>

                <div>
                    <small>Financial Reporting Period</small>

                    <strong>
                        <?= htmlspecialchars($selectedYearName) ?>
                        ·
                        <?= htmlspecialchars($selectedTermName) ?>
                    </strong>
                </div>

            </div>

            <form
                method="GET"
                action=""
                class="finance-period-form"
            >

                <select
                    name="academic_year_id"
                    id="academicYearId"
                    class="form-select professional-input"
                >

                    <option value="">
                        All academic years
                    </option>

                    <?php foreach (
                        $academicYears as $academicYear
                    ): ?>

                        <option
                            value="<?= (int) $academicYear['id'] ?>"
                            <?= (int) $academicYearId ===
                                (int) $academicYear['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= htmlspecialchars(
                                $academicYear['year_name']
                            ) ?>

                            <?= (int) $academicYear['is_current'] === 1
                                ? ' (Current)'
                                : '' ?>
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
                            <?= (int) $termId ===
                                (int) $term['id']
                                ? 'selected'
                                : '' ?>
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

                <button
                    type="submit"
                    class="btn btn-outline-success"
                >
                    Apply
                </button>

            </form>

        </section>

        <!-- MAIN FINANCIAL STATISTICS -->

        <section class="row g-4 mb-4 finance-stat-grid">

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-green">
                        <i class="bi bi-wallet2"></i>
                    </span>

                    <div>
                        <small>Today's Collections</small>

                        <strong>
                            <?= financeMoney(
                                $todayCollections
                            ) ?>
                        </strong>

                        <span>
                            Payments received today
                        </span>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-blue">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </span>

                    <div>
                        <small>Total Invoiced</small>

                        <strong>
                            <?= financeMoney(
                                $totalInvoiced
                            ) ?>
                        </strong>

                        <span>
                            Charges for selected period
                        </span>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-gold">
                        <i class="bi bi-cash-stack"></i>
                    </span>

                    <div>
                        <small>Total Payments</small>

                        <strong>
                            <?= financeMoney(
                                $totalPayments
                            ) ?>
                        </strong>

                        <span>
                            Valid payments received
                        </span>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="finance-stat-card">

                    <span class="finance-stat-icon finance-stat-red">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </span>

                    <div>
                        <small>Outstanding Balance</small>

                        <strong>
                            <?= financeMoney(
                                $outstandingBalance
                            ) ?>
                        </strong>

                        <span>
                            Fees still owed
                        </span>
                    </div>

                </article>

            </div>

        </section>

        <!-- COLLECTION PROGRESS AND INVOICE SUMMARY -->

        <section class="row g-4 mb-4 finance-overview-grid">

            <div class="col-xl-7">

                <article class="dashboard-card h-100">

                    <div class="dashboard-card-header">

                        <div>
                            <h3>Fee Collection Progress</h3>

                            <p>
                                Invoiced amount compared with payments received
                            </p>
                        </div>

                        <strong class="finance-collection-rate">
                            <?= number_format(
                                $collectionRate,
                                1
                            ) ?>%
                        </strong>

                    </div>

                    <div class="finance-progress-content">

                        <div class="finance-progress-track">

                            <span
                                style="width:
                                <?= number_format(
                                    $collectionRate,
                                    2,
                                    '.',
                                    ''
                                ) ?>%;"
                            ></span>

                        </div>

                        <div class="finance-progress-labels">

                            <div>
                                <small>Collected</small>

                                <strong>
                                    <?= financeMoney(
                                        $totalPayments
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <small>Outstanding</small>

                                <strong>
                                    <?= financeMoney(
                                        $outstandingBalance
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <small>Credit Balance</small>

                                <strong>
                                    <?= financeMoney(
                                        $creditBalance
                                    ) ?>
                                </strong>
                            </div>

                        </div>

                    </div>

                </article>

            </div>

            <div class="col-xl-5">

                <article class="dashboard-card h-100">

                    <div class="dashboard-card-header">

                        <div>
                            <h3>Invoice Summary</h3>

                            <p>
                                Status of pupil invoices
                            </p>
                        </div>

                    </div>

                    <div class="finance-invoice-summary">

                        <div>
                            <span class="finance-summary-dot finance-dot-blue"></span>

                            <div>
                                <small>Total Invoices</small>

                                <strong>
                                    <?= number_format(
                                        (int) (
                                            $invoiceSummary[
                                                'total_invoices'
                                            ] ?? 0
                                        )
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                        <div>
                            <span class="finance-summary-dot finance-dot-red"></span>

                            <div>
                                <small>Unpaid</small>

                                <strong>
                                    <?= number_format(
                                        (int) (
                                            $invoiceSummary[
                                                'unpaid_invoices'
                                            ] ?? 0
                                        )
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                        <div>
                            <span class="finance-summary-dot finance-dot-gold"></span>

                            <div>
                                <small>Partially Paid</small>

                                <strong>
                                    <?= number_format(
                                        (int) (
                                            $invoiceSummary[
                                                'partial_invoices'
                                            ] ?? 0
                                        )
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                        <div>
                            <span class="finance-summary-dot finance-dot-green"></span>

                            <div>
                                <small>Paid</small>

                                <strong>
                                    <?= number_format(
                                        (int) (
                                            $invoiceSummary[
                                                'paid_invoices'
                                            ] ?? 0
                                        )
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                    </div>

                </article>

            </div>

        </section>

        <!-- QUICK ACTIONS -->

        <section class="dashboard-card mb-4">

            <div class="dashboard-card-header">

                <div>
                    <h3>Finance Operations</h3>

                    <p>
                        Quick access to major finance functions
                    </p>
                </div>

            </div>

            <div class="finance-quick-actions">

                <a href="fee-categories/index.php">

                    <span>
                        <i class="bi bi-tags-fill"></i>
                    </span>

                    <div>
                        <strong>Fee Categories</strong>

                        <small>
                            <?= number_format(
                                $totalCategories
                            ) ?>
                            active categories
                        </small>
                    </div>

                </a>

                <a href="fee-structures/index.php">

                    <span>
                        <i class="bi bi-list-check"></i>
                    </span>

                    <div>
                        <strong>Fee Structures</strong>

                        <small>
                            <?= number_format(
                                $totalStructures
                            ) ?>
                            active structures
                        </small>
                    </div>

                </a>

                <a href="invoices/index.php">

                    <span>
                        <i class="bi bi-receipt"></i>
                    </span>

                    <div>
                        <strong>Pupil Invoices</strong>

                        <small>
                            View charges and balances
                        </small>
                    </div>

                </a>

                <a href="payments/create.php">

                    <span>
                        <i class="bi bi-cash-coin"></i>
                    </span>

                    <div>
                        <strong>Receive Payment</strong>

                        <small>
                            Record pupil fee payment
                        </small>
                    </div>

                </a>

                <a href="payments/index.php">

                    <span>
                        <i class="bi bi-clock-history"></i>
                    </span>

                    <div>
                        <strong>Payment History</strong>

                        <small>
                            Review all transactions
                        </small>
                    </div>

                </a>

                <a href="reports/index.php">

                    <span>
                        <i class="bi bi-bar-chart-fill"></i>
                    </span>

                    <div>
                        <strong>Finance Reports</strong>

                        <small>
                            Collections and balances
                        </small>
                    </div>

                </a>

            </div>

        </section>

        <!-- RECENT PAYMENTS AND METHOD SUMMARY -->

        <section class="row g-4 mb-4 finance-activity-grid">

            <div class="col-xl-8">

                <article class="dashboard-card h-100">

                    <div class="dashboard-card-header">

                        <div>
                            <h3>Recent Payments</h3>

                            <p>
                                Latest completed pupil payments
                            </p>
                        </div>

                        <a
                            href="payments/index.php"
                            class="btn btn-sm btn-outline-success"
                        >
                            View All
                        </a>

                    </div>

                    <div class="table-responsive">

                        <table class="table dashboard-table align-middle">

                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Receipt</th>
                                    <th>Pupil</th>
                                    <th>Class</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($recentPayments === []): ?>

                                <tr>
                                    <td colspan="7">

                                        <div class="empty-state">

                                            <i class="bi bi-cash-coin"></i>

                                            <strong>
                                                No payments recorded
                                            </strong>

                                            <span>
                                                Completed payments will appear here.
                                            </span>

                                        </div>

                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $recentPayments as $payment
                                ): ?>

                                    <?php
                                    $pupilName = trim(
                                        $payment['first_name'] . ' ' .
                                        ($payment['middle_name'] ?? '') . ' ' .
                                        $payment['last_name']
                                    );
                                    ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $payment[
                                                            'payment_date'
                                                        ]
                                                    )
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="finance-code-badge">
                                                <?= htmlspecialchars(
                                                    $payment[
                                                        'receipt_number'
                                                    ]
                                                ) ?>
                                            </span>
                                        </td>

                                        <td>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $pupilName
                                                ) ?>
                                            </strong>

                                            <small class="d-block text-muted">
                                                <?= htmlspecialchars(
                                                    $payment[
                                                        'admission_number'
                                                    ]
                                                ) ?>
                                            </small>

                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $payment['class_name']
                                                ?? 'Not assigned'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $payment['method_name']
                                            ) ?>
                                        </td>

                                        <td class="finance-table-amount">
                                            <?= financeMoney(
                                                $payment[
                                                    'amount_received'
                                                ]
                                            ) ?>
                                        </td>

                                        <td>
                                            <a
                                                href="payments/view.php?id=<?= (int) $payment['id'] ?>"
                                                class="table-action-button"
                                                title="View payment"
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

                </article>

            </div>

            <div class="col-xl-4">

                <article class="dashboard-card h-100">

                    <div class="dashboard-card-header">

                        <div>
                            <h3>Payment Methods</h3>

                            <p>
                                Collections by payment method
                            </p>
                        </div>

                    </div>

                    <div class="finance-method-list">

                        <?php if (
                            $paymentMethodSummary === []
                        ): ?>

                            <div class="empty-state">

                                <i class="bi bi-credit-card"></i>

                                <strong>
                                    No payment methods
                                </strong>

                                <span>
                                    Configure payment methods first.
                                </span>

                            </div>

                        <?php else: ?>

                            <?php foreach (
                                $paymentMethodSummary as $method
                            ): ?>

                                <div class="finance-method-item">

                                    <span>
                                        <i class="bi bi-credit-card-fill"></i>
                                    </span>

                                    <div>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $method['method_name']
                                            ) ?>
                                        </strong>

                                        <small>
                                            <?= number_format(
                                                (int) $method[
                                                    'transaction_count'
                                                ]
                                            ) ?>
                                            transactions
                                        </small>
                                    </div>

                                    <b>
                                        <?= financeMoney(
                                            $method['total_amount']
                                        ) ?>
                                    </b>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </article>

            </div>

        </section>

        <!-- OUTSTANDING BALANCES -->

        <section class="dashboard-card">

            <div class="dashboard-card-header">

                <div>
                    <h3>Largest Outstanding Balances</h3>

                    <p>
                        Pupils with the highest unpaid balances
                    </p>
                </div>

                <a
                    href="reports/outstanding-balances.php"
                    class="btn btn-sm btn-outline-danger"
                >
                    View Defaulters
                </a>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>Pupil</th>
                            <th>Admission Number</th>
                            <th>Class</th>
                            <th>Total Charged</th>
                            <th>Total Paid</th>
                            <th>Balance</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($largestBalances === []): ?>

                        <tr>
                            <td colspan="7">

                                <div class="empty-state">

                                    <i class="bi bi-check-circle"></i>

                                    <strong>
                                        No outstanding balances
                                    </strong>

                                    <span>
                                        No unpaid pupil balances were found
                                        for the selected period.
                                    </span>

                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $largestBalances as $balance
                        ): ?>

                            <?php
                            $pupilName = trim(
                                $balance['first_name'] . ' ' .
                                ($balance['middle_name'] ?? '') . ' ' .
                                $balance['last_name']
                            );
                            ?>

                            <tr>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars(
                                        $pupilName
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $balance[
                                            'admission_number'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $balance['class_name']
                                        ?? 'Not assigned'
                                    ) ?>

                                    <?= !empty(
                                        $balance['stream_name']
                                    )
                                        ? ' – ' .
                                            htmlspecialchars(
                                                $balance[
                                                    'stream_name'
                                                ]
                                            )
                                        : '' ?>
                                </td>

                                <td>
                                    <?= financeMoney(
                                        $balance[
                                            'total_charged'
                                        ]
                                    ) ?>
                                </td>

                                <td class="finance-paid-value">
                                    <?= financeMoney(
                                        $balance['total_paid']
                                    ) ?>
                                </td>

                                <td class="finance-balance-value">
                                    <?= financeMoney(
                                        $balance[
                                            'total_balance'
                                        ]
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="statements/pupil.php?pupil_id=<?= (int) $balance['pupil_id'] ?>"
                                        class="table-action-button"
                                        title="View financial statement"
                                    >
                                        <i class="bi bi-file-earmark-text"></i>
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

    const termOptions =
        Array.from(termSelect.options);

    function filterTerms() {
        const selectedYear =
            yearSelect.value;

        termOptions.forEach((option) => {
            if (option.value === '') {
                option.hidden = false;
                return;
            }

            option.hidden =
                selectedYear !== '' &&
                option.dataset.yearId !==
                    selectedYear;
        });

        const selectedOption =
            termSelect.options[
                termSelect.selectedIndex
            ];

        if (
            selectedOption &&
            selectedOption.value !== '' &&
            selectedYear !== '' &&
            selectedOption.dataset.yearId !==
                selectedYear
        ) {
            termSelect.value = '';
        }
    }

    yearSelect.addEventListener(
        'change',
        filterTerms
    );

    filterTerms();
});
</script>

</body>
</html>
