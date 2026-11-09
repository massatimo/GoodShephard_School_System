<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Validate request
|--------------------------------------------------------------------------
*/

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

$pupilId = filter_input(
    INPUT_GET,
    'pupil_id',
    FILTER_VALIDATE_INT
);

if (
    !$examinationId ||
    !$examinationClassId ||
    !$pupilId
) {
    exit('Invalid report-card request.');
}

/*
|--------------------------------------------------------------------------
| School configuration
|--------------------------------------------------------------------------
*/

$schoolInformation = require __DIR__ . '/../config/school.php';
$schoolName = $schoolInformation['name'];
$schoolInitials = 'RKPS';
$schoolAddress = $schoolInformation['address'];
$schoolTelephone = $schoolInformation['telephone'];

$schoolLogo = '../assets/images/royal-logo.jpg';

/*
|--------------------------------------------------------------------------
| Retrieve examination, class and pupil
|--------------------------------------------------------------------------
*/

$profileStatement = $pdo->prepare(
    'SELECT
        examinations.id AS examination_id,
        examinations.examination_name,
        examinations.status AS examination_status,
        examinations.start_date,
        examinations.end_date,

        examination_classes.id AS examination_class_id,
        examination_classes.class_id,
        examination_classes.stream_id,

        examination_types.type_name,

        academic_years.id AS academic_year_id,
        academic_years.year_name,

        terms.id AS term_id,
        terms.term_name,

        classes.class_name,
        classes.class_code,
        classes.class_level,

        streams.stream_name,

        pupils.id AS pupil_id,
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender,
        pupils.date_of_birth,
        pupils.photo,
        pupils.guardian_name,
        pupils.guardian_phone

     FROM examination_classes

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

     INNER JOIN pupils
        ON pupils.class_id =
            examination_classes.class_id

     WHERE examinations.id = :examination_id

       AND examination_classes.id =
            :examination_class_id

       AND pupils.id = :pupil_id

       AND (
            examination_classes.stream_id IS NULL
            OR pupils.stream_id =
                examination_classes.stream_id
       )

     LIMIT 1'
);

$profileStatement->execute([
    'examination_id' => $examinationId,
    'examination_class_id' => $examinationClassId,
    'pupil_id' => $pupilId,
]);

$profile = $profileStatement->fetch();

if (!$profile) {
    exit(
        'The pupil does not belong to the selected examination class.'
    );
}

/*
|--------------------------------------------------------------------------
| Retrieve subject results
|--------------------------------------------------------------------------
*/

$subjectStatement = $pdo->prepare(
    'SELECT
        examination_subjects.id
            AS examination_subject_id,

        examination_subjects.maximum_mark,
        examination_subjects.pass_mark,
        examination_subjects.weight_percentage,

        subjects.subject_name,
        subjects.subject_code,

        pupil_marks.mark_obtained,
        pupil_marks.grade,
        pupil_marks.points,
        pupil_marks.teacher_remark,
        pupil_marks.is_absent

     FROM examination_subjects

     INNER JOIN subjects
        ON subjects.id =
            examination_subjects.subject_id

     LEFT JOIN pupil_marks
        ON pupil_marks.examination_subject_id =
            examination_subjects.id

       AND pupil_marks.pupil_id = :pupil_id

     WHERE examination_subjects.examination_class_id =
        :examination_class_id

       AND examination_subjects.status = "Active"

     ORDER BY subjects.subject_name'
);

$subjectStatement->execute([
    'pupil_id' => $pupilId,
    'examination_class_id' => $examinationClassId,
]);

$subjectResults = $subjectStatement->fetchAll();

/*
|--------------------------------------------------------------------------
| Calculate pupil totals
|--------------------------------------------------------------------------
*/

$totalObtained = 0.0;
$totalMaximum = 0.0;
$totalPoints = 0.0;
$subjectsWithMarks = 0;
$absentSubjects = 0;

foreach ($subjectResults as &$subjectResult) {
    $maximumMark =
        (float) $subjectResult['maximum_mark'];

    $totalMaximum += $maximumMark;

    $isAbsent =
        (int) ($subjectResult['is_absent'] ?? 0) === 1;

    $subjectResult['is_absent'] = $isAbsent;

    if ($isAbsent) {
        $absentSubjects++;
        $subjectResult['percentage'] = null;
        continue;
    }

    if ($subjectResult['mark_obtained'] === null) {
        $subjectResult['percentage'] = null;
        continue;
    }

    $markObtained =
        (float) $subjectResult['mark_obtained'];

    $totalObtained += $markObtained;
    $subjectsWithMarks++;

    if ($subjectResult['points'] !== null) {
        $totalPoints +=
            (float) $subjectResult['points'];
    }

    $subjectResult['percentage'] =
        $maximumMark > 0
            ? ($markObtained / $maximumMark) * 100
            : 0;
}

unset($subjectResult);

$average =
    $totalMaximum > 0
        ? ($totalObtained / $totalMaximum) * 100
        : 0;

/*
|--------------------------------------------------------------------------
| Retrieve pupils belonging to this examination class
|--------------------------------------------------------------------------
*/

$pupilSql = '
    SELECT
        pupils.id
    FROM pupils
    WHERE pupils.class_id = :class_id
      AND pupils.pupil_status = "Active"
';

$pupilParameters = [
    'class_id' => $profile['class_id'],
];

if (!empty($profile['stream_id'])) {
    $pupilSql .= '
        AND pupils.stream_id = :stream_id
    ';

    $pupilParameters['stream_id'] =
        $profile['stream_id'];
}

$pupilStatement = $pdo->prepare($pupilSql);
$pupilStatement->execute($pupilParameters);

$classPupilIds = array_map(
    'intval',
    $pupilStatement->fetchAll(PDO::FETCH_COLUMN)
);

$totalClassPupils = count($classPupilIds);

/*
|--------------------------------------------------------------------------
| Calculate class position
|--------------------------------------------------------------------------
*/

$classPosition = null;
$classResults = [];

if ($classPupilIds !== []) {
    foreach ($classPupilIds as $classPupilId) {
        $classResults[$classPupilId] = [
            'total' => 0.0,
            'average' => 0.0,
        ];
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($classPupilIds), '?')
    );

    $classMarksStatement = $pdo->prepare(
        'SELECT
            pupil_marks.pupil_id,
            pupil_marks.mark_obtained,
            pupil_marks.is_absent

         FROM pupil_marks

         INNER JOIN examination_subjects
            ON examination_subjects.id =
                pupil_marks.examination_subject_id

         WHERE examination_subjects.examination_class_id = ?

           AND examination_subjects.status = "Active"

           AND pupil_marks.pupil_id IN (' .
            $placeholders .
        ')'
    );

    $classMarksStatement->execute(
        array_merge(
            [$examinationClassId],
            $classPupilIds
        )
    );

    foreach (
        $classMarksStatement->fetchAll()
        as $classMark
    ) {
        if (
            (int) $classMark['is_absent'] === 1 ||
            $classMark['mark_obtained'] === null
        ) {
            continue;
        }

        $resultPupilId =
            (int) $classMark['pupil_id'];

        $classResults[$resultPupilId]['total'] +=
            (float) $classMark['mark_obtained'];
    }

    foreach ($classResults as &$classResult) {
        $classResult['average'] =
            $totalMaximum > 0
                ? (
                    $classResult['total'] /
                    $totalMaximum
                ) * 100
                : 0;
    }

    unset($classResult);

    uasort(
        $classResults,
        static function (
            array $first,
            array $second
        ): int {
            $averageComparison =
                $second['average']
                <=> $first['average'];

            if ($averageComparison !== 0) {
                return $averageComparison;
            }

            return $second['total']
                <=> $first['total'];
        }
    );

    $positionCounter = 0;
    $currentPosition = 0;
    $previousAverage = null;
    $previousTotal = null;

    foreach (
        $classResults as
        $resultPupilId => $classResult
    ) {
        $positionCounter++;

        if (
            $previousAverage === null ||
            round(
                (float) $classResult['average'],
                4
            ) !== round(
                (float) $previousAverage,
                4
            ) ||
            round(
                (float) $classResult['total'],
                4
            ) !== round(
                (float) $previousTotal,
                4
            )
        ) {
            $currentPosition = $positionCounter;
        }

        if ((int) $resultPupilId === $pupilId) {
            $classPosition = $currentPosition;
            break;
        }

        $previousAverage =
            $classResult['average'];

        $previousTotal =
            $classResult['total'];
    }
}

/*
|--------------------------------------------------------------------------
| Retrieve saved report information
|--------------------------------------------------------------------------
*/

$reportStatement = $pdo->prepare(
    'SELECT
        report_card_comments.*,

        next_class.class_name
            AS next_class_name,

        next_class.class_code
            AS next_class_code

     FROM report_card_comments

     LEFT JOIN classes AS next_class
        ON next_class.id =
            report_card_comments.next_class_id

     WHERE report_card_comments.examination_class_id =
        :examination_class_id

       AND report_card_comments.pupil_id =
        :pupil_id

     LIMIT 1'
);

$reportStatement->execute([
    'examination_class_id' => $examinationClassId,
    'pupil_id' => $pupilId,
]);

$reportCard = $reportStatement->fetch();

/*
|--------------------------------------------------------------------------
| Attendance values
|--------------------------------------------------------------------------
*/

$daysOpen =
    (int) ($reportCard['days_open'] ?? 0);

$daysPresent =
    (int) ($reportCard['days_present'] ?? 0);

$daysAbsent = max(
    0,
    $daysOpen - $daysPresent
);

$attendanceRate =
    $daysOpen > 0
        ? ($daysPresent / $daysOpen) * 100
        : 0;

/*
|--------------------------------------------------------------------------
| Comments and promotion
|--------------------------------------------------------------------------
*/

$classTeacherComment = trim(
    (string) (
        $reportCard['class_teacher_comment'] ?? ''
    )
);

$headTeacherComment = trim(
    (string) (
        $reportCard['head_teacher_comment'] ?? ''
    )
);

$promotionStatus =
    $reportCard['promotion_status']
    ?? 'Pending';

$nextClassName =
    $reportCard['next_class_name']
    ?? null;

/*
|--------------------------------------------------------------------------
| General performance description
|--------------------------------------------------------------------------
*/

function overallPerformance(
    float $average
): string {
    if ($average >= 90) {
        return 'Outstanding';
    }

    if ($average >= 80) {
        return 'Excellent';
    }

    if ($average >= 70) {
        return 'Very Good';
    }

    if ($average >= 60) {
        return 'Good';
    }

    if ($average >= 50) {
        return 'Satisfactory';
    }

    return 'Needs Improvement';
}

function ordinalPosition(?int $position): string
{
    if ($position === null) {
        return '—';
    }

    $lastTwoDigits = $position % 100;

    if (
        $lastTwoDigits >= 11 &&
        $lastTwoDigits <= 13
    ) {
        return $position . 'th';
    }

    return match ($position % 10) {
        1 => $position . 'st',
        2 => $position . 'nd',
        3 => $position . 'rd',
        default => $position . 'th',
    };
}

$performanceDescription =
    overallPerformance($average);

$fullName = trim(
    $profile['first_name'] . ' ' .
    ($profile['middle_name'] ?? '') . ' ' .
    $profile['last_name']
);

$classDisplay =
    $profile['class_name'];

if (!empty($profile['stream_name'])) {
    $classDisplay .=
        ' – ' . $profile['stream_name'];
}

$pupilPhoto =
    !empty($profile['photo'])
        ? '../' . ltrim(
            (string) $profile['photo'],
            '/'
        )
        : '../assets/images/default-avatar.png';

$generatedDate = date('d F Y');
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
        Report Card -
        <?= htmlspecialchars($fullName) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="../assets/css/report-card.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="print-toolbar">

    <a
        href="pupil-result.php?<?= htmlspecialchars(
            http_build_query([
                'examination_id' =>
                    $examinationId,
                'examination_class_id' =>
                    $examinationClassId,
                'pupil_id' =>
                    $pupilId,
            ])
        ) ?>"
        class="toolbar-button toolbar-back"
    >
        <i class="bi bi-arrow-left"></i>
        Back
    </a>

    <button
        type="button"
        class="toolbar-button toolbar-print"
        onclick="window.print()"
    >
        <i class="bi bi-printer-fill"></i>
        Print Report Card
    </button>

</div>

<main class="report-card-page">

    <!-- SCHOOL HEADER -->

    <header class="report-school-header">

        <div class="report-logo">

            <img
                src="<?= htmlspecialchars($schoolLogo) ?>"
                alt="<?= htmlspecialchars($schoolName) ?>"
            >

        </div>

        <div class="report-school-identity">

            <h1>
                <?= htmlspecialchars($schoolName) ?>
            </h1>

            <p>
                <?= htmlspecialchars($schoolAddress) ?>
            </p>

            <p>
                Tel:
                <?= htmlspecialchars($schoolTelephone) ?>

            </p>

            <strong>
                Effort Achieves
            </strong>

        </div>

        <div class="report-pupil-photo">

            <img
                src="<?= htmlspecialchars($pupilPhoto) ?>"
                alt="<?= htmlspecialchars($fullName) ?>"
                onerror="
                    this.src='../assets/images/default-avatar.png'
                "
            >

        </div>

    </header>

    <section class="report-title-section">

        <span>
            <?= htmlspecialchars(
                strtoupper(
                    (string) $profile['term_name']
                )
            ) ?>
            REPORT
        </span>

        <h2>
            <?= htmlspecialchars(
                $profile['examination_name']
            ) ?>
        </h2>

        <p>
            Academic Year
            <?= htmlspecialchars(
                $profile['year_name']
            ) ?>
        </p>

    </section>

    <!-- PUPIL DETAILS -->

    <section class="report-information-grid">

        <div>
            <small>Pupil Name</small>

            <strong>
                <?= htmlspecialchars(
                    strtoupper($fullName)
                ) ?>
            </strong>
        </div>

        <div>
            <small>Admission Number</small>

            <strong>
                <?= htmlspecialchars(
                    $profile['admission_number']
                ) ?>
            </strong>
        </div>

        <div>
            <small>Class</small>

            <strong>
                <?= htmlspecialchars($classDisplay) ?>
            </strong>
        </div>

        <div>
            <small>Gender</small>

            <strong>
                <?= htmlspecialchars(
                    $profile['gender']
                ) ?>
            </strong>
        </div>

        <div>
            <small>Examination Type</small>

            <strong>
                <?= htmlspecialchars(
                    $profile['type_name']
                ) ?>
            </strong>
        </div>

        <div>
            <small>Class Position</small>

            <strong>
                <?= htmlspecialchars(
                    ordinalPosition(
                        $classPosition
                    )
                ) ?>

                out of

                <?= number_format(
                    $totalClassPupils
                ) ?>
            </strong>
        </div>

    </section>

    <!-- SUBJECT RESULTS -->

    <section class="report-section">

        <div class="report-section-heading">

            <h3>Academic Performance</h3>

            <span>
                <?= count($subjectResults) ?>
                Subjects
            </span>

        </div>

        <table class="report-results-table">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Maximum</th>
                    <th>Mark</th>
                    <th>Grade</th>
                    <th>Points</th>
                    <th>Remark</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($subjectResults === []): ?>

                <tr>
                    <td colspan="7">
                        No subject results are available.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach (
                    $subjectResults as
                    $index => $subjectResult
                ): ?>

                    <tr>

                        <td>
                            <?= $index + 1 ?>
                        </td>

                        <td class="subject-name-cell">

                            <strong>
                                <?= htmlspecialchars(
                                    $subjectResult[
                                        'subject_name'
                                    ]
                                ) ?>
                            </strong>

                            <small>
                                <?= htmlspecialchars(
                                    $subjectResult[
                                        'subject_code'
                                    ]
                                ) ?>
                            </small>

                        </td>

                        <td>
                            <?= number_format(
                                (float) $subjectResult[
                                    'maximum_mark'
                                ],
                                0
                            ) ?>
                        </td>

                        <td>

                            <?php if (
                                $subjectResult['is_absent']
                            ): ?>

                                <span class="report-absent">
                                    ABS
                                </span>

                            <?php elseif (
                                $subjectResult[
                                    'mark_obtained'
                                ] !== null
                            ): ?>

                                <strong>
                                    <?= number_format(
                                        (float) $subjectResult[
                                            'mark_obtained'
                                        ],
                                        1
                                    ) ?>
                                </strong>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= htmlspecialchars(
                                (string) (
                                    $subjectResult[
                                        'grade'
                                    ] ?? '—'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= $subjectResult['points']
                                !== null
                                ? number_format(
                                    (float) $subjectResult[
                                        'points'
                                    ],
                                    0
                                )
                                : '—' ?>
                        </td>

                        <td class="remark-cell">
                            <?= htmlspecialchars(
                                (string) (
                                    $subjectResult[
                                        'teacher_remark'
                                    ] ?? '—'
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

            <tfoot>
                <tr>
                    <th colspan="2">
                        Overall Result
                    </th>

                    <th>
                        <?= number_format(
                            $totalMaximum,
                            0
                        ) ?>
                    </th>

                    <th>
                        <?= number_format(
                            $totalObtained,
                            1
                        ) ?>
                    </th>

                    <th colspan="2">
                        Aggregate:
                        <?= number_format(
                            $totalPoints,
                            0
                        ) ?>
                    </th>

                    <th>
                        <?= htmlspecialchars(
                            $performanceDescription
                        ) ?>
                    </th>
                </tr>
            </tfoot>

        </table>

    </section>

    <!-- PERFORMANCE SUMMARY -->

    <section class="report-summary-grid">

        <article>
            <small>Total Marks</small>

            <strong>
                <?= number_format(
                    $totalObtained,
                    1
                ) ?>

                /

                <?= number_format(
                    $totalMaximum,
                    0
                ) ?>
            </strong>
        </article>

        <article>
            <small>Average</small>

            <strong>
                <?= number_format(
                    $average,
                    1
                ) ?>%
            </strong>
        </article>

        <article>
            <small>Aggregate Points</small>

            <strong>
                <?= number_format(
                    $totalPoints,
                    0
                ) ?>
            </strong>
        </article>

        <article>
            <small>Position</small>

            <strong>
                <?= htmlspecialchars(
                    ordinalPosition(
                        $classPosition
                    )
                ) ?>

                /

                <?= number_format(
                    $totalClassPupils
                ) ?>
            </strong>
        </article>

        <article>
            <small>Subjects Marked</small>

            <strong>
                <?= number_format(
                    $subjectsWithMarks
                ) ?>

                /

                <?= number_format(
                    count($subjectResults)
                ) ?>
            </strong>
        </article>

        <article>
            <small>Overall Performance</small>

            <strong>
                <?= htmlspecialchars(
                    $performanceDescription
                ) ?>
            </strong>
        </article>

    </section>

    <!-- ATTENDANCE -->

    <section class="report-section">

        <div class="report-section-heading">
            <h3>Attendance Summary</h3>
        </div>

        <div class="report-attendance-grid">

            <div>
                <small>Days School Open</small>

                <strong>
                    <?= number_format($daysOpen) ?>
                </strong>
            </div>

            <div>
                <small>Days Present</small>

                <strong>
                    <?= number_format($daysPresent) ?>
                </strong>
            </div>

            <div>
                <small>Days Absent</small>

                <strong>
                    <?= number_format($daysAbsent) ?>
                </strong>
            </div>

            <div>
                <small>Attendance Rate</small>

                <strong>
                    <?= number_format(
                        $attendanceRate,
                        1
                    ) ?>%
                </strong>
            </div>

        </div>

    </section>

    <!-- COMMENTS -->

    <section class="report-comments-grid">

        <article>

            <div class="comment-heading">
                Class Teacher’s Comment
            </div>

            <p>
                <?= $classTeacherComment !== ''
                    ? nl2br(
                        htmlspecialchars(
                            $classTeacherComment
                        )
                    )
                    : 'No class teacher comment has been entered.' ?>
            </p>

            <div class="signature-line">
                Class Teacher’s Signature
            </div>

        </article>

        <article>

            <div class="comment-heading">
                Head Teacher’s Comment
            </div>

            <p>
                <?= $headTeacherComment !== ''
                    ? nl2br(
                        htmlspecialchars(
                            $headTeacherComment
                        )
                    )
                    : 'No head teacher comment has been entered.' ?>
            </p>

            <div class="signature-line">
                Head Teacher’s Signature
            </div>

        </article>

    </section>

    <!-- PROMOTION -->

    <section class="report-promotion-section">

        <div>

            <small>Promotion Decision</small>

            <strong>
                <?= htmlspecialchars(
                    $promotionStatus
                ) ?>
            </strong>

        </div>

        <div>

            <small>Next Class</small>

            <strong>
                <?= $nextClassName
                    ? htmlspecialchars(
                        $nextClassName
                    )
                    : 'Not specified' ?>
            </strong>

        </div>

        <div>

            <small>Next Term Begins</small>

            <strong>
                __________________
            </strong>

        </div>

        <div class="report-stamp-space">

            <span>Official School Stamp</span>

        </div>

    </section>

    <footer class="report-footer">

        <div>
            Generated on
            <?= htmlspecialchars(
                $generatedDate
            ) ?>
        </div>

        <div>
            <?= htmlspecialchars(
                $schoolInitials
            ) ?>
            School Management System
        </div>

    </footer>

</main>

<script>
window.addEventListener('beforeprint', () => {
    document.title =
        <?= json_encode(
            $profile['admission_number'] .
            ' - ' .
            $fullName .
            ' - Report Card'
        ) ?>;
});
</script>

</body>
</html>
