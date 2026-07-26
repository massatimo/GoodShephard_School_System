<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$staffId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$staffId || $staffId < 1) {
    $_SESSION['error_message'] = 'Invalid staff member selected.';

    header('Location: index.php');
    exit;
}

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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['old_input']);

function staffEditValue(
    array $old,
    array $staff,
    string $field,
    mixed $default = ''
): string {
    $value = $old[$field] ?? $staff[$field] ?? $default;

    return htmlspecialchars((string) $value);
}

function staffEditSelected(
    array $old,
    array $staff,
    string $field,
    string $value
): string {
    $currentValue = $old[$field] ?? $staff[$field] ?? '';

    return (string) $currentValue === $value
        ? 'selected'
        : '';
}

$photoPath = null;

if (!empty($staff['photo'])) {
    $relativePhotoPath = ltrim((string) $staff['photo'], '/');

    if (is_file(__DIR__ . '/../' . $relativePhotoPath)) {
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
        Edit Staff | Good Shepherd Primary School
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

                <h2>Edit Staff Member</h2>

                <p>
                    Update personal, employment and professional information.
                </p>
            </div>

            <a
                href="view.php?id=<?= (int) $staff['id'] ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Profile
            </a>

        </section>

        <?php if ($errors !== []): ?>

            <div class="alert alert-danger">

                <strong>
                    Correct the following information:
                </strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <form
            action="update.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="staff_id"
                value="<?= (int) $staff['id'] ?>"
            >

            <!-- PERSONAL INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-person-vcard-fill"></i>
                    </span>

                    <div>
                        <h3>Personal Information</h3>
                        <p>Identity and personal details</p>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-md-3">
                        <label class="form-label">
                            Staff number *
                        </label>

                        <input
                            type="text"
                            name="staff_number"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'staff_number'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            First name *
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'first_name'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Middle name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'middle_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Last name *
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'last_name'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Gender *</label>

                        <select
                            name="gender"
                            class="form-select professional-input"
                            required
                        >
                            <option value="">Select gender</option>

                            <option
                                value="Male"
                                <?= staffEditSelected(
                                    $old,
                                    $staff,
                                    'gender',
                                    'Male'
                                ) ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= staffEditSelected(
                                    $old,
                                    $staff,
                                    'gender',
                                    'Female'
                                ) ?>
                            >
                                Female
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Date of birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'date_of_birth'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Nationality</label>

                        <input
                            type="text"
                            name="nationality"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'nationality',
                                'Ugandan'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Religion</label>

                        <select
                            name="religion"
                            class="form-select professional-input"
                        >
                            <option value="">Select religion</option>

                            <?php
                            $religions = [
                                'Seventh-day Adventist',
                                'Catholic',
                                'Anglican',
                                'Muslim',
                                'Pentecostal',
                                'Orthodox',
                                'Other',
                            ];
                            ?>

                            <?php foreach ($religions as $religion): ?>
                                <option
                                    value="<?= htmlspecialchars($religion) ?>"
                                    <?= staffEditSelected(
                                        $old,
                                        $staff,
                                        'religion',
                                        $religion
                                    ) ?>
                                >
                                    <?= htmlspecialchars($religion) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            National ID/NIN
                        </label>

                        <input
                            type="text"
                            name="national_id_number"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'national_id_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Marital status
                        </label>

                        <select
                            name="marital_status"
                            class="form-select professional-input"
                        >
                            <option value="">Select status</option>

                            <?php
                            $maritalStatuses = [
                                'Single',
                                'Married',
                                'Divorced',
                                'Widowed',
                                'Separated',
                            ];
                            ?>

                            <?php foreach ($maritalStatuses as $status): ?>
                                <option
                                    value="<?= $status ?>"
                                    <?= staffEditSelected(
                                        $old,
                                        $staff,
                                        'marital_status',
                                        $status
                                    ) ?>
                                >
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Replace photograph
                        </label>

                        <input
                            type="file"
                            name="photo"
                            class="form-control professional-input"
                            accept=".jpg,.jpeg,.png"
                        >

                        <small class="text-muted">
                            Leave empty to keep the current photograph.
                        </small>
                    </div>

                    <?php if ($photoPath !== null): ?>
                        <div class="col-md-4">
                            <label class="form-label d-block">
                                Current photograph
                            </label>

                            <img
                                src="<?= htmlspecialchars($photoPath) ?>"
                                alt="Staff photograph"
                                class="edit-pupil-photo-preview"
                            >
                        </div>
                    <?php endif; ?>

                </div>

            </section>

            <!-- CONTACT AND LOCATION -->

            <section class="form-section-card">

                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-telephone-fill"></i>
                    </span>

                    <div>
                        <h3>Contact and Location</h3>
                        <p>Telephone, email and residential information</p>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label">
                            Primary phone *
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'phone'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Alternative phone
                        </label>

                        <input
                            type="tel"
                            name="alternative_phone"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'alternative_phone'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Email address
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'email'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">District</label>

                        <input
                            type="text"
                            name="district"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'district'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sub-county</label>

                        <input
                            type="text"
                            name="sub_county"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'sub_county'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Village</label>

                        <input
                            type="text"
                            name="village"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'village'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Home address</label>

                        <textarea
                            name="home_address"
                            class="form-control professional-input"
                            rows="2"
                        ><?= staffEditValue(
                            $old,
                            $staff,
                            'home_address'
                        ) ?></textarea>
                    </div>

                </div>

            </section>

            <!-- EMPLOYMENT INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-briefcase-fill"></i>
                    </span>

                    <div>
                        <h3>Employment Information</h3>
                        <p>Job role, category and appointment details</p>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label">
                            Staff category *
                        </label>

                        <select
                            name="staff_category"
                            class="form-select professional-input"
                            required
                        >
                            <?php
                            $categories = [
                                'Teaching Staff',
                                'Non-Teaching Staff',
                                'Administration',
                            ];
                            ?>

                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?= $category ?>"
                                    <?= staffEditSelected(
                                        $old,
                                        $staff,
                                        'staff_category',
                                        $category
                                    ) ?>
                                >
                                    <?= $category ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Department</label>

                        <input
                            type="text"
                            name="department"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'department'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Designation *
                        </label>

                        <input
                            type="text"
                            name="designation"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'designation'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Employment type *
                        </label>

                        <select
                            name="employment_type"
                            class="form-select professional-input"
                            required
                        >
                            <?php
                            $employmentTypes = [
                                'Permanent',
                                'Contract',
                                'Part-Time',
                                'Volunteer',
                                'Probation',
                            ];
                            ?>

                            <?php foreach ($employmentTypes as $type): ?>
                                <option
                                    value="<?= $type ?>"
                                    <?= staffEditSelected(
                                        $old,
                                        $staff,
                                        'employment_type',
                                        $type
                                    ) ?>
                                >
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Appointment date
                        </label>

                        <input
                            type="date"
                            name="appointment_date"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'appointment_date'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Employment status *
                        </label>

                        <select
                            name="employment_status"
                            class="form-select professional-input"
                            required
                        >
                            <?php
                            $employmentStatuses = [
                                'Active',
                                'On Leave',
                                'Suspended',
                                'Retired',
                                'Resigned',
                                'Terminated',
                                'Inactive',
                            ];
                            ?>

                            <?php foreach ($employmentStatuses as $status): ?>
                                <option
                                    value="<?= $status ?>"
                                    <?= staffEditSelected(
                                        $old,
                                        $staff,
                                        'employment_status',
                                        $status
                                    ) ?>
                                >
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

            </section>

            <!-- QUALIFICATIONS -->

            <section class="form-section-card">

                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </span>

                    <div>
                        <h3>Qualifications and Registration</h3>
                        <p>Academic and professional credentials</p>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label">
                            Highest qualification
                        </label>

                        <input
                            type="text"
                            name="qualification"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'qualification'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Specialization
                        </label>

                        <input
                            type="text"
                            name="specialization"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'specialization'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Teaching registration number
                        </label>

                        <input
                            type="text"
                            name="teaching_registration_number"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'teaching_registration_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">TIN number</label>

                        <input
                            type="text"
                            name="tin_number"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'tin_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">NSSF number</label>

                        <input
                            type="text"
                            name="nssf_number"
                            class="form-control professional-input"
                            value="<?= staffEditValue(
                                $old,
                                $staff,
                                'nssf_number'
                            ) ?>"
                        >
                    </div>

                </div>

            </section>

            <!-- BANK AND NEXT OF KIN -->

            <section class="form-section-card">

                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-bank"></i>
                    </span>

                    <div>
                        <h3>Bank and Next of Kin</h3>
                        <p>Payment and emergency contact information</p>
                    </div>
                </div>

                <div class="row g-4">

                    <?php
                    $textFields = [
                        'bank_name' => 'Bank name',
                        'bank_account_name' => 'Account name',
                        'bank_account_number' => 'Account number',
                        'next_of_kin_name' => 'Next of kin name',
                        'next_of_kin_phone' => 'Next of kin phone',
                        'next_of_kin_relationship' => 'Relationship',
                        'emergency_contact_name' => 'Emergency contact name',
                        'emergency_contact_phone' => 'Emergency contact phone',
                    ];
                    ?>

                    <?php foreach ($textFields as $field => $label): ?>

                        <div class="col-md-4">
                            <label class="form-label">
                                <?= htmlspecialchars($label) ?>
                            </label>

                            <input
                                type="text"
                                name="<?= htmlspecialchars($field) ?>"
                                class="form-control professional-input"
                                value="<?= staffEditValue(
                                    $old,
                                    $staff,
                                    $field
                                ) ?>"
                            >
                        </div>

                    <?php endforeach; ?>

                    <div class="col-md-12">
                        <label class="form-label">
                            Medical information
                        </label>

                        <textarea
                            name="medical_information"
                            class="form-control professional-input"
                            rows="2"
                        ><?= staffEditValue(
                            $old,
                            $staff,
                            'medical_information'
                        ) ?></textarea>
                    </div>

                </div>

            </section>

            <div class="form-submit-bar">

                <a
                    href="view.php?id=<?= (int) $staff['id'] ?>"
                    class="btn btn-light"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Save Changes
                </button>

            </div>

        </form>

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