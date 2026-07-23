<header class="app-topbar">
    <div class="topbar-left">
        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
            aria-label="Open or close sidebar"
        >
            <i class="bi bi-list"></i>
        </button>

        <div>
            <h1 class="page-title">
                Dashboard
            </h1>

            <p class="page-subtitle">
                Monitor school activities and performance.
            </p>
        </div>
    </div>

    <div class="topbar-actions">
        <button
            type="button"
            class="topbar-icon-button"
            aria-label="Notifications"
        >
            <i class="bi bi-bell"></i>
            <span class="notification-dot"></span>
        </button>

        <div class="dropdown">
            <button
                class="profile-button dropdown-toggle"
                data-bs-toggle="dropdown"
                type="button"
            >
                <span class="profile-avatar">
                    <?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?>
                </span>

                <span class="profile-details d-none d-md-flex">
                    <strong>
                        <?= htmlspecialchars($_SESSION['full_name']) ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars(ucwords($_SESSION['role'])) ?>
                    </small>
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-person me-2"></i>
                        My Profile
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-gear me-2"></i>
                        Account Settings
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a
                        class="dropdown-item text-danger"
                        href="../auth/logout.php"
                    >
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Sign Out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>