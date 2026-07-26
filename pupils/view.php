<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Validate pupil ID
|--------------------------------------------------------------------------
*/

$pupilId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$pupilId || $pupilId < 1) {
    $_SESSION['error_message'] = 'Invalid pupil selected.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve pupil, class and stream information
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        pupils.*,
        classes.class_name AS assigned_class_name,
        streams.stream_name AS assigned_stream_name
    FROM pupils
    LEFT JOIN classes
        ON classes.id = pupils.class_id
    LEFT JOIN streams
        ON streams.id = pupils.stream_id
    WHERE pupils.id = :id
    LIMIT 1
';

$statement = $pdo->prepare($sql);

$statement->execute([
    'id' => $pupilId,
]);

$pupil = $statement->fetch();

if (!$pupil) {
    $_SESSION['error_message'] = 'The selected pupil was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function displayValue(
    mixed $value,
    string $fallback = 'Not provided'
): string {
    if ($value === null || trim((string) $value) === '') {
        return $fallback;
    }

    return htmlspecialchars((string) $value);
}

function formatProfileDate(mixed $date): string
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

function calculateAge(mixed $dateOfBirth): string
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

function pupilStatusClass(string $status): string
{
    return match (strtolower($status)) {
        'active' => 'profile-status-active',
        'transferred' => 'profile-status-transferred',
        'completed' => 'profile-status-completed',
        'dropped' => 'profile-status-dropped',
        'suspended' => 'profile-status-suspended',
        default => 'profile-status-default',
    };
}

/*
|--------------------------------------------------------------------------
| Prepare pupil information
|--------------------------------------------------------------------------
*/

$fullName = trim(
    ($pupil['first_name'] ?? '') . ' ' .
    ($pupil['middle_name'] ?? '') . ' ' .
    ($pupil['last_name'] ?? '')
);

$assignedClass =
    $pupil['assigned_class_name']
    ?? $pupil['class_name']
    ?? 'Not assigned';

$assignedStream =
    $pupil['assigned_stream_name']
    ?? $pupil['stream_name']
    ?? 'Not assigned';

$pupilStatus = (string) ($pupil['pupil_status'] ?? 'Active');

$hasDisability =
    (int) ($pupil['has_disability'] ?? 0) === 1
        ? 'Yes'
        : 'No';

$photoPath = null;

if (!empty($pupil['photo'])) {
    $relativePhotoPath = ltrim((string) $pupil['photo'], '/');

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

        <!-- PAGE HEADING -->

        <section class="module-heading">

            <div>
                <span class="module-label">
                    PUPIL MANAGEMENT
                </span>

                <h2>Pupil Profile</h2>

                <p>
                    Complete academic, personal, family and medical record.
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
                    href="print.php?id=<?= (int) $pupil['id'] ?>"
                    target="_blank"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-printer-fill me-2"></i>
                    Print Profile
                </a>

                <a
                    href="edit.php?id=<?= (int) $pupil['id'] ?>"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Pupil
                </a>

            </div>

        </section>

        <!-- PUPIL SUMMARY -->

        <section class="pupil-profile-summary">

            <div class="pupil-profile-photo-section">

                <?php if ($photoPath !== null): ?>

                    <img
                        src="<?= htmlspecialchars($photoPath) ?>"
                        alt="<?= htmlspecialchars($fullName) ?>"
                        class="pupil-profile-photo"
                    >

                <?php else: ?>

                    <div class="profile-placeholder">
                        <i class="bi bi-person-fill"></i>
                    </div>

                <?php endif; ?>

            </div>

            <div class="pupil-profile-summary-details">

                <span class="profile-admission-label">
                    <?= displayValue(
                        $pupil['admission_number'] ?? null
                    ) ?>
                </span>

                <h1>
                    <?= htmlspecialchars($fullName) ?>
                </h1>

                <div class="profile-summary-tags">

                    <span>
                        <i class="bi bi-mortarboard-fill"></i>
                        <?= displayValue($assignedClass) ?>
                    </span>

                    <span>
                        <i class="bi bi-diagram-3-fill"></i>
                        <?= displayValue($assignedStream) ?>
                    </span>

                    <span>
                        <i class="bi bi-gender-ambiguous"></i>
                        <?= displayValue($pupil['gender'] ?? null) ?>
                    </span>

                </div>

                <div class="profile-summary-meta">

                    <div>
                        <small>Age</small>

                        <strong>
                            <?= calculateAge(
                                $pupil['date_of_birth'] ?? null
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Admission date</small>

                        <strong>
                            <?= formatProfileDate(
                                $pupil['admission_date'] ?? null
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Status</small>

                        <span
                            class="profile-status-badge
                            <?= pupilStatusClass($pupilStatus) ?>"
                        >
                            <?= htmlspecialchars($pupilStatus) ?>
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
                            <p>Identification and personal details</p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Admission Number</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'admission_number'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>EMIS Number</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['emis_number'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Learner ID Number</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['lin_number'] ?? null
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
                                        <?= displayValue(
                                            $pupil['gender'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Date of Birth</th>

                                    <td>
                                        <?= formatProfileDate(
                                            $pupil[
                                                'date_of_birth'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Age</th>

                                    <td>
                                        <?= calculateAge(
                                            $pupil[
                                                'date_of_birth'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Nationality</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['nationality'] ?? null,
                                            'Ugandan'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Religion</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['religion'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- ACADEMIC INFORMATION -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-mortarboard-fill"></i>

                        <div>
                            <h3>Academic Information</h3>
                            <p>Admission and class placement</p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Class</th>
                                    <td><?= displayValue($assignedClass) ?></td>
                                </tr>

                                <tr>
                                    <th>Stream</th>
                                    <td><?= displayValue($assignedStream) ?></td>
                                </tr>

                                <tr>
                                    <th>Admission Date</th>

                                    <td>
                                        <?= formatProfileDate(
                                            $pupil[
                                                'admission_date'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Admission Type</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'admission_type'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Former School</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'former_school'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Pupil Status</th>

                                    <td>
                                        <span
                                            class="profile-status-badge
                                            <?= pupilStatusClass(
                                                $pupilStatus
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $pupilStatus
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Record Created</th>

                                    <td>
                                        <?= formatProfileDate(
                                            $pupil[
                                                'created_at'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Last Updated</th>

                                    <td>
                                        <?= formatProfileDate(
                                            $pupil[
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

            <!-- HOME LOCATION -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-geo-alt-fill"></i>

                        <div>
                            <h3>Home Location</h3>
                            <p>Ugandan administrative location details</p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>District</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['district'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>County</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['county'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Sub-county</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['sub_county'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Parish</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['parish'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Village</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil['village'] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Home Address</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
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

            <!-- PARENT AND GUARDIAN -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-people-fill"></i>

                        <div>
                            <h3>Parent and Guardian Information</h3>
                            <p>Family contacts and orphan status</p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Father's Name</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'father_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Father's Phone</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'father_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Father's Occupation</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'father_occupation'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Mother's Name</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'mother_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Mother's Phone</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'mother_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Mother's Occupation</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'mother_occupation'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Guardian's Name</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'guardian_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Guardian's Phone</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'guardian_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Relationship</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'guardian_relationship'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Orphan Status</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'orphan_status'
                                            ] ?? null,
                                            'Not Orphan'
                                        ) ?>
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

            </div>

            <!-- MEDICAL AND SPECIAL NEEDS -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-heart-pulse-fill"></i>

                        <div>
                            <h3>Medical and Special Needs</h3>
                            <p>Health, disability and support information</p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Blood Group</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'blood_group'
                                            ] ?? null,
                                            'Unknown'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Medical Condition</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'medical_condition'
                                            ] ?? null,
                                            'None recorded'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Allergies</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'allergies'
                                            ] ?? null,
                                            'None recorded'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Has Disability</th>

                                    <td>
                                        <?= htmlspecialchars(
                                            $hasDisability
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Disability Type</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'disability_type'
                                            ] ?? null,
                                            'None recorded'
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Special Needs</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'special_needs'
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

            <!-- EMERGENCY CONTACT -->

            <div class="col-xl-6">

                <article class="profile-table-card">

                    <div class="profile-table-header">
                        <i class="bi bi-telephone-fill"></i>

                        <div>
                            <h3>Emergency Contact</h3>
                            <p>Contact used during pupil emergencies</p>
                        </div>
                    </div>

                    <div class="table-responsive">

                        <table class="table profile-information-table">

                            <tbody>

                                <tr>
                                    <th>Contact Name</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'emergency_contact_name'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Phone Number</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'emergency_contact_phone'
                                            ] ?? null
                                        ) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Relationship</th>

                                    <td>
                                        <?= displayValue(
                                            $pupil[
                                                'emergency_contact_relationship'
                                            ] ?? null
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
                Back to Pupils
            </a>

            <div>

                <a
                    href="edit.php?id=<?= (int) $pupil['id'] ?>"
                    class="btn btn-outline-success"
                >
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit
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