<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$pupilId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pupilId || $pupilId < 1) {
    exit('Invalid pupil selected.');
}

$statement = $pdo->prepare(
    'SELECT
        pupils.*,
        classes.class_name AS assigned_class_name,
        streams.stream_name AS assigned_stream_name
     FROM pupils
     LEFT JOIN classes
        ON classes.id = pupils.class_id
     LEFT JOIN streams
        ON streams.id = pupils.stream_id
     WHERE pupils.id = :id
     LIMIT 1'
);

$statement->execute([
    'id' => $pupilId,
]);

$pupil = $statement->fetch();

if (!$pupil) {
    exit('Pupil not found.');
}

function printValue(
    mixed $value,
    string $fallback = 'Not provided'
): string {
    if ($value === null || trim((string) $value) === '') {
        return $fallback;
    }

    return htmlspecialchars((string) $value);
}

function printDate(mixed $date): string
{
    if (!$date) {
        return 'Not provided';
    }

    $timestamp = strtotime((string) $date);

    return $timestamp
        ? date('d M Y', $timestamp)
        : htmlspecialchars((string) $date);
}

$fullName = trim(
    ($pupil['first_name'] ?? '') . ' ' .
    ($pupil['middle_name'] ?? '') . ' ' .
    ($pupil['last_name'] ?? '')
);

$className =
    $pupil['assigned_class_name']
    ?? $pupil['class_name']
    ?? 'Not assigned';

$streamName =
    $pupil['assigned_stream_name']
    ?? $pupil['stream_name']
    ?? 'Not assigned';

$photoUrl = null;

if (!empty($pupil['photo'])) {
    $file =
        __DIR__ . '/../' .
        ltrim((string) $pupil['photo'], '/');

    if (is_file($file)) {
        $photoUrl =
            '../' . ltrim((string) $pupil['photo'], '/');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Pupil Profile - <?= htmlspecialchars($fullName) ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            background: #eef1f4;
            font-family: Arial, Helvetica, sans-serif;
        }

        .print-toolbar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 18px;
        }

        .print-toolbar button,
        .print-toolbar a {
            padding: 10px 18px;
            border: 0;
            border-radius: 7px;
            color: #ffffff;
            background: #0f6b3f;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .print-toolbar a {
            background: #667085;
        }

        .print-document {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px;
            padding: 15mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
        }

        .school-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 15px;
            border-bottom: 3px solid #0f6b3f;
        }

        .school-logo {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }

        .school-heading {
            flex: 1;
            padding: 0 18px;
            text-align: center;
        }

        .school-heading h1 {
            margin: 0;
            color: #0f5d38;
            font-size: 25px;
            text-transform: uppercase;
        }

        .school-heading h2 {
            margin: 6px 0;
            font-size: 17px;
        }

        .school-heading p {
            margin: 3px 0;
            font-size: 12px;
        }

        .pupil-photo,
        .photo-placeholder {
            width: 90px;
            height: 105px;
            border: 2px solid #0f6b3f;
            border-radius: 5px;
        }

        .pupil-photo {
            object-fit: cover;
        }

        .photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f6b3f;
            font-size: 35px;
        }

        .profile-title {
            margin: 20px 0 15px;
            padding: 10px;
            color: #ffffff;
            background: #0f6b3f;
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
        }

        .summary-table,
        .information-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table {
            margin-bottom: 18px;
        }

        .summary-table th,
        .summary-table td,
        .information-table th,
        .information-table td {
            padding: 8px 10px;
            border: 1px solid #cfd6de;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }

        .summary-table th,
        .information-table th {
            width: 32%;
            background: #f1f6f3;
            font-weight: bold;
        }

        .section-title {
            margin: 17px 0 0;
            padding: 8px 10px;
            color: #ffffff;
            background: #173f68;
            font-size: 13px;
            text-transform: uppercase;
        }

        .signature-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 50px;
            margin-top: 55px;
        }

        .signature-box {
            padding-top: 8px;
            border-top: 1px solid #333333;
            font-size: 12px;
            text-align: center;
        }

        .document-footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #cfd6de;
            color: #667085;
            font-size: 10px;
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .print-toolbar {
                display: none;
            }

            .print-document {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
            }

            @page {
                size: A4;
                margin: 8mm;
            }
        }
    </style>
</head>

<body>

<div class="print-toolbar">

    <a href="view.php?id=<?= (int) $pupil['id'] ?>">
        Back to Profile
    </a>

    <button onclick="window.print()">
        Print or Save as PDF
    </button>

</div>

<main class="print-document">

    <header class="school-header">

        <img
            src="../assets/images/logo.png"
            alt="School logo"
            class="school-logo"
        >

        <div class="school-heading">
            <h1>Good Shepherd Primary School</h1>

            <h2>Pupil Registration Profile</h2>

            <p>
                Excellence • Discipline • Integrity
            </p>

            <p>
                School Management System
            </p>
        </div>

        <?php if ($photoUrl !== null): ?>
            <img
                src="<?= htmlspecialchars($photoUrl) ?>"
                alt="Pupil photograph"
                class="pupil-photo"
            >
        <?php else: ?>
            <div class="photo-placeholder">👤</div>
        <?php endif; ?>

    </header>

    <div class="profile-title">
        Official Pupil Profile
    </div>

    <table class="summary-table">
        <tr>
            <th>Full Name</th>
            <td><?= htmlspecialchars($fullName) ?></td>

            <th>Admission Number</th>
            <td>
                <?= printValue($pupil['admission_number'] ?? null) ?>
            </td>
        </tr>

        <tr>
            <th>Class</th>
            <td><?= printValue($className) ?></td>

            <th>Stream</th>
            <td><?= printValue($streamName) ?></td>
        </tr>

        <tr>
            <th>Gender</th>
            <td><?= printValue($pupil['gender'] ?? null) ?></td>

            <th>Status</th>
            <td><?= printValue($pupil['pupil_status'] ?? null) ?></td>
        </tr>
    </table>

    <div class="section-title">Personal Information</div>

    <table class="information-table">
        <tr>
            <th>EMIS Number</th>
            <td><?= printValue($pupil['emis_number'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Learner Identification Number</th>
            <td><?= printValue($pupil['lin_number'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Date of Birth</th>
            <td><?= printDate($pupil['date_of_birth'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Nationality</th>
            <td><?= printValue($pupil['nationality'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Religion</th>
            <td><?= printValue($pupil['religion'] ?? null) ?></td>
        </tr>
    </table>

    <div class="section-title">Academic Information</div>

    <table class="information-table">
        <tr>
            <th>Class</th>
            <td><?= printValue($className) ?></td>
        </tr>

        <tr>
            <th>Stream</th>
            <td><?= printValue($streamName) ?></td>
        </tr>

        <tr>
            <th>Admission Date</th>
            <td><?= printDate($pupil['admission_date'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Admission Type</th>
            <td><?= printValue($pupil['admission_type'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Former School</th>
            <td><?= printValue($pupil['former_school'] ?? null) ?></td>
        </tr>
    </table>

    <div class="section-title">Home Location</div>

    <table class="information-table">
        <tr>
            <th>District</th>
            <td><?= printValue($pupil['district'] ?? null) ?></td>
        </tr>

        <tr>
            <th>County</th>
            <td><?= printValue($pupil['county'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Sub-county</th>
            <td><?= printValue($pupil['sub_county'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Parish</th>
            <td><?= printValue($pupil['parish'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Village</th>
            <td><?= printValue($pupil['village'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Home Address</th>
            <td><?= printValue($pupil['home_address'] ?? null) ?></td>
        </tr>
    </table>

    <div class="section-title">Parent and Guardian Information</div>

    <table class="information-table">
        <tr>
            <th>Father's Name</th>
            <td><?= printValue($pupil['father_name'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Father's Phone</th>
            <td><?= printValue($pupil['father_phone'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Mother's Name</th>
            <td><?= printValue($pupil['mother_name'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Mother's Phone</th>
            <td><?= printValue($pupil['mother_phone'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Guardian's Name</th>
            <td><?= printValue($pupil['guardian_name'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Guardian's Phone</th>
            <td><?= printValue($pupil['guardian_phone'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Guardian Relationship</th>
            <td>
                <?= printValue(
                    $pupil['guardian_relationship'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Orphan Status</th>
            <td><?= printValue($pupil['orphan_status'] ?? null) ?></td>
        </tr>
    </table>

    <div class="section-title">Medical and Emergency Information</div>

    <table class="information-table">
        <tr>
            <th>Blood Group</th>
            <td><?= printValue($pupil['blood_group'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Medical Condition</th>
            <td>
                <?= printValue(
                    $pupil['medical_condition'] ?? null,
                    'None recorded'
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Allergies</th>
            <td>
                <?= printValue(
                    $pupil['allergies'] ?? null,
                    'None recorded'
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Special Needs</th>
            <td>
                <?= printValue(
                    $pupil['special_needs'] ?? null,
                    'None recorded'
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Emergency Contact</th>
            <td>
                <?= printValue(
                    $pupil['emergency_contact_name'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Emergency Phone</th>
            <td>
                <?= printValue(
                    $pupil['emergency_contact_phone'] ?? null
                ) ?>
            </td>
        </tr>
    </table>

    <section class="signature-section">

        <div class="signature-box">
            Parent/Guardian Signature
        </div>

        <div class="signature-box">
            Head Teacher's Signature and Stamp
        </div>

    </section>

    <footer class="document-footer">
        Generated on <?= date('d M Y, h:i A') ?> through the
        Good Shepherd Primary School Management System.
    </footer>

</main>

</body>
</html>