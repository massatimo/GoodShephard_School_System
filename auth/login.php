<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$errorMessage = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errorMessage = 'Please enter your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $statement = $pdo->prepare(
            'SELECT
                id,
                full_name,
                email,
                password,
                role,
                status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute([
            'email' => $email,
        ]);

        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errorMessage = 'The email address or password is incorrect.';
        } elseif ($user['status'] !== 'active') {
            $errorMessage = 'This account has been deactivated.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $updateLogin = $pdo->prepare(
                'UPDATE users
                 SET last_login = NOW()
                 WHERE id = :id'
            );

            $updateLogin->execute([
                'id' => $user['id'],
            ]);

            header('Location: ../admin/dashboard.php');
            exit;
        }
    }
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
        Staff Login | Good Shepherd Primary School
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

<body class="modern-login-page">

<main class="modern-login-layout">

    <!-- LEFT SCHOOL IMAGE SECTION -->
    <section class="login-school-visual">

        <div class="school-image-overlay"></div>

        <div class="school-visual-content">

            <div class="school-heading-block">
                <span class="school-small-title">
                    WELCOME TO
                </span>

                <h1>
                    Good Shepherd
                    <span>Primary School</span>
                </h1>

                <div class="school-title-line"></div>

                <p class="school-motto-text">
                    Excellence
                    <span>•</span>
                    Discipline
                    <span>•</span>
                    Integrity
                </p>
            </div>

            <div class="welcome-message-card">
                <div class="welcome-message-icon">
                    <i class="bi bi-shield-check"></i>
                </div>

                <div>
                    <h3>Welcome Back!</h3>

                    <p>
                        Sign in to access the School Management System.
                    </p>
                </div>
            </div>

        </div>

        <div class="school-curve school-curve-gold"></div>
        <div class="school-curve school-curve-green"></div>

    </section>

    <!-- RIGHT LOGIN SECTION -->
    <section class="login-form-area">

        <div class="login-card-modern">

            <div class="login-school-identity">

                <div class="login-logo-wrapper">
                    <img
                        src="../assets/images/logo.jpg"
                        alt="Good Shepherd Primary School logo"
                        class="login-school-logo"
                        onerror="
                            this.style.display='none';
                            document.getElementById('logoFallback').style.display='grid';
                        "
                    >

                    <div
                        class="login-logo-fallback"
                        id="logoFallback"
                    >
                        GS
                    </div>
                </div>

                <h2>
                    Good Shepherd
                </h2>

                <h3>
                    Primary School
                </h3>

                <div class="identity-line"></div>

                <p>
                    SCHOOL MANAGEMENT SYSTEM
                </p>

            </div>

            <?php if ($errorMessage !== ''): ?>
                <div
                    class="alert alert-danger login-alert"
                    role="alert"
                >
                    <i class="bi bi-exclamation-circle-fill"></i>

                    <span>
                        <?= htmlspecialchars($errorMessage) ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>

                <div class="login-field-group">
                    <label for="email">
                        Email Address
                    </label>

                    <div class="modern-input-wrapper">
                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="login-field-group">
                    <label for="password">
                        Password
                    </label>

                    <div class="modern-input-wrapper">
                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="modern-password-toggle"
                            id="togglePassword"
                            aria-label="Show or hide password"
                        >
                            <i
                                class="bi bi-eye-slash"
                                id="passwordIcon"
                            ></i>
                        </button>
                    </div>
                </div>

                <div class="login-options">

                    <label class="remember-option">
                        <input
                            type="checkbox"
                            name="remember"
                        >

                        <span>Remember me</span>
                    </label>

                    <a
                        href="#"
                        class="forgot-password-link"
                    >
                        Forgot Password?
                    </a>

                </div>

                <button
                    type="submit"
                    class="modern-signin-button"
                >
                    <i class="bi bi-lock-fill"></i>

                    <span>Sign In</span>
                </button>

            </form>

            <div class="login-divider">
                <span>Authorized staff access</span>
            </div>

            <div class="secure-login-message">
                <i class="bi bi-shield-lock"></i>

                <span>
                    Secure system. Authorized access only.
                </span>
            </div>

        </div>

        <footer class="modern-login-footer">
            &copy; <?= date('Y') ?>
            Good Shepherd Primary School. All rights reserved.
        </footer>

    </section>

</main>

<script src="../assets/js/app.js"></script>

</body>
</html>