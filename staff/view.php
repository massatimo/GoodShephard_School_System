<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Validate staff ID
|--------------------------------------------------------------------------
*/

$staffId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$staffId || $staffId < 1) {
    $_SESSION['error_message'] = 'Invalid staff member selected.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve the staff record
|--------------------------------------------------------------------------
*/

$statement = $pdo->prepare(
    'SELECT *
     FROM staff
     WHERE id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $staffId,
]);

$staff = $statement->fetch();

if (!$staff) {
    $_SESSION['error_message'] = 'The staff member was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function staffDisplayValue(
    mixed $value,
    string $fallback = 'Not provided'
): string {
    if ($value === null || trim((string) $value) === '') {
        return $fallback;
    }

    return htmlspecialchars((string) $value);
}

function staffFormatDate(mixed $date): string
{
    if ($date === null || trim((string) $date) === '') {
        return 'Not provided';
    }

    $timestamp = strtotime((string) $date);

    if ($timestamp === false) {
        return htmlspecialchars((string) $date);
    }

    return date('d M Y', $timestamp);
}

function staffCalculateAge(mixed $dateOfBirth): string
{
    if (
        $dateOfBirth === null ||
        trim((string) $dateOfBirth) === ''
    ) {
        return 'Not available';
    }

    try {
        $birthDate = new DateTime((string) $dateOfBirth);
        $today = new DateTime();

        if ($birthDate > $today) {
            return 'Invalid date';
        }

        $age = $birthDate->diff($today)->y;

        return $age . ($age === 1 ? ' year' : ' years');
    } catch (Exception) {
        return 'Not available';
    }
}

function employmentStatusClass(string $status): string
{
    return match (strtolower($status)) {
        'active' => 'profile-status-active',
        'on leave' => 'profile-status-on-leave',
        'retired' => 'profile-status-completed',
        'resigned' => 'profile-status-transferred',
        'suspended',
        'terminated',
        'inactive' => 'profile-status-dropped',
        default => 'profile-status-default',
    };
}

/*
|--------------------------------------------------------------------------
| Prepare staff information
|--------------------------------------------------------------------------
*/

$fullName = trim(
    ($staff['first_name'] ?? '') . ' ' .
    ($staff['middle_name'] ?? '') . ' ' .
    ($staff['last_name'] ?? '')
);

if ($fullName === '') {
    $fullName = trim(
        (string) ($staff['full_name'] ?? 'Unknown Staff Member')
    );
}

$designation =
    $staff['designation']
    ?? $staff['position']
    ?? 'Not assigned';

$employmentStatus =
    (string) ($staff['employment_status'] ?? 'Active');

$photoPath = null;

if (!empty($staff['photo'])) {
    $relativePhotoPath = ltrim(
        (string) $staff['photo'],
        '/'
    );

    $fullPhotoPath =
        __DIR__ . '/../' . $relativePhotoPath;

    if (is_file($fullPhotoPath)) {
        $photoPath = '../' . $relativePhotoPath;
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
        <?= htmlspecialchars($fullName) ?>
        | Good Shepherd Primary School
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

        <section class="module-heading">

            <div>
                <span class="module-label">
                    HUMAN RESOURCE MANAGEMENT
                </span>

                <h2>Staff Profile</h2>

                <p>
                    Complete personal, employment, qualification
                    and contact information.
                </p>
            </div>

            <div class="profile-heading-actions">

                <a
                    href="index.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Back
                </a>

                <a
                    href="edit.php?id=<?= (int) $staff['id'] ?>"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Staff
                </a>

            </div>

        </section>

        <?php
        $successMessage = $_SESSION['success_message'] ?? '';
        unset($_SESSION['success_message']);
        ?>

        <?php if ($successMessage !== ''): ?>

            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>

                <?= htmlspecialchars($successMessage) ?>
            </div>

        <?php endif; ?>

        <!-- STAFF SUMMARY -->

        <section class="staff-profile-summary">

            <div class="staff-profile-photo-section">

                <?php if ($photoPath !== null): ?>

                    <img
                        src="<?= htmlspecialchars($photoPath) ?>"
                        alt="<?= htmlspecialchars($fullName) ?>"
                        class="staff-profile-photo"
                    >

                <?php else: ?>

                    <div class="staff-profile-placeholder">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>

                <?php endif; ?>

            </div>

            <div class="staff-profile-summary-details">

                <span class="profile-admission-label">
                    <?= staffDisplayValue(
                        $staff['staff_number'] ?? null
                    ) ?>
                </span>

                <h1>
                    <?= htmlspecialchars($fullName) ?>
                </h1>

                <div class="profile-summary-tags">

                    <span>
                        <i class="bi bi-briefcase-fill"></i>

                        <?= staffDisplayValue($designation) ?>
                    </span>

                    <span>
                        <i class="bi bi-people-fill"></i>

                        <?= staffDisplayValue(
                            $staff['staff_category'] ?? null
                        ) ?>
                    </span>

                    <span>
                        <i class="bi bi-building-fill"></i>

                        <?= staffDisplayValue(
                            $staff['department'] ?? null
                        ) ?>
                    </span>

                </div>

                <div class="profile-summary-meta">

                    <div>
                        <small>Employment Type</small>

                        <strong>
                            <?= staffDisplayValue(
                                $staff['employment_type'] ?? null
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Appointment Date</small>

                        <strong>
                            <?= staffFormatDate(
                                $staff['appointment_date'] ?? null
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Status</small>

                        <span
                            class="profile-status-badge
                            <?= employmentStatusClass(
                                $employmentStatus
                            ) ?>"
                        >
                            <?= htmlspecialchars($employmentStatus) ?>
                        </span>
                    </div>

                </div>

            </div>

        </section>

        <!-- INFORMATION TABLES -->

        <section class="row g-4 mt-1">

            <!-- PERSONAL INFORMATION -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-person-vcard-fill"></i>

                        <div>
                            <h3>Personal Information</h3>

                            <p>
                                Identity and personal details
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Staff Number</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'staff_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Full Name</th>

                                    <td>
                                        <?= htmlspecialchars($fullName) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Gender</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff['gender'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Date of Birth</th>

                                    <td>
                                        <?= staffFormatDate(
                                            $staff[
                                                'date_of_birth'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Age</th>

                                    <td>
                                        <?= staffCalculateAge(
                                            $staff[
                                                'date_of_birth'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Nationality</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'nationality'
                                            ] ?? null,
                                            'Ugandan'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Religion</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff['religion'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>National ID/NIN</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'national_id_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Marital Status</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'marital_status'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- CONTACT INFORMATION -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-telephone-fill"></i>

                        <div>
                            <h3>Contact Information</h3>

                            <p>
                                Telephone and email details
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Primary Phone</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff['phone'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Alternative Phone</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'alternative_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Email Address</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff['email'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>District</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff['district'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Sub-county</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'sub_county'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Village</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff['village'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Home Address</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'home_address'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- EMPLOYMENT INFORMATION -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-briefcase-fill"></i>

                        <div>
                            <h3>Employment Information</h3>

                            <p>
                                Appointment and job information
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Staff Category</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'staff_category'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Department</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'department'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Designation</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $designation
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Employment Type</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'employment_type'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Appointment Date</th>

                                    <td>
                                        <?= staffFormatDate(
                                            $staff[
                                                'appointment_date'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Employment Status</th>

                                    <td>
                                        <span
                                            class="profile-status-badge
                                            <?= employmentStatusClass(
                                                $employmentStatus
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $employmentStatus
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Record Created</th>

                                    <td>
                                        <?= staffFormatDate(
                                            $staff[
                                                'created_at'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Last Updated</th>

                                    <td>
                                        <?= staffFormatDate(
                                            $staff[
                                                'updated_at'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- QUALIFICATIONS -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-mortarboard-fill"></i>

                        <div>
                            <h3>Qualifications and Registration</h3>

                            <p>
                                Academic and professional credentials
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Highest Qualification</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'qualification'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Specialization</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'specialization'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Teaching Registration Number</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'teaching_registration_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>TIN Number</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'tin_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>NSSF Number</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'nssf_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- BANK INFORMATION -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-bank"></i>

                        <div>
                            <h3>Bank Information</h3>

                            <p>
                                Salary payment account details
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Bank Name</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'bank_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Account Name</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'bank_account_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Account Number</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'bank_account_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- NEXT OF KIN -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-people-fill"></i>

                        <div>
                            <h3>Next of Kin and Emergency Contact</h3>

                            <p>
                                Family and emergency contact details
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Next of Kin Name</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'next_of_kin_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Next of Kin Phone</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'next_of_kin_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Relationship</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'next_of_kin_relationship'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Emergency Contact Name</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'emergency_contact_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Emergency Contact Phone</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'emergency_contact_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- MEDICAL INFORMATION -->

            <div class="col-xl-12">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-heart-pulse-fill"></i>

                        <div>
                            <h3>Medical Information</h3>

                            <p>
                                Important staff health information
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Medical Information</th>

                                    <td>
                                        <?= staffDisplayValue(
                                            $staff[
                                                'medical_information'
                                            ] ?? null,
                                            'None recorded'
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

        </section>

        <!-- ACTION BUTTONS -->

        <section class="profile-bottom-actions">

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Staff
            </a>

            <div>

                <a
                    href="edit.php?id=<?= (int) $staff['id'] ?>"
                    class="btn btn-outline-success"
                >
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit
                </a>

                <a
                    href="print.php?id=<?= (int) $staff['id'] ?>"
                    target="_blank"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-printer-fill me-2"></i>
                    Print Profile
                </a>

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