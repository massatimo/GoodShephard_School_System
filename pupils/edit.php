<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Validate pupil ID
|--------------------------------------------------------------------------
*/

$pupilId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pupilId || $pupilId < 1) {
    $_SESSION['error_message'] = 'Invalid pupil selected.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve pupil
|--------------------------------------------------------------------------
*/

$pupilStatement = $pdo->prepare(
    'SELECT *
     FROM pupils
     WHERE id = :id
     LIMIT 1'
);

$pupilStatement->execute([
    'id' => $pupilId,
]);

$pupil = $pupilStatement->fetch();

if (!$pupil) {
    $_SESSION['error_message'] = 'The pupil was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Create CSRF token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Retrieve classes and streams
|--------------------------------------------------------------------------
*/

$classes = $pdo->query(
    "SELECT
        id,
        class_name,
        class_level
     FROM classes
     WHERE status = 'Active'
     ORDER BY class_level"
)->fetchAll();

$streams = $pdo->query(
    "SELECT
        streams.id,
        streams.class_id,
        streams.stream_name,
        classes.class_name
     FROM streams
     INNER JOIN classes
        ON classes.id = streams.class_id
     WHERE streams.status = 'Active'
     ORDER BY classes.class_level, streams.stream_name"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Retrieve validation errors and old form data
|--------------------------------------------------------------------------
*/

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['old_input']);

function editValue(
    array $old,
    array $pupil,
    string $field,
    mixed $default = ''
): string {
    $value = $old[$field] ?? $pupil[$field] ?? $default;

    return htmlspecialchars((string) $value);
}

function editSelected(
    array $old,
    array $pupil,
    string $field,
    string|int $value
): string {
    $currentValue = $old[$field] ?? $pupil[$field] ?? '';

    return (string) $currentValue === (string) $value
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
        Edit Pupil | Good Shepherd Primary School
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
                    PUPIL MANAGEMENT
                </span>

                <h2>Edit Pupil</h2>

                <p>
                    Update the pupil's registration and personal information.
                </p>
            </div>

            <a
                href="view.php?id=<?= (int) $pupil['id'] ?>"
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
            enctype="multipart/form-data"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="pupil_id"
                value="<?= (int) $pupil['id'] ?>"
            >

            <!-- ADMISSION INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-card-heading"></i>
                    </span>

                    <div>
                        <h3>Admission Information</h3>

                        <p>
                            Registration, class and stream placement
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label">
                            Admission number *
                        </label>

                        <input
                            type="text"
                            name="admission_number"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'admission_number'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            EMIS number
                        </label>

                        <input
                            type="text"
                            name="emis_number"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'emis_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Learner Identification Number
                        </label>

                        <input
                            type="text"
                            name="lin_number"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'lin_number'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Admission date *
                        </label>

                        <input
                            type="date"
                            name="admission_date"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'admission_date'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Admission type *
                        </label>

                        <select
                            name="admission_type"
                            class="form-select professional-input"
                            required
                        >
                            <?php
                            $admissionTypes = [
                                'New Admission',
                                'Transfer',
                                'Re-admission',
                            ];
                            ?>

                            <?php foreach ($admissionTypes as $type): ?>
                                <option
                                    value="<?= htmlspecialchars($type) ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'admission_type',
                                        $type
                                    ) ?>
                                >
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Former school
                        </label>

                        <input
                            type="text"
                            name="former_school"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'former_school'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Class *
                        </label>

                        <select
                            name="class_id"
                            id="classId"
                            class="form-select professional-input"
                            required
                        >
                            <option value="">
                                Select class
                            </option>

                            <?php foreach ($classes as $class): ?>
                                <option
                                    value="<?= (int) $class['id'] ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'class_id',
                                        $class['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $class['class_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Stream
                        </label>

                        <select
                            name="stream_id"
                            id="streamId"
                            class="form-select professional-input"
                        >
                            <option value="">
                                Select stream
                            </option>

                            <?php foreach ($streams as $stream): ?>
                                <option
                                    value="<?= (int) $stream['id'] ?>"
                                    data-class-id="<?= (int) $stream['class_id'] ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'stream_id',
                                        $stream['id']
                                    ) ?>
                                >
                                    <?= htmlspecialchars(
                                        $stream['stream_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Pupil status *
                        </label>

                        <select
                            name="pupil_status"
                            class="form-select professional-input"
                            required
                        >
                            <?php
                            $statuses = [
                                'Active',
                                'Transferred',
                                'Completed',
                                'Dropped',
                            ];
                            ?>

                            <?php foreach ($statuses as $status): ?>
                                <option
                                    value="<?= htmlspecialchars($status) ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'pupil_status',
                                        $status
                                    ) ?>
                                >
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

            </section>

            <!-- PERSONAL INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-person-vcard-fill"></i>
                    </span>

                    <div>
                        <h3>Personal Information</h3>
                        <p>Identification and personal details</p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label">
                            First name *
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'first_name'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Middle name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'middle_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Last name *
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
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
                            <option value="">
                                Select gender
                            </option>

                            <option
                                value="Male"
                                <?= editSelected(
                                    $old,
                                    $pupil,
                                    'gender',
                                    'Male'
                                ) ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= editSelected(
                                    $old,
                                    $pupil,
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
                            value="<?= editValue(
                                $old,
                                $pupil,
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
                            value="<?= editValue(
                                $old,
                                $pupil,
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
                            <option value="">
                                Select religion
                            </option>

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
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'religion',
                                        $religion
                                    ) ?>
                                >
                                    <?= htmlspecialchars($religion) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Replace pupil photograph
                        </label>

                        <input
                            type="file"
                            name="photo"
                            class="form-control professional-input"
                            accept=".jpg,.jpeg,.png"
                        >

                        <small class="text-muted">
                            Leave empty to retain the existing photograph.
                        </small>
                    </div>

                    <?php if (!empty($pupil['photo'])): ?>
                        <div class="col-md-6">
                            <label class="form-label d-block">
                                Current photograph
                            </label>

                            <img
                                src="../<?= htmlspecialchars(
                                    ltrim((string) $pupil['photo'], '/')
                                ) ?>"
                                alt="Current pupil photograph"
                                class="edit-pupil-photo-preview"
                            >
                        </div>
                    <?php endif; ?>

                </div>

            </section>

            <!-- LOCATION INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-geo-alt-fill"></i>
                    </span>

                    <div>
                        <h3>Home Location</h3>
                        <p>Ugandan administrative location information</p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-4">
                        <label class="form-label">District</label>

                        <input
                            type="text"
                            name="district"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'district'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">County</label>

                        <input
                            type="text"
                            name="county"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'county'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sub-county</label>

                        <input
                            type="text"
                            name="sub_county"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'sub_county'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Parish</label>

                        <input
                            type="text"
                            name="parish"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'parish'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Village</label>

                        <input
                            type="text"
                            name="village"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
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
                        ><?= editValue(
                            $old,
                            $pupil,
                            'home_address'
                        ) ?></textarea>
                    </div>

                </div>

            </section>

            <!-- PARENT AND GUARDIAN -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-people-fill"></i>
                    </span>

                    <div>
                        <h3>Parent and Guardian Information</h3>
                        <p>Family contacts and orphan status</p>
                    </div>

                </div>

                <div class="row g-4">

                    <?php
                    $familyFields = [
                        'father_name' => "Father's name",
                        'father_phone' => "Father's phone",
                        'father_occupation' => "Father's occupation",
                        'mother_name' => "Mother's name",
                        'mother_phone' => "Mother's phone",
                        'mother_occupation' => "Mother's occupation",
                        'guardian_name' => "Guardian's name",
                        'guardian_phone' => "Guardian's phone",
                    ];
                    ?>

                    <?php foreach ($familyFields as $field => $label): ?>
                        <div class="col-md-4">
                            <label class="form-label">
                                <?= htmlspecialchars($label) ?>
                            </label>

                            <input
                                type="text"
                                name="<?= htmlspecialchars($field) ?>"
                                class="form-control professional-input"
                                value="<?= editValue(
                                    $old,
                                    $pupil,
                                    $field
                                ) ?>"
                            >
                        </div>
                    <?php endforeach; ?>

                    <div class="col-md-4">
                        <label class="form-label">
                            Guardian relationship
                        </label>

                        <select
                            name="guardian_relationship"
                            class="form-select professional-input"
                        >
                            <option value="">
                                Select relationship
                            </option>

                            <?php
                            $relationships = [
                                'Father',
                                'Mother',
                                'Uncle',
                                'Aunt',
                                'Grandfather',
                                'Grandmother',
                                'Brother',
                                'Sister',
                                'Other',
                            ];
                            ?>

                            <?php foreach ($relationships as $relationship): ?>
                                <option
                                    value="<?= htmlspecialchars(
                                        $relationship
                                    ) ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'guardian_relationship',
                                        $relationship
                                    ) ?>
                                >
                                    <?= htmlspecialchars($relationship) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Orphan status *
                        </label>

                        <select
                            name="orphan_status"
                            class="form-select professional-input"
                            required
                        >
                            <?php
                            $orphanStatuses = [
                                'Not Orphan',
                                'Single Orphan - Father Deceased',
                                'Single Orphan - Mother Deceased',
                                'Double Orphan',
                            ];
                            ?>

                            <?php foreach ($orphanStatuses as $status): ?>
                                <option
                                    value="<?= htmlspecialchars($status) ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'orphan_status',
                                        $status
                                    ) ?>
                                >
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

            </section>

            <!-- MEDICAL INFORMATION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </span>

                    <div>
                        <h3>Medical and Special Needs</h3>
                        <p>Health, disability and emergency information</p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-3">
                        <label class="form-label">
                            Blood group
                        </label>

                        <select
                            name="blood_group"
                            class="form-select professional-input"
                        >
                            <option value="">Unknown</option>

                            <?php
                            $bloodGroups = [
                                'A+',
                                'A-',
                                'B+',
                                'B-',
                                'AB+',
                                'AB-',
                                'O+',
                                'O-',
                            ];
                            ?>

                            <?php foreach ($bloodGroups as $group): ?>
                                <option
                                    value="<?= $group ?>"
                                    <?= editSelected(
                                        $old,
                                        $pupil,
                                        'blood_group',
                                        $group
                                    ) ?>
                                >
                                    <?= $group ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">
                            Medical condition
                        </label>

                        <input
                            type="text"
                            name="medical_condition"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'medical_condition'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Allergies
                        </label>

                        <input
                            type="text"
                            name="allergies"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'allergies'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">
                            Disability
                        </label>

                        <div class="form-check form-switch pt-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="has_disability"
                                id="hasDisability"
                                value="1"
                                <?= (
                                    (int) (
                                        $old['has_disability']
                                        ?? $pupil['has_disability']
                                        ?? 0
                                    ) === 1
                                ) ? 'checked' : '' ?>
                            >

                            <label
                                class="form-check-label"
                                for="hasDisability"
                            >
                                Pupil has a disability
                            </label>

                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Disability type
                        </label>

                        <input
                            type="text"
                            name="disability_type"
                            id="disabilityType"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'disability_type'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Special needs
                        </label>

                        <input
                            type="text"
                            name="special_needs"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'special_needs'
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
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'emergency_contact_name'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Emergency contact phone
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_phone"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'emergency_contact_phone'
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Emergency contact relationship
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_relationship"
                            class="form-control professional-input"
                            value="<?= editValue(
                                $old,
                                $pupil,
                                'emergency_contact_relationship'
                            ) ?>"
                        >
                    </div>

                </div>

            </section>

            <div class="form-submit-bar">

                <a
                    href="view.php?id=<?= (int) $pupil['id'] ?>"
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const classSelect = document.getElementById('classId');
    const streamSelect = document.getElementById('streamId');

    if (classSelect && streamSelect) {
        const streamOptions = Array.from(streamSelect.options);

        function filterStreams() {
            const selectedClass = classSelect.value;

            streamOptions.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden =
                    option.dataset.classId !== selectedClass;
            });

            const selectedOption =
                streamSelect.options[streamSelect.selectedIndex];

            if (
                selectedOption &&
                selectedOption.value !== '' &&
                selectedOption.dataset.classId !== selectedClass
            ) {
                streamSelect.value = '';
            }
        }

        classSelect.addEventListener('change', filterStreams);
        filterStreams();
    }

    const disabilityCheckbox =
        document.getElementById('hasDisability');

    const disabilityType =
        document.getElementById('disabilityType');

    if (disabilityCheckbox && disabilityType) {
        function updateDisabilityField() {
            disabilityType.disabled =
                !disabilityCheckbox.checked;

            if (!disabilityCheckbox.checked) {
                disabilityType.value = '';
            }
        }

        disabilityCheckbox.addEventListener(
            'change',
            updateDisabilityField
        );

        updateDisabilityField();
    }
});
</script>

</body>
</html>