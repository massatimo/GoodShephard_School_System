<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$academicYearId = filter_input(
    INPUT_GET,
    'academic_year_id',
    FILTER_VALIDATE_INT
);

$termId = filter_input(
    INPUT_GET,
    'term_id',
    FILTER_VALIDATE_INT
);

$classId = filter_input(
    INPUT_GET,
    'class_id',
    FILTER_VALIDATE_INT
);

$streamId = filter_input(
    INPUT_GET,
    'stream_id',
    FILTER_VALIDATE_INT
);

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

if (
    !$academicYearId ||
    !$termId ||
    !$classId ||
    $dateFrom === '' ||
    $dateTo === ''
) {
    exit('Incomplete report filters.');
}

$headingStatement = $pdo->prepare(
    'SELECT
        academic_years.year_name,
        terms.term_name,
        classes.class_name
     FROM academic_years
     INNER JOIN terms
        ON terms.academic_year_id =
            academic_years.id
     CROSS JOIN classes
     WHERE academic_years.id =
        :academic_year_id
       AND terms.id = :term_id
       AND classes.id = :class_id
     LIMIT 1'
);

$headingStatement->execute([
    'academic_year_id' => $academicYearId,
    'term_id' => $termId,
    'class_id' => $classId,
]);

$heading = $headingStatement->fetch();

if (!$heading) {
    exit('Invalid report information.');
}

$streamName = 'All Streams';

if ($streamId) {
    $streamStatement = $pdo->prepare(
        'SELECT stream_name
         FROM streams
         WHERE id = :id
         LIMIT 1'
    );

    $streamStatement->execute([
        'id' => $streamId,
    ]);

    $streamName =
        $streamStatement->fetchColumn()
        ?: 'Unknown Stream';
}

$sql = '
    SELECT
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status =
                    "Present"
                THEN 1
                ELSE 0
            END
        ) AS total_present,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status =
                    "Absent"
                THEN 1
                ELSE 0
            END
        ) AS total_absent,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status =
                    "Late"
                THEN 1
                ELSE 0
            END
        ) AS total_late,

        SUM(
            CASE
                WHEN pupil_attendance.attendance_status =
                    "Excused"
                THEN 1
                ELSE 0
            END
        ) AS total_excused

    FROM pupils

    LEFT JOIN pupil_attendance
        ON pupil_attendance.pupil_id = pupils.id

    LEFT JOIN attendance_registers
        ON attendance_registers.id =
            pupil_attendance.attendance_register_id
       AND attendance_registers.academic_year_id =
            :academic_year_id
       AND attendance_registers.term_id = :term_id
       AND attendance_registers.class_id = :class_id
       AND attendance_registers.attendance_date
            BETWEEN :date_from AND :date_to
';

$parameters = [
    'academic_year_id' => $academicYearId,
    'term_id' => $termId,
    'class_id' => $classId,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
];

if ($streamId) {
    $sql .= '
        AND attendance_registers.stream_id =
            :register_stream_id
    ';

    $parameters['register_stream_id'] =
        $streamId;
}

$sql .= '
    WHERE pupils.class_id = :pupil_class_id
      AND pupils.pupil_status = "Active"
';

$parameters['pupil_class_id'] = $classId;

if ($streamId) {
    $sql .= '
        AND pupils.stream_id = :pupil_stream_id
    ';

    $parameters['pupil_stream_id'] =
        $streamId;
}

$sql .= '
    GROUP BY pupils.id
    ORDER BY
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name
';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);

$rows = $statement->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Attendance Summary Report</title>

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

        .toolbar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 18px;
        }

        .toolbar a,
        .toolbar button {
            padding: 10px 18px;
            border: 0;
            border-radius: 7px;
            color: #ffffff;
            background: #0f6b3f;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar a {
            background: #667085;
        }

        .document {
            width: 297mm;
            min-height: 210mm;
            margin: auto;
            padding: 12mm;
            background: #ffffff;
        }

        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 3px solid #0f6b3f;
            padding-bottom: 12px;
        }

        .school-logo {
            width: 75px;
            height: 75px;
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
        }

        .report-details {
            width: 100%;
            margin: 16px 0;
            border-collapse: collapse;
        }

        .report-details th,
        .report-details td {
            padding: 7px 9px;
            border: 1px solid #cfd6de;
            font-size: 11px;
        }

        .report-details th {
            background: #f1f6f3;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            padding: 7px;
            border: 1px solid #cfd6de;
            font-size: 10px;
            text-align: left;
        }

        .report-table th {
            color: #ffffff;
            background: #173f68;
        }

        .rate-good {
            color: #117147;
            font-weight: bold;
        }

        .rate-warning {
            color: #a46a00;
            font-weight: bold;
        }

        .rate-poor {
            color: #b34343;
            font-weight: bold;
        }

        .signature-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 80px;
            margin-top: 50px;
        }

        .signature-box {
            padding-top: 8px;
            border-top: 1px solid #333333;
            font-size: 11px;
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .document {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 7mm;
            }

            @page {
                size: A4 landscape;
                margin: 7mm;
            }
        }
    </style>
</head>

<body>

<div class="toolbar">

    <a href="javascript:history.back()">
        Back
    </a>

    <button onclick="window.print()">
        Print or Save as PDF
    </button>

</div>

<main class="document">

    <header class="school-header">

        <img
            src="../assets/images/logo.png"
            alt="School logo"
            class="school-logo"
        >

        <div class="school-heading">

            <h1>Good Shepherd Primary School</h1>

            <h2>Class Attendance Summary Report</h2>

            <p>
                Excellence • Discipline • Integrity
            </p>

        </div>

    </header>

    <table class="report-details">

        <tr>
            <th>Academic Year</th>
            <td>
                <?= htmlspecialchars($heading['year_name']) ?>
            </td>

            <th>Term</th>
            <td>
                <?= htmlspecialchars($heading['term_name']) ?>
            </td>

            <th>Class</th>
            <td>
                <?= htmlspecialchars($heading['class_name']) ?>
            </td>
        </tr>

        <tr>
            <th>Stream</th>
            <td><?= htmlspecialchars($streamName) ?></td>

            <th>Date From</th>
            <td>
                <?= htmlspecialchars(
                    date('d M Y', strtotime($dateFrom))
                ) ?>
            </td>

            <th>Date To</th>
            <td>
                <?= htmlspecialchars(
                    date('d M Y', strtotime($dateTo))
                ) ?>
            </td>
        </tr>

    </table>

    <table class="report-table">

        <thead>
            <tr>
                <th>#</th>
                <th>Admission Number</th>
                <th>Pupil Name</th>
                <th>Gender</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Excused</th>
                <th>Total Recorded</th>
                <th>Attendance Rate</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($rows as $index => $row): ?>

            <?php
            $fullName = trim(
                $row['first_name'] . ' ' .
                ($row['middle_name'] ?? '') . ' ' .
                $row['last_name']
            );

            $present = (int) $row['total_present'];
            $absent = (int) $row['total_absent'];
            $late = (int) $row['total_late'];
            $excused = (int) $row['total_excused'];

            $total =
                $present + $absent + $late + $excused;

            $rate =
                $total > 0
                    ? (($present + $late) / $total) * 100
                    : 0;

            $rateClass =
                $rate >= 75
                    ? 'rate-good'
                    : (
                        $rate >= 60
                            ? 'rate-warning'
                            : 'rate-poor'
                    );
            ?>

            <tr>

                <td><?= $index + 1 ?></td>

                <td>
                    <?= htmlspecialchars(
                        $row['admission_number']
                    ) ?>
                </td>

                <td><?= htmlspecialchars($fullName) ?></td>

                <td>
                    <?= htmlspecialchars($row['gender']) ?>
                </td>

                <td><?= $present ?></td>

                <td><?= $absent ?></td>

                <td><?= $late ?></td>

                <td><?= $excused ?></td>

                <td><?= $total ?></td>

                <td class="<?= $rateClass ?>">
                    <?= number_format($rate, 1) ?>%
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <section class="signature-section">

        <div class="signature-box">
            Class Teacher's Signature
        </div>

        <div class="signature-box">
            Head Teacher's Signature and Stamp
        </div>

    </section>

</main>

</body>
</html>