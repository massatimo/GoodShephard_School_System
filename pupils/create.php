<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$classes = $pdo->query(
    "SELECT id, class_name
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

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['old_input']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Register Pupil | Good Shepherd Primary School</title>

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

                <h2>Register New Pupil</h2>

                <p>
                    Enter the pupil's admission, personal and
                    guardian information.
                </p>
            </div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to pupils
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
            action="store.php"
            method="POST"
            enctype="multipart/form-data"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <section class="form-section-card">
                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-card-heading"></i>
                    </span>

                    <div>
                        <h3>Admission information</h3>
                        <p>School registration and class placement</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">
                            Admission number
                        </label>

                        <input
                            type="text"
                            class="form-control professional-input"
                            value="Generated automatically"
                            readonly
                        >

                        <div class="form-text">
                            Assigned when the pupil is registered.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            EMIS number
                        </label>

                        <input
                            type="text"
                            name="emis_number"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['emis_number'] ?? ''
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
                            value="<?= htmlspecialchars(
                                $old['lin_number'] ?? ''
                            ) ?>"
                            placeholder="LIN, where available"
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
                            value="<?= htmlspecialchars(
                                $old['admission_date'] ?? date('Y-m-d')
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

                            foreach ($admissionTypes as $type):
                            ?>
                                <option
                                    value="<?= $type ?>"
                                    <?= ($old['admission_type'] ?? '')
                                        === $type ? 'selected' : '' ?>
                                >
                                    <?= $type ?>
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
                            value="<?= htmlspecialchars(
                                $old['former_school'] ?? ''
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
                            <option value="">Select class</option>

                            <?php foreach ($classes as $class): ?>
                                <option
                                    value="<?= (int) $class['id'] ?>"
                                    <?= (string) ($old['class_id'] ?? '')
                                        === (string) $class['id']
                                        ? 'selected'
                                        : '' ?>
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
                            <option value="">Select stream</option>

                            <?php foreach ($streams as $stream): ?>
                                <option
                                    value="<?= (int) $stream['id'] ?>"
                                    data-class-id="<?= (int) $stream['class_id'] ?>"
                                    <?= (string) ($old['stream_id'] ?? '')
                                        === (string) $stream['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $stream['stream_name']
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="form-section-card">
                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-person-vcard"></i>
                    </span>

                    <div>
                        <h3>Personal information</h3>
                        <p>Basic identification information</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">First name *</label>

                        <input
                            type="text"
                            name="first_name"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['first_name'] ?? ''
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Middle name</label>

                        <input
                            type="text"
                            name="middle_name"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['middle_name'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last name *</label>

                        <input
                            type="text"
                            name="last_name"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['last_name'] ?? ''
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
                                <?= ($old['gender'] ?? '') === 'Male'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= ($old['gender'] ?? '') === 'Female'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Female
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date of birth</label>

                        <input
                            type="date"
                            name="date_of_birth"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['date_of_birth'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Nationality</label>

                        <input
                            type="text"
                            name="nationality"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['nationality'] ?? 'Ugandan'
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
                            <option>Seventh-day Adventist</option>
                            <option>Catholic</option>
                            <option>Anglican</option>
                            <option>Muslim</option>
                            <option>Pentecostal</option>
                            <option>Orthodox</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pupil photograph</label>

                        <input
                            type="file"
                            name="photo"
                            class="form-control professional-input"
                            accept=".jpg,.jpeg,.png"
                        >

                        <small class="form-text text-muted">
                            JPG or PNG, maximum 2 MB.
                        </small>
                    </div>
                </div>
            </section>

            <section class="form-section-card">
                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-geo-alt-fill"></i>
                    </span>

                    <div>
                        <h3>Home location</h3>
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
                            value="<?= htmlspecialchars(
                                $old['district'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">County</label>
                        <input
                            type="text"
                            name="county"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['county'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sub-county</label>
                        <input
                            type="text"
                            name="sub_county"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['sub_county'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Parish</label>
                        <input
                            type="text"
                            name="parish"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['parish'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Village</label>
                        <input
                            type="text"
                            name="village"
                            class="form-control professional-input"
                            value="<?= htmlspecialchars(
                                $old['village'] ?? ''
                            ) ?>"
                        >
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Home address</label>

                        <textarea
                            name="home_address"
                            class="form-control professional-input"
                            rows="2"
                        ><?= htmlspecialchars(
                            $old['home_address'] ?? ''
                        ) ?></textarea>
                    </div>
                </div>
            </section>

            <section class="form-section-card">
                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-people-fill"></i>
                    </span>

                    <div>
                        <h3>Parent and guardian information</h3>
                        <p>Primary family and emergency contacts</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">Father's name</label>
                        <input
                            type="text"
                            name="father_name"
                            class="form-control professional-input"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Father's phone</label>
                        <input
                            type="tel"
                            name="father_phone"
                            class="form-control professional-input"
                            placeholder="+256..."
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Father's occupation</label>
                        <input
                            type="text"
                            name="father_occupation"
                            class="form-control professional-input"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mother's name</label>
                        <input
                            type="text"
                            name="mother_name"
                            class="form-control professional-input"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mother's phone</label>
                        <input
                            type="tel"
                            name="mother_phone"
                            class="form-control professional-input"
                            placeholder="+256..."
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mother's occupation</label>
                        <input
                            type="text"
                            name="mother_occupation"
                            class="form-control professional-input"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Guardian's name</label>
                        <input
                            type="text"
                            name="guardian_name"
                            class="form-control professional-input"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Guardian's phone</label>
                        <input
                            type="tel"
                            name="guardian_phone"
                            class="form-control professional-input"
                            placeholder="+256..."
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Guardian relationship
                        </label>

                        <select
                            name="guardian_relationship"
                            class="form-select professional-input"
                        >
                            <option value="">Select relationship</option>
                            <option>Father</option>
                            <option>Mother</option>
                            <option>Uncle</option>
                            <option>Aunt</option>
                            <option>Grandfather</option>
                            <option>Grandmother</option>
                            <option>Brother</option>
                            <option>Sister</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Orphan status</label>

                        <select
                            name="orphan_status"
                            class="form-select professional-input"
                        >
                            <option value="Not Orphan">Not Orphan</option>
                            <option value="Single Orphan - Father Deceased">
                                Single Orphan – Father Deceased
                            </option>
                            <option value="Single Orphan - Mother Deceased">
                                Single Orphan – Mother Deceased
                            </option>
                            <option value="Double Orphan">
                                Double Orphan
                            </option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="form-section-card">
                <div class="form-section-header">
                    <span class="form-section-icon">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </span>

                    <div>
                        <h3>Medical and special needs</h3>
                        <p>Information needed for pupil care and safety</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">Blood group</label>
                        <select
                            name="blood_group"
                            class="form-select professional-input"
                        >
                            <option value="">Unknown</option>
                            <option>A+</option>
                            <option>A-</option>
                            <option>B+</option>
                            <option>B-</option>
                            <option>AB+</option>
                            <option>AB-</option>
                            <option>O+</option>
                            <option>O-</option>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">
                            Medical conditions
                        </label>

                        <input
                            type="text"
                            name="medical_condition"
                            class="form-control professional-input"
                            placeholder="Example: Asthma, epilepsy"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Allergies</label>

                        <input
                            type="text"
                            name="allergies"
                            class="form-control professional-input"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">
                            Disability information
                        </label>

                        <div class="form-check form-switch pt-2">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="has_disability"
                                id="hasDisability"
                                value="1"
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
                        <label class="form-label">Disability type</label>

                        <input
                            type="text"
                            name="disability_type"
                            id="disabilityType"
                            class="form-control professional-input"
                            disabled
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Special needs or support required
                        </label>

                        <input
                            type="text"
                            name="special_needs"
                            class="form-control professional-input"
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
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Relationship
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_relationship"
                            class="form-control professional-input"
                        >
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
                    Register Pupil
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

        const selectedStream =
            streamSelect.options[streamSelect.selectedIndex];

        if (
            selectedStream &&
            selectedStream.value !== '' &&
            selectedStream.dataset.classId !== selectedClass
        ) {
            streamSelect.value = '';
        }
    }

    classSelect.addEventListener('change', filterStreams);
    filterStreams();

    const disabilityCheckbox =
        document.getElementById('hasDisability');

    const disabilityType =
        document.getElementById('disabilityType');

    disabilityCheckbox.addEventListener('change', () => {
        disabilityType.disabled = !disabilityCheckbox.checked;

        if (!disabilityCheckbox.checked) {
            disabilityType.value = '';
        }
    });
});
</script>

</body>
</html>
