<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$staffId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$staffId || $staffId < 1) {
    exit('Invalid staff member selected.');
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
    exit('Staff member not found.');
}

function printStaffValue(
    mixed $value,
    string $fallback = 'Not provided'
): string {
    if ($value === null || trim((string) $value) === '') {
        return $fallback;
    }

    return htmlspecialchars((string) $value);
}

function printStaffDate(mixed $date): string
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
    ($staff['first_name'] ?? '') . ' ' .
    ($staff['middle_name'] ?? '') . ' ' .
    ($staff['last_name'] ?? '')
);

if ($fullName === '') {
    $fullName = (string) ($staff['full_name'] ?? 'Unknown Staff');
}

$designation =
    $staff['designation']
    ?? $staff['position']
    ?? 'Not assigned';

$photoUrl = null;

if (!empty($staff['photo'])) {
    $relativePath = ltrim((string) $staff['photo'], '/');

    if (is_file(__DIR__ . '/../' . $relativePath)) {
        $photoUrl = '../' . $relativePath;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Staff Profile - <?= htmlspecialchars($fullName) ?>
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
            font-size: 24px;
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

        .staff-photo,
        .photo-placeholder {
            width: 90px;
            height: 105px;
            border: 2px solid #0f6b3f;
            border-radius: 5px;
        }

        .staff-photo {
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

    <a href="view.php?id=<?= (int) $staff['id'] ?>">
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
            <h2>Official Staff Profile</h2>
            <p>Excellence • Discipline • Integrity</p>
            <p>School Management System</p>
        </div>

        <?php if ($photoUrl !== null): ?>

            <img
                src="<?= htmlspecialchars($photoUrl) ?>"
                alt="Staff photograph"
                class="staff-photo"
            >

        <?php else: ?>

            <div class="photo-placeholder">👤</div>

        <?php endif; ?>

    </header>

    <div class="profile-title">
        Staff Employment Record
    </div>

    <table class="summary-table">

        <tr>
            <th>Full Name</th>
            <td><?= htmlspecialchars($fullName) ?></td>

            <th>Staff Number</th>
            <td>
                <?= printStaffValue($staff['staff_number'] ?? null) ?>
            </td>
        </tr>

        <tr>
            <th>Designation</th>
            <td><?= printStaffValue($designation) ?></td>

            <th>Department</th>
            <td>
                <?= printStaffValue($staff['department'] ?? null) ?>
            </td>
        </tr>

        <tr>
            <th>Staff Category</th>
            <td>
                <?= printStaffValue(
                    $staff['staff_category'] ?? null
                ) ?>
            </td>

            <th>Status</th>
            <td>
                <?= printStaffValue(
                    $staff['employment_status'] ?? null
                ) ?>
            </td>
        </tr>

    </table>

    <div class="section-title">Personal Information</div>

    <table class="information-table">

        <tr>
            <th>Gender</th>
            <td><?= printStaffValue($staff['gender'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Date of Birth</th>
            <td>
                <?= printStaffDate(
                    $staff['date_of_birth'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Nationality</th>
            <td>
                <?= printStaffValue(
                    $staff['nationality'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Religion</th>
            <td><?= printStaffValue($staff['religion'] ?? null) ?></td>
        </tr>

        <tr>
            <th>National ID/NIN</th>
            <td>
                <?= printStaffValue(
                    $staff['national_id_number'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Marital Status</th>
            <td>
                <?= printStaffValue(
                    $staff['marital_status'] ?? null
                ) ?>
            </td>
        </tr>

    </table>

    <div class="section-title">Contact and Location</div>

    <table class="information-table">

        <tr>
            <th>Primary Phone</th>
            <td><?= printStaffValue($staff['phone'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Alternative Phone</th>
            <td>
                <?= printStaffValue(
                    $staff['alternative_phone'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Email Address</th>
            <td><?= printStaffValue($staff['email'] ?? null) ?></td>
        </tr>

        <tr>
            <th>District</th>
            <td><?= printStaffValue($staff['district'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Sub-county</th>
            <td>
                <?= printStaffValue($staff['sub_county'] ?? null) ?>
            </td>
        </tr>

        <tr>
            <th>Village</th>
            <td><?= printStaffValue($staff['village'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Home Address</th>
            <td>
                <?= printStaffValue(
                    $staff['home_address'] ?? null
                ) ?>
            </td>
        </tr>

    </table>

    <div class="section-title">Employment Information</div>

    <table class="information-table">

        <tr>
            <th>Employment Type</th>
            <td>
                <?= printStaffValue(
                    $staff['employment_type'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Appointment Date</th>
            <td>
                <?= printStaffDate(
                    $staff['appointment_date'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Highest Qualification</th>
            <td>
                <?= printStaffValue(
                    $staff['qualification'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Specialization</th>
            <td>
                <?= printStaffValue(
                    $staff['specialization'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Teaching Registration Number</th>
            <td>
                <?= printStaffValue(
                    $staff[
                        'teaching_registration_number'
                    ] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>TIN Number</th>
            <td><?= printStaffValue($staff['tin_number'] ?? null) ?></td>
        </tr>

        <tr>
            <th>NSSF Number</th>
            <td><?= printStaffValue($staff['nssf_number'] ?? null) ?></td>
        </tr>

    </table>

    <div class="section-title">Bank Information</div>

    <table class="information-table">

        <tr>
            <th>Bank Name</th>
            <td><?= printStaffValue($staff['bank_name'] ?? null) ?></td>
        </tr>

        <tr>
            <th>Account Name</th>
            <td>
                <?= printStaffValue(
                    $staff['bank_account_name'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Account Number</th>
            <td>
                <?= printStaffValue(
                    $staff['bank_account_number'] ?? null
                ) ?>
            </td>
        </tr>

    </table>

    <div class="section-title">Next of Kin and Emergency Contact</div>

    <table class="information-table">

        <tr>
            <th>Next of Kin</th>
            <td>
                <?= printStaffValue(
                    $staff['next_of_kin_name'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Next of Kin Phone</th>
            <td>
                <?= printStaffValue(
                    $staff['next_of_kin_phone'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Relationship</th>
            <td>
                <?= printStaffValue(
                    $staff[
                        'next_of_kin_relationship'
                    ] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Emergency Contact</th>
            <td>
                <?= printStaffValue(
                    $staff['emergency_contact_name'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Emergency Phone</th>
            <td>
                <?= printStaffValue(
                    $staff['emergency_contact_phone'] ?? null
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Medical Information</th>
            <td>
                <?= printStaffValue(
                    $staff['medical_information'] ?? null,
                    'None recorded'
                ) ?>
            </td>
        </tr>

    </table>

    <section class="signature-section">

        <div class="signature-box">
            Staff Member's Signature
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