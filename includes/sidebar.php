<?php

$currentPage = basename($_SERVER['PHP_SELF']);

function sidebarActive(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'active' : '';
}
?>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">GS</div>

        <div class="brand-text">
            <strong>Good Shepherd</strong>
            <span>Primary School</span>
        </div>
    </div>

    <div class="sidebar-section-label">
        MAIN MENU
    </div>

    <nav class="sidebar-navigation">

        <a
            href="../admin/dashboard.php"
            class="sidebar-link <?= sidebarActive('dashboard.php', $currentPage) ?>"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-people-fill"></i>
            <span>Pupils</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-person-badge-fill"></i>
            <span>Staff</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-building-fill"></i>
            <span>Classes & Streams</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-calendar-check-fill"></i>
            <span>Attendance</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-journal-check"></i>
            <span>Examinations</span>
        </a>

        <div class="sidebar-section-label">
            FINANCE
        </div>

        <a href="#" class="sidebar-link">
            <i class="bi bi-wallet2"></i>
            <span>Fees & Payments</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Expenses</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-cash-stack"></i>
            <span>Cashbook</span>
        </a>

        <div class="sidebar-section-label">
            MANAGEMENT
        </div>

        <a href="#" class="sidebar-link">
            <i class="bi bi-box-seam-fill"></i>
            <span>Inventory</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Reports</span>
        </a>

        <a href="#" class="sidebar-link">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
        </div>

        <div class="sidebar-user-info">
            <strong>
                <?= htmlspecialchars($_SESSION['full_name']) ?>
            </strong>

            <span>
                <?= htmlspecialchars(ucwords($_SESSION['role'])) ?>
            </span>
        </div>
    </div>
</aside>