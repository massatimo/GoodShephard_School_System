<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$examinationId = filter_input(
    INPUT_GET,
    'examination_id',
    FILTER_VALIDATE_INT
);

$examinationClassId = filter_input(
    INPUT_GET,
    'examination_class_id',
    FILTER_VALIDATE_INT
);

$examinationSubjectId = filter_input(
    INPUT_GET,
    'examination_subject_id',
    FILTER_VALIDATE_INT
);

if (
    !$examinationId ||
    !$examinationClassId ||
    !$examinationSubjectId
) {
    exit('Invalid subject-analysis request.');
}

$schoolInformation = require __DIR__ . '/../config/school.php';
$schoolName = $schoolInformation['name'];

$schoolAddress = $schoolInformation['address'] . ' | Tel: ' . $schoolInformation['telephone'];

$schoolLogo =
    '../assets/images/royal-logo.jpg';

/*
|--------------------------------------------------------------------------
| Retrieve examination subject information
|--------------------------------------------------------------------------
*/

$informationStatement = $pdo->prepare(
    'SELECT
        examinations.examination_name,

        examination_types.type_name,

        academic_years.year_name,
        terms.term_name,

        examination_classes.class_id,
        examination_classes.stream_id,

        classes.class_name,
        classes.class_code,

        streams.stream_name,

        examination_subjects.maximum_mark,
        examination_subjects.pass_mark,

        subjects.subject_name,
        subjects.subject_code

     FROM examination_subjects

     INNER JOIN examination_classes
        ON examination_classes.id =
            examination_subjects.examination_class_id

     INNER JOIN examinations
        ON examinations.id =
            examination_classes.examination_id

     INNER JOIN examination_types
        ON examination_types.id =
            examinations.examination_type_id

     INNER JOIN academic_years
        ON academic_years.id =
            examinations.academic_year_id

     INNER JOIN terms
        ON terms.id =
            examinations.term_id

     INNER JOIN classes
        ON classes.id =
            examination_classes.class_id

     LEFT JOIN streams
        ON streams.id =
            examination_classes.stream_id

     INNER JOIN subjects
        ON subjects.id =
            examination_subjects.subject_id

     WHERE examinations.id =
        :examination_id

       AND examination_classes.id =
        :examination_class_id

       AND examination_subjects.id =
        :examination_subject_id

     LIMIT 1'
);

$informationStatement->execute([
    'examination_id' =>
        $examinationId,
    'examination_class_id' =>
        $examinationClassId,
    'examination_subject_id' =>
        $examinationSubjectId,
]);

$information =
    $informationStatement->fetch();

if (!$information) {
    exit(
        'The selected subject analysis was not found.'
    );
}

/*
|--------------------------------------------------------------------------
| Retrieve pupils and marks
|--------------------------------------------------------------------------
*/

$resultSql = '
    SELECT
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender,

        pupil_marks.mark_obtained,
        pupil_marks.grade,
        pupil_marks.points,
        pupil_marks.teacher_remark,
        pupil_marks.is_absent

    FROM pupils

    LEFT JOIN pupil_marks
        ON pupil_marks.pupil_id =
            pupils.id

       AND pupil_marks.examination_subject_id =
            :examination_subject_id

    WHERE pupils.class_id = :class_id

      AND pupils.pupil_status = "Active"
';

$resultParameters = [
    'examination_subject_id' =>
        $examinationSubjectId,
    'class_id' =>
        $information['class_id'],
];

if (!empty($information['stream_id'])) {
    $resultSql .= '
        AND pupils.stream_id = :stream_id
    ';

    $resultParameters['stream_id'] =
        $information['stream_id'];
}

$resultSql .= '
    ORDER BY
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name
';

$resultStatement =
    $pdo->prepare($resultSql);

$resultStatement->execute(
    $resultParameters
);

$results =
    $resultStatement->fetchAll();

$totalPupils = count($results);
$totalMarked = 0;
$totalPassed = 0;
$totalFailed = 0;
$totalAbsent = 0;
$marks = [];
$gradeDistribution = [];

foreach ($results as &$result) {
    $result['is_absent'] =
        (int) ($result['is_absent'] ?? 0) === 1;

    $result['position'] = null;

    if ($result['is_absent']) {
        $totalAbsent++;
        continue;
    }

    if ($result['mark_obtained'] === null) {
        continue;
    }

    $mark =
        (float) $result['mark_obtained'];

    $result['mark_obtained'] = $mark;

    $marks[] = $mark;
    $totalMarked++;

    if (
        $mark >=
        (float) $information['pass_mark']
    ) {
        $totalPassed++;
    } else {
        $totalFailed++;
    }

    $grade = trim(
        (string) ($result['grade'] ?? '')
    );

    if ($grade === '') {
        $grade = 'Ungraded';
    }

    $gradeDistribution[$grade] =
        ($gradeDistribution[$grade] ?? 0) + 1;
}

unset($result);

usort(
    $results,
    static function (
        array $first,
        array $second
    ): int {
        $firstMark =
            $first['mark_obtained'];

        $secondMark =
            $second['mark_obtained'];

        if (
            $firstMark === null &&
            $secondMark !== null
        ) {
            return 1;
        }

        if (
            $firstMark !== null &&
            $secondMark === null
        ) {
            return -1;
        }

        if (
            $firstMark === null &&
            $secondMark === null
        ) {
            return strcmp(
                (string) $first['first_name'],
                (string) $second['first_name']
            );
        }

        return
            (float) $secondMark
            <=>
            (float) $firstMark;
    }
);

$positionCounter = 0;
$currentPosition = 0;
$previousMark = null;

foreach ($results as &$result) {
    if (
        $result['mark_obtained'] === null ||
        $result['is_absent']
    ) {
        continue;
    }

    $positionCounter++;

    $mark =
        (float) $result['mark_obtained'];

    if (
        $previousMark === null ||
        round($mark, 4) !==
        round($previousMark, 4)
    ) {
        $currentPosition =
            $positionCounter;
    }

    $result['position'] =
        $currentPosition;

    $previousMark = $mark;
}

unset($result);

$highestMark =
    $marks !== []
        ? max($marks)
        : 0;

$lowestMark =
    $marks !== []
        ? min($marks)
        : 0;

$averageMark =
    $marks !== []
        ? array_sum($marks) /
            count($marks)
        : 0;

$passRate =
    $totalMarked > 0
        ? ($totalPassed / $totalMarked) * 100
        : 0;

$classDisplay =
    $information['class_name'];

if (!empty($information['stream_name'])) {
    $classDisplay .=
        ' – ' .
        $information['stream_name'];
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
        Subject Analysis -
        <?= htmlspecialchars(
            $information['subject_name']
        ) ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            color: #1f2b35;
            background: #e9eef1;
            font-family: Arial, sans-serif;
        }

        .print-toolbar {
            width: 210mm;
            display: flex;
            justify-content: space-between;
            margin: 0 auto 16px;
        }

        .print-toolbar a,
        .print-toolbar button {
            padding: 10px 15px;
            border: 0;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }

        .print-toolbar a {
            color: #25313a;
            background: #ffffff;
        }

        .print-toolbar button {
            color: #ffffff;
            background: #2d1772;
        }

        .analysis-page {
            width: 210mm;
            min-height: 297mm;
            margin: auto;
            padding: 13mm;
            background: #ffffff;
            box-shadow:
                0 12px 35px
                rgba(0, 0, 0, 0.12);
        }

        .school-header {
            display: grid;
            grid-template-columns:
                85px 1fr 85px;
            align-items: center;
            gap: 15px;
            padding-bottom: 12px;
            border-bottom: 3px solid #2d1772;
        }

        .school-header img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .school-header div {
            text-align: center;
        }

        .school-header h1 {
            margin: 0;
            color: #2d1772;
            font-family: Georgia, serif;
            font-size: 25px;
            text-transform: uppercase;
        }

        .school-header p {
            margin: 5px 0 0;
            color: #66727e;
            font-size: 11px;
        }

        .report-title {
            margin: 13px 0;
            padding: 11px;
            color: #ffffff;
            text-align: center;
            background:
                linear-gradient(
                    135deg,
                    #211052,
                    #2d1772
                );
        }

        .report-title h2 {
            margin: 0;
            font-size: 17px;
            text-transform: uppercase;
        }

        .report-title p {
            margin: 4px 0 0;
            font-size: 10px;
        }

        .information-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 8px;
        }

        .information-grid div,
        .summary-grid div {
            padding: 9px;
            border: 1px solid #cfd7dc;
            border-radius: 5px;
        }

        small {
            display: block;
            margin-bottom: 4px;
            color: #66727e;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        strong {
            font-size: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);
            gap: 8px;
            margin-top: 12px;
        }

        .summary-grid div {
            text-align: center;
            background: #edf7fc;
        }

        .summary-grid strong {
            color: #2d1772;
            font-size: 14px;
        }

        .section-heading {
            margin-top: 13px;
            padding: 7px 10px;
            border-left: 4px solid #2d1772;
            color: #211052;
            background: #edf7fc;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #cfd7dc;
            text-align: center;
        }

        th {
            color: #ffffff;
            background: #211052;
            font-size: 8px;
            text-transform: uppercase;
        }

        td:nth-child(2),
        td:nth-child(3) {
            text-align: left;
        }

        tbody tr:nth-child(even) {
            background: #f8faf9;
        }

        .grade-table {
            width: 60%;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            padding-top: 7px;
            border-top: 1px solid #cfd7dc;
            color: #66727e;
            font-size: 7px;
        }

        @page {
            size: A4 portrait;
            margin: 7mm;
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .print-toolbar {
                display: none;
            }

            .analysis-page {
                width: 100%;
                min-height: auto;
                padding: 5mm;
                box-shadow: none;
            }

            table,
            .summary-grid,
            .grade-table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="print-toolbar">

    <a
        href="subject-analysis.php?<?= htmlspecialchars(
            http_build_query([
                'examination_id' =>
                    $examinationId,
                'examination_class_id' =>
                    $examinationClassId,
                'examination_subject_id' =>
                    $examinationSubjectId,
            ])
        ) ?>"
    >
        Back
    </a>

    <button onclick="window.print()">
        Print Subject Analysis
    </button>

</div>

<main class="analysis-page">

    <header class="school-header">

        <img
            src="<?= htmlspecialchars(
                $schoolLogo
            ) ?>"
            alt="School logo"
        >

        <div>
            <h1>
                <?= htmlspecialchars(
                    $schoolName
                ) ?>
            </h1>

            <p>
                <?= htmlspecialchars(
                    $schoolAddress
                ) ?>
            </p>

            <p>
                Subject Performance Analysis
            </p>
        </div>

        <div></div>

    </header>

    <section class="report-title">

        <h2>
            <?= htmlspecialchars(
                $information['subject_name']
            ) ?>
            Analysis Report
        </h2>

        <p>
            <?= htmlspecialchars(
                $information['examination_name']
            ) ?>
            ·
            <?= htmlspecialchars(
                $information['term_name']
            ) ?>
            ·
            <?= htmlspecialchars(
                $information['year_name']
            ) ?>
        </p>

    </section>

    <section class="information-grid">

        <div>
            <small>Class</small>

            <strong>
                <?= htmlspecialchars(
                    $classDisplay
                ) ?>
            </strong>
        </div>

        <div>
            <small>Subject</small>

            <strong>
                <?= htmlspecialchars(
                    $information['subject_name']
                ) ?>
                (
                <?= htmlspecialchars(
                    $information['subject_code']
                ) ?>
                )
            </strong>
        </div>

        <div>
            <small>Examination Type</small>

            <strong>
                <?= htmlspecialchars(
                    $information['type_name']
                ) ?>
            </strong>
        </div>

        <div>
            <small>Maximum Mark</small>

            <strong>
                <?= number_format(
                    (float) $information[
                        'maximum_mark'
                    ],
                    0
                ) ?>
            </strong>
        </div>

        <div>
            <small>Pass Mark</small>

            <strong>
                <?= number_format(
                    (float) $information[
                        'pass_mark'
                    ],
                    0
                ) ?>
            </strong>
        </div>

        <div>
            <small>Total Pupils</small>

            <strong>
                <?= number_format(
                    $totalPupils
                ) ?>
            </strong>
        </div>

    </section>

    <section class="summary-grid">

        <div>
            <small>Highest</small>
            <strong>
                <?= number_format(
                    $highestMark,
                    1
                ) ?>
            </strong>
        </div>

        <div>
            <small>Lowest</small>
            <strong>
                <?= number_format(
                    $lowestMark,
                    1
                ) ?>
            </strong>
        </div>

        <div>
            <small>Average</small>
            <strong>
                <?= number_format(
                    $averageMark,
                    1
                ) ?>
            </strong>
        </div>

        <div>
            <small>Pass Rate</small>
            <strong>
                <?= number_format(
                    $passRate,
                    1
                ) ?>%
            </strong>
        </div>

        <div>
            <small>Marked</small>
            <strong>
                <?= number_format(
                    $totalMarked
                ) ?>
            </strong>
        </div>

        <div>
            <small>Passed</small>
            <strong>
                <?= number_format(
                    $totalPassed
                ) ?>
            </strong>
        </div>

        <div>
            <small>Failed</small>
            <strong>
                <?= number_format(
                    $totalFailed
                ) ?>
            </strong>
        </div>

        <div>
            <small>Absent</small>
            <strong>
                <?= number_format(
                    $totalAbsent
                ) ?>
            </strong>
        </div>

    </section>

    <div class="section-heading">
        Pupil Subject Ranking
    </div>

    <table>

        <thead>
            <tr>
                <th>Position</th>
                <th>Admission Number</th>
                <th>Pupil Name</th>
                <th>Gender</th>
                <th>Mark</th>
                <th>Grade</th>
                <th>Points</th>
                <th>Remark</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($results as $result): ?>

            <?php
            $fullName = trim(
                $result['first_name'] . ' ' .
                ($result['middle_name'] ?? '') . ' ' .
                $result['last_name']
            );
            ?>

            <tr>

                <td>
                    <?= $result['position']
                        !== null
                        ? (int) $result[
                            'position'
                        ]
                        : '—' ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $result[
                            'admission_number'
                        ]
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $fullName
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $result['gender']
                    ) ?>
                </td>

                <td>
                    <?= $result['is_absent']
                        ? 'ABS'
                        : (
                            $result[
                                'mark_obtained'
                            ] !== null
                                ? number_format(
                                    (float) $result[
                                        'mark_obtained'
                                    ],
                                    1
                                )
                                : '—'
                        ) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        (string) (
                            $result['grade']
                            ?? '—'
                        )
                    ) ?>
                </td>

                <td>
                    <?= $result['points'] !== null
                        ? number_format(
                            (float) $result[
                                'points'
                            ],
                            0
                        )
                        : '—' ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        (string) (
                            $result[
                                'teacher_remark'
                            ] ?? '—'
                        )
                    ) ?>
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <div class="section-heading">
        Grade Distribution
    </div>

    <table class="grade-table">

        <thead>
            <tr>
                <th>Grade</th>
                <th>Number of Pupils</th>
                <th>Percentage</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($gradeDistribution === []): ?>

            <tr>
                <td colspan="3">
                    No grades are available.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach (
                $gradeDistribution as
                $grade => $count
            ): ?>

                <tr>
                    <td>
                        <?= htmlspecialchars(
                            $grade
                        ) ?>
                    </td>

                    <td>
                        <?= number_format(
                            $count
                        ) ?>
                    </td>

                    <td>
                        <?= $totalMarked > 0
                            ? number_format(
                                (
                                    $count /
                                    $totalMarked
                                ) * 100,
                                1
                            ) . '%'
                            : '0.0%' ?>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

    <footer class="footer">

        <span>
            Generated on
            <?= htmlspecialchars(
                date('d F Y')
            ) ?>
        </span>

        <span>
            Royal Kindergarten and Primary School Management System
        </span>

    </footer>

</main>

</body>
</html>