<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['old_input']);

function oldValue(
    array $old,
    string $field,
    string $default = ''
): string {
    return htmlspecialchars(
        (string) ($old[$field] ?? $default)
    );
}

function oldSelected(
    array $old,
    string $field,
    string $value
): string {
    return ($old[$field] ?? '') === $value
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
        Register Staff | Good Shepherd Primary School
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

                <h2>Register Staff Member</h2>

                <p>
                    Enter personal, employment, qualification
                    and contact information.
                </p>
            </div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Staff
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
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <!-- PERSONAL INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-person-vcard-fill"></i>
                    </span>

                    <div>
                        <h3>Personal Information</h3>
                        <p>Staff identity and personal details</p>
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
                            value="<?= oldValue(
                                $old,
                                'staff_number'
                            ) ?>"
                            placeholder="Example: GS-T001"
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
                            value="<?= oldValue(
                                $old,
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
                            value="<?= oldValue(
                                $old,
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
                            value="<?= oldValue(
                                $old,
                                'last_name'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Gender *
                        </label>

                        <select
                            name="gender"
                            class="form-select professional-input"
                            required
                        >
                            <option value="">Select gender</option>

                            <option
                                value="Male"
                                <?= oldSelected(
                                    $old,
                                    'gender',
                                    'Male'
                                ) ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= oldSelected(
                                    $old,
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
                            value="<?= oldValue(
                                $old,
                                'date_of_birth'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Nationality
                        </label>

                        <input
                            type="text"
                            name="nationality"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'nationality',
                                'Ugandan'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Religion
                        </label>

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
                                    value="<?= htmlspecialchars(
                                        $religion
                                    ) ?>"
                                    <?= oldSelected(
                                        $old,
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
                            National ID Number/NIN
                        </label>

                        <input
                            type="text"
                            name="national_id_number"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
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
                            <option value="">
                                Select status
                            </option>

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
                                    <?= oldSelected(
                                        $old,
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
                            Passport photograph
                        </label>

                        <input
                            type="file"
                            name="photo"
                            class="form-control professional-input"
                            accept=".jpg,.jpeg,.png"
                        >

                        <small class="text-muted">
                            JPG or PNG, maximum 2 MB.
                        </small>
                    </div>

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
                        <p>Telephone, email and home location</p>
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
                            value="<?= oldValue($old, 'phone') ?>"
                            placeholder="+256..."
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
                            value="<?= oldValue(
                                $old,
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
                            value="<?= oldValue($old, 'email') ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">District</label>

                        <input
                            type="text"
                            name="district"
                            class="form-control professional-input"
                            value="<?= oldValue($old, 'district') ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sub-county</label>

                        <input
                            type="text"
                            name="sub_county"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
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
                            value="<?= oldValue($old, 'village') ?>"
                        >
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">
                            Home address
                        </label>

                        <textarea
                            name="home_address"
                            class="form-control professional-input"
                            rows="2"
                        ><?= oldValue(
                            $old,
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
                        <p>Role, appointment and employment status</p>
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
                            <option value="">
                                Select category
                            </option>

                            <option
                                value="Teaching Staff"
                                <?= oldSelected(
                                    $old,
                                    'staff_category',
                                    'Teaching Staff'
                                ) ?>
                            >
                                Teaching Staff
                            </option>

                            <option
                                value="Non-Teaching Staff"
                                <?= oldSelected(
                                    $old,
                                    'staff_category',
                                    'Non-Teaching Staff'
                                ) ?>
                            >
                                Non-Teaching Staff
                            </option>

                            <option
                                value="Administration"
                                <?= oldSelected(
                                    $old,
                                    'staff_category',
                                    'Administration'
                                ) ?>
                            >
                                Administration
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Department
                        </label>

                        <input
                            type="text"
                            name="department"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'department'
                            ) ?>"
                            placeholder="Example: Academics"
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
                            value="<?= oldValue(
                                $old,
                                'designation'
                            ) ?>"
                            placeholder="Example: Class Teacher"
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
                                    <?= oldSelected(
                                        $old,
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
                            value="<?= oldValue(
                                $old,
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
                                    <?= oldSelected(
                                        $old,
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

            <!-- QUALIFICATION AND REGISTRATION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </span>

                    <div>
                        <h3>Qualifications and Registration</h3>
                        <p>Academic and professional information</p>
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
                            value="<?= oldValue(
                                $old,
                                'qualification'
                            ) ?>"
                            placeholder="Example: Diploma in Primary Education"
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
                            value="<?= oldValue(
                                $old,
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
                            value="<?= oldValue(
                                $old,
                                'teaching_registration_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            TIN number
                        </label>

                        <input
                            type="text"
                            name="tin_number"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'tin_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            NSSF number
                        </label>

                        <input
                            type="text"
                            name="nssf_number"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
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
                        <p>Payment and emergency contact details</p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label">
                            Bank name
                        </label>

                        <input
                            type="text"
                            name="bank_name"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'bank_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Account name
                        </label>

                        <input
                            type="text"
                            name="bank_account_name"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'bank_account_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Account number
                        </label>

                        <input
                            type="text"
                            name="bank_account_number"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'bank_account_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Next of kin name
                        </label>

                        <input
                            type="text"
                            name="next_of_kin_name"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'next_of_kin_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Next of kin phone
                        </label>

                        <input
                            type="tel"
                            name="next_of_kin_phone"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'next_of_kin_phone'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Relationship
                        </label>

                        <input
                            type="text"
                            name="next_of_kin_relationship"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'next_of_kin_relationship'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Emergency contact name
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_name"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'emergency_contact_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Emergency contact phone
                        </label>

                        <input
                            type="tel"
                            name="emergency_contact_phone"
                            class="form-control professional-input"
                            value="<?= oldValue(
                                $old,
                                'emergency_contact_phone'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">
                            Medical information
                        </label>

                        <textarea
                            name="medical_information"
                            class="form-control professional-input"
                            rows="2"
                        ><?= oldValue(
                            $old,
                            'medical_information'
                        ) ?></textarea>
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
                    Register Staff
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