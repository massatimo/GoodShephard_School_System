<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$totalPupils = (int) $pdo
    ->query('SELECT COUNT(*) FROM pupils')
    ->fetchColumn();

$totalStaff = (int) $pdo
    ->query('SELECT COUNT(*) FROM staff')
    ->fetchColumn();

$totalCollections = (float) $pdo
    ->query('SELECT COALESCE(SUM(amount), 0) FROM fee_payments')
    ->fetchColumn();

$totalExpenses = (float) $pdo
    ->query('SELECT COALESCE(SUM(amount), 0) FROM expenses')
    ->fetchColumn();

$availableBalance = $totalCollections - $totalExpenses;

$recentPayments = $pdo->query(
    'SELECT
        fee_payments.receipt_number,
        fee_payments.amount,
        fee_payments.payment_date,
        pupils.first_name,
        pupils.last_name
     FROM fee_payments
     INNER JOIN pupils
        ON pupils.id = fee_payments.pupil_id
     ORDER BY fee_payments.id DESC
     LIMIT 5'
)->fetchAll();
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
        Dashboard | Good Shepherd Primary School
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

        <section class="welcome-banner">
            <div>
                <span class="welcome-label">
                    GOOD SHEPHERD PRIMARY SCHOOL
                </span>

                <h2>
                    Welcome back,
                    <?= htmlspecialchars($_SESSION['full_name']) ?>
                </h2>

                <p>
                    Here is a summary of the school's current
                    administrative, academic and financial activity.
                </p>
            </div>

            <div class="academic-period">
                <i class="bi bi-calendar3"></i>

                <div>
                    <small>Current period</small>
                    <strong>Term II, <?= date('Y') ?></strong>
                </div>
            </div>
        </section>

        <section class="row g-4 mt-1">

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-blue">
                    <div class="stat-card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Total pupils</span>

                        <strong>
                            <?= number_format($totalPupils) ?>
                        </strong>

                        <small>
                            Registered pupils
                        </small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-green">
                    <div class="stat-card-icon">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Total staff</span>

                        <strong>
                            <?= number_format($totalStaff) ?>
                        </strong>

                        <small>
                            Teaching and support staff
                        </small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-gold">
                    <div class="stat-card-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Fee collections</span>

                        <strong class="currency-value">
                            UGX <?= number_format($totalCollections) ?>
                        </strong>

                        <small>
                            Total recorded collections
                        </small>
                    </div>
                </article>
            </div>

            <div class="col-sm-6 col-xl-3">
                <article class="stat-card stat-card-purple">
                    <div class="stat-card-icon">
                        <i class="bi bi-bank"></i>
                    </div>

                    <div class="stat-card-content">
                        <span>Available balance</span>

                        <strong class="currency-value">
                            UGX <?= number_format($availableBalance) ?>
                        </strong>

                        <small>
                            Collections less expenses
                        </small>
                    </div>
                </article>
            </div>

        </section>

        <section class="row g-4 mt-1">

            <div class="col-xl-8">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h3>School overview</h3>

                            <p>
                                Illustrative monthly activity for the prototype
                            </p>
                        </div>

                        <select class="form-select dashboard-select">
                            <option>This term</option>
                            <option>This month</option>
                            <option>This year</option>
                        </select>
                    </div>

                    <div class="chart-placeholder">
                        <div
                            class="chart-bar"
                            style="height: 42%"
                        >
                            <span>Jan</span>
                        </div>

                        <div
                            class="chart-bar"
                            style="height: 56%"
                        >
                            <span>Feb</span>
                        </div>

                        <div
                            class="chart-bar"
                            style="height: 70%"
                        >
                            <span>Mar</span>
                        </div>

                        <div
                            class="chart-bar"
                            style="height: 62%"
                        >
                            <span>Apr</span>
                        </div>

                        <div
                            class="chart-bar"
                            style="height: 84%"
                        >
                            <span>May</span>
                        </div>

                        <div
                            class="chart-bar"
                            style="height: 75%"
                        >
                            <span>Jun</span>
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-xl-4">
                <article class="dashboard-card h-100">
                    <div class="dashboard-card-header">
                        <div>
                            <h3>Quick actions</h3>

                            <p>Frequently used operations</p>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <a href="#" class="quick-action">
                            <span class="quick-action-icon action-blue">
                                <i class="bi bi-person-plus-fill"></i>
                            </span>

                            <div>
                                <strong>Register pupil</strong>
                                <small>Create a pupil record</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#" class="quick-action">
                            <span class="quick-action-icon action-green">
                                <i class="bi bi-cash-coin"></i>
                            </span>

                            <div>
                                <strong>Record payment</strong>
                                <small>Receive school fees</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#" class="quick-action">
                            <span class="quick-action-icon action-orange">
                                <i class="bi bi-receipt"></i>
                            </span>

                            <div>
                                <strong>Record expense</strong>
                                <small>Enter school expenditure</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#" class="quick-action">
                            <span class="quick-action-icon action-purple">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                            </span>

                            <div>
                                <strong>Generate report</strong>
                                <small>View school reports</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </article>
            </div>

        </section>

        <section class="row g-4 mt-1">

            <div class="col-xl-8">
                <article class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div>
                            <h3>Recent fee payments</h3>

                            <p>Latest payments recorded by the bursar</p>
                        </div>

                        <a href="#" class="card-link">
                            View all
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table dashboard-table align-middle">
                            <thead>
                                <tr>
                                    <th>Pupil</th>
                                    <th>Receipt</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if ($recentPayments === []): ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="bi bi-receipt"></i>

                                            <strong>
                                                No fee payments recorded
                                            </strong>

                                            <span>
                                                Recent payments will appear here.
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPayments as $payment): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars(
                                                $payment['first_name'] . ' ' .
                                                $payment['last_name']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $payment['receipt_number']
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $payment['payment_date']
                                            ) ?>
                                        </td>

                                        <td class="fw-semibold">
                                            UGX
                                            <?= number_format(
                                                (float) $payment['amount']
                                            ) ?>
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
                <article class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div>
                            <h3>School calendar</h3>

                            <p>Upcoming activities</p>
                        </div>
                    </div>

                    <div class="event-list">
                        <div class="event-item">
                            <div class="event-date">
                                <strong>12</strong>
                                <span>JUN</span>
                            </div>

                            <div>
                                <strong>Staff meeting</strong>
                                <span>Head Teacher's office · 3:00 PM</span>
                            </div>
                        </div>

                        <div class="event-item">
                            <div class="event-date">
                                <strong>18</strong>
                                <span>JUN</span>
                            </div>

                            <div>
                                <strong>Mid-term examinations</strong>
                                <span>All classes</span>
                            </div>
                        </div>

                        <div class="event-item">
                            <div class="event-date">
                                <strong>25</strong>
                                <span>JUN</span>
                            </div>

                            <div>
                                <strong>Visitation day</strong>
                                <span>School compound · 9:00 AM</span>
                            </div>
                        </div>
                    </div>
                </article>
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