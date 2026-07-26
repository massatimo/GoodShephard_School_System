<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$registerId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$registerId || $registerId < 1) {
    exit('Invalid attendance register selected.');
}

$registerStatement = $pdo->prepare(
    'SELECT
        attendance_registers.*,
        academic_years.year_name,
        terms.term_name,
        classes.class_name,
        streams.stream_name,
        users.full_name AS recorded_by_name
     FROM attendance_registers
     INNER JOIN academic_years
        ON academic_years.id =
            attendance_registers.academic_year_id
     INNER JOIN terms
        ON terms.id =
            attendance_registers.term_id
     INNER JOIN classes
        ON classes.id =
            attendance_registers.class_id
     LEFT JOIN streams
        ON streams.id =
            attendance_registers.stream_id
     INNER JOIN users
        ON users.id =
            attendance_registers.recorded_by
     WHERE attendance_registers.id = :id
     LIMIT 1'
);

$registerStatement->execute([
    'id' => $registerId,
]);

$register = $registerStatement->fetch();

if (!$register) {
    exit('Attendance register not found.');
}

$pupilStatement = $pdo->prepare(
    'SELECT
        pupil_attendance.attendance_status,
        pupil_attendance.remarks,

        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender

     FROM pupil_attendance

     INNER JOIN pupils
        ON pupils.id = pupil_attendance.pupil_id

     WHERE pupil_attendance.attendance_register_id =
        :register_id

     ORDER BY
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name'
);

$pupilStatement->execute([
    'register_id' => $registerId,
]);

$records = $pupilStatement->fetchAll();

$summary = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'Excused' => 0,
];

foreach ($records as $record) {
    $status = $record['attendance_status'];

    if (isset($summary[$status])) {
        $summary[$status]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Attendance Register
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

        .print-toolbar a,
        .print-toolbar button {
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
            padding: 13mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
        }

        .school-header {
            display: flex;
            align-items: center;
            padding-bottom: 14px;
            border-bottom: 3px solid #0f6b3f;
        }

        .school-logo {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .school-heading {
            flex: 1;
            text-align: center;
        }

        .school-heading h1 {
            margin: 0;
            color: #0f5d38;
            font-size: 23px;
            text-transform: uppercase;
        }

        .school-heading h2 {
            margin: 6px 0;
            font-size: 16px;
            text-transform: uppercase;
        }

        .school-heading p {
            margin: 3px 0;
            font-size: 11px;
        }

        .register-info,
        .attendance-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .register-info {
            margin: 18px 0;
        }

        .register-info th,
        .register-info td,
        .summary-table th,
        .summary-table td,
        .attendance-table th,
        .attendance-table td {
            padding: 7px 8px;
            border: 1px solid #cfd6de;
            font-size: 11px;
            text-align: left;
        }

        .register-info th,
        .summary-table th {
            background: #f1f6f3;
        }

        .summary-table {
            margin-bottom: 18px;
        }

        .attendance-table th {
            color: #ffffff;
            background: #173f68;
        }

        .attendance-table td:first-child,
        .attendance-table th:first-child {
            width: 35px;
            text-align: center;
        }

        .status-present {
            color: #117147;
            font-weight: bold;
        }

        .status-absent {
            color: #b34343;
            font-weight: bold;
        }

        .status-late {
            color: #a46a00;
            font-weight: bold;
        }

        .status-excused {
            color: #315f9a;
            font-weight: bold;
        }

        .signature-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 60px;
            margin-top: 55px;
        }

        .signature-box {
            padding-top: 8px;
            border-top: 1px solid #333333;
            font-size: 11px;
            text-align: center;
        }

        .document-footer {
            margin-top: 24px;
            padding-top: 9px;
            border-top: 1px solid #d7dce2;
            color: #667085;
            font-size: 9px;
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
                padding: 8mm;
                box-shadow: none;
            }

            @page {
                size: A4 portrait;
                margin: 7mm;
            }
        }
    </style>
</head>

<body>

<div class="print-toolbar">

    <a href="view.php?id=<?= (int) $register['id'] ?>">
        Back to Register
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

            <h2>Pupil Attendance Register</h2>

            <p>
                Excellence • Discipline • Integrity
            </p>

        </div>

    </header>

    <table class="register-info">

        <tr>
            <th>Academic Year</th>
            <td>
                <?= htmlspecialchars($register['year_name']) ?>
            </td>

            <th>Term</th>
            <td>
                <?= htmlspecialchars($register['term_name']) ?>
            </td>
        </tr>

        <tr>
            <th>Class</th>
            <td>
                <?= htmlspecialchars($register['class_name']) ?>
            </td>

            <th>Stream</th>
            <td>
                <?= htmlspecialchars(
                    $register['stream_name']
                    ?? 'All streams'
                ) ?>
            </td>
        </tr>

        <tr>
            <th>Date</th>
            <td>
                <?= htmlspecialchars(
                    date(
                        'd M Y',
                        strtotime($register['attendance_date'])
                    )
                ) ?>
            </td>

            <th>Session</th>
            <td>
                <?= htmlspecialchars($register['session']) ?>
            </td>
        </tr>

        <tr>
            <th>Recorded By</th>
            <td>
                <?= htmlspecialchars(
                    $register['recorded_by_name']
                ) ?>
            </td>

            <th>Total Pupils</th>
            <td><?= count($records) ?></td>
        </tr>

    </table>

    <table class="summary-table">

        <tr>
            <th>Present</th>
            <td><?= $summary['Present'] ?></td>

            <th>Absent</th>
            <td><?= $summary['Absent'] ?></td>

            <th>Late</th>
            <td><?= $summary['Late'] ?></td>

            <th>Excused</th>
            <td><?= $summary['Excused'] ?></td>
        </tr>

    </table>

    <table class="attendance-table">

        <thead>
            <tr>
                <th>#</th>
                <th>Admission Number</th>
                <th>Pupil Name</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($records as $index => $record): ?>

            <?php
            $fullName = trim(
                $record['first_name'] . ' ' .
                ($record['middle_name'] ?? '') . ' ' .
                $record['last_name']
            );

            $statusClass =
                'status-' .
                strtolower($record['attendance_status']);
            ?>

            <tr>

                <td><?= $index + 1 ?></td>

                <td>
                    <?= htmlspecialchars(
                        $record['admission_number']
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars($fullName) ?>
                </td>

                <td>
                    <?= htmlspecialchars($record['gender']) ?>
                </td>

                <td class="<?= htmlspecialchars($statusClass) ?>">
                    <?= htmlspecialchars(
                        $record['attendance_status']
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $record['remarks'] ?: '—'
                    ) ?>
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <?php if (!empty($register['notes'])): ?>

        <p style="font-size: 11px; margin-top: 15px;">
            <strong>Register Notes:</strong>

            <?= htmlspecialchars($register['notes']) ?>
        </p>

    <?php endif; ?>

    <section class="signature-section">

        <div class="signature-box">
            Class Teacher's Signature
        </div>

        <div class="signature-box">
            Head Teacher's Signature
        </div>

    </section>

    <footer class="document-footer">
        Generated on <?= date('d M Y, h:i A') ?> through the
        Good Shepherd Primary School Management System.
    </footer>

</main>

</body>
</html>