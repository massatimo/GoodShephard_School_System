<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    $_SESSION['error_message'] =
        'Invalid pupil result request.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve examination, class and pupil information
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

        academic_years.year_name,
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
    'examination_class_id' =>
        $examinationClassId,
    'pupil_id' => $pupilId,
]);

$profile = $profileStatement->fetch();

if (!$profile) {
    $_SESSION['error_message'] =
        'The pupil does not belong to the selected examination class.';

    header(
        'Location: results.php?examination_id=' .
        $examinationId
    );
    exit;
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
    'examination_class_id' =>
        $examinationClassId,
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
| Calculate pupil position
|--------------------------------------------------------------------------
*/

$classPupilSql = '
    SELECT pupils.id
    FROM pupils
    WHERE pupils.class_id = :class_id
      AND pupils.pupil_status = "Active"
';

$classPupilParameters = [
    'class_id' => $profile['class_id'],
];

if (!empty($profile['stream_id'])) {
    $classPupilSql .= '
        AND pupils.stream_id = :stream_id
    ';

    $classPupilParameters['stream_id'] =
        $profile['stream_id'];
}

$classPupilStatement =
    $pdo->prepare($classPupilSql);

$classPupilStatement->execute(
    $classPupilParameters
);

$classPupilIds = array_map(
    'intval',
    $classPupilStatement->fetchAll(
        PDO::FETCH_COLUMN
    )
);

$classResults = [];

if ($classPupilIds !== []) {
    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($classPupilIds),
            '?'
        )
    );

    $classMarksStatement = $pdo->prepare(
        'SELECT
            pupil_marks.pupil_id,
            pupil_marks.mark_obtained,
            pupil_marks.is_absent,
            examination_subjects.maximum_mark

         FROM pupil_marks

         INNER JOIN examination_subjects
            ON examination_subjects.id =
                pupil_marks.examination_subject_id

         WHERE examination_subjects.examination_class_id = ?
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

    $classMarks = $classMarksStatement->fetchAll();

    $classMaximumStatement = $pdo->prepare(
        'SELECT COALESCE(
            SUM(maximum_mark),
            0
         )
         FROM examination_subjects
         WHERE examination_class_id =
            :examination_class_id
           AND status = "Active"'
    );

    $classMaximumStatement->execute([
        'examination_class_id' =>
            $examinationClassId,
    ]);

    $classTotalMaximum =
        (float) $classMaximumStatement->fetchColumn();

    foreach ($classPupilIds as $classPupilId) {
        $classResults[$classPupilId] = [
            'total' => 0.0,
            'average' => 0.0,
        ];
    }

    foreach ($classMarks as $classMark) {
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
            $classTotalMaximum > 0
                ? (
                    $classResult['total'] /
                    $classTotalMaximum
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
}

$classPosition = null;
$currentPosition = 0;
$previousAverage = null;
$previousTotal = null;

$resultIndex = 0;

foreach (
    $classResults as
    $resultPupilId => $classResult
) {
    $resultIndex++;
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
        $currentPosition = $resultIndex;
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

$totalClassPupils = count($classPupilIds);

/*
|--------------------------------------------------------------------------
| Retrieve attendance from attendance records
|--------------------------------------------------------------------------
*/

try {
    $attendanceStatement = $pdo->prepare(
        'SELECT
            COUNT(DISTINCT attendance_registers.attendance_date)
                AS days_open,

            SUM(
                CASE
                    WHEN attendance_records.attendance_status
                        IN ("Present", "Late")
                    THEN 1
                    ELSE 0
                END
            ) AS days_present

         FROM attendance_records

         INNER JOIN attendance_registers
            ON attendance_registers.id =
                attendance_records.attendance_register_id

         WHERE attendance_records.pupil_id =
            :pupil_id

           AND attendance_registers.academic_year_id = (
                SELECT academic_year_id
                FROM examinations
                WHERE id = :examination_id
           )

           AND attendance_registers.term_id = (
                SELECT term_id
                FROM examinations
                WHERE id = :examination_id_term
           )'
    );

    $attendanceStatement->execute([
        'pupil_id' => $pupilId,
        'examination_id' => $examinationId,
        'examination_id_term' =>
            $examinationId,
    ]);

    $attendanceSummary =
        $attendanceStatement->fetch();
} catch (PDOException $exception) {
    /*
     * This fallback allows the report page to work
     * if attendance column names differ.
     */
    $attendanceSummary = [
        'days_open' => 0,
        'days_present' => 0,
    ];
}

/*
|--------------------------------------------------------------------------
| Retrieve saved report-card information
|--------------------------------------------------------------------------
*/

$reportStatement = $pdo->prepare(
    'SELECT
        report_card_comments.*,
        next_class.class_name
            AS next_class_name

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
    'examination_class_id' =>
        $examinationClassId,
    'pupil_id' => $pupilId,
]);

$reportCard = $reportStatement->fetch();

/*
|--------------------------------------------------------------------------
| Prepare attendance values
|--------------------------------------------------------------------------
*/

$automaticDaysOpen =
    (int) ($attendanceSummary['days_open'] ?? 0);

$automaticDaysPresent =
    (int) ($attendanceSummary['days_present'] ?? 0);

$daysOpen =
    $reportCard
        ? (int) $reportCard['days_open']
        : $automaticDaysOpen;

$daysPresent =
    $reportCard
        ? (int) $reportCard['days_present']
        : $automaticDaysPresent;

$daysAbsent = max(
    0,
    $daysOpen - $daysPresent
);

/*
|--------------------------------------------------------------------------
| Suggested teacher comment
|--------------------------------------------------------------------------
*/

function suggestedResultComment(
    float $average
): string {
    if ($average >= 90) {
        return
            'Outstanding performance. Keep maintaining this excellent standard.';
    }

    if ($average >= 80) {
        return
            'Very good performance. Continue working hard.';
    }

    if ($average >= 70) {
        return
            'Good performance. There is still room for improvement.';
    }

    if ($average >= 60) {
        return
            'Fair performance. More effort is required next term.';
    }

    if ($average >= 50) {
        return
            'Satisfactory performance. More practice and consistency are needed.';
    }

    return
        'The pupil needs significant improvement and closer academic support.';
}

$classTeacherComment =
    trim(
        (string) (
            $reportCard['class_teacher_comment']
            ?? ''
        )
    );

if ($classTeacherComment === '') {
    $classTeacherComment =
        suggestedResultComment($average);
}

$headTeacherComment =
    trim(
        (string) (
            $reportCard['head_teacher_comment']
            ?? ''
        )
    );

$promotionStatus =
    $reportCard['promotion_status']
    ?? (
        $average >= 50
            ? 'Promoted'
            : 'Pending'
    );

$nextClassId =
    $reportCard['next_class_id']
    ?? null;

/*
|--------------------------------------------------------------------------
| Retrieve classes for next-class selection
|--------------------------------------------------------------------------
*/

$classes = $pdo->query(
    "SELECT
        id,
        class_name,
        class_code,
        class_level
     FROM classes
     WHERE status = 'Active'
     ORDER BY class_level"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION['success_message'] ?? '';

$errorMessage =
    $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
);

$fullName = trim(
    $profile['first_name'] . ' ' .
    ($profile['middle_name'] ?? '') . ' ' .
    $profile['last_name']
);

$photoPath =
    !empty($profile['photo'])
        ? '../' . ltrim(
            (string) $profile['photo'],
            '/'
        )
        : '../assets/images/default-avatar.png';

function subjectResultStatus(
    ?float $percentage
): string {
    if ($percentage === null) {
        return 'result-status-empty';
    }

    if ($percentage >= 80) {
        return 'result-status-excellent';
    }

    if ($percentage >= 50) {
        return 'result-status-pass';
    }

    return 'result-status-fail';
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
        | Pupil Result
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

        <?php if ($successMessage !== ''): ?>

            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>

                <?= htmlspecialchars($successMessage) ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>

            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= htmlspecialchars($errorMessage) ?>
            </div>

        <?php endif; ?>

        <section class="module-heading">

            <div>
                <span class="module-label">
                    PUPIL RESULT PROFILE
                </span>

                <h2>
                    <?= htmlspecialchars($fullName) ?>
                </h2>

                <p>
                    <?= htmlspecialchars(
                        $profile['examination_name']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $profile['year_name']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $profile['term_name']
                    ) ?>
                </p>
            </div>

            <div class="profile-heading-actions">

                <a
                    href="results.php?<?= htmlspecialchars(
                        http_build_query([
                            'examination_id' =>
                                $examinationId,
                            'examination_class_id' =>
                                $examinationClassId,
                        ])
                    ) ?>"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Class Results
                </a>

                <a
                    href="print-report-card.php?<?= htmlspecialchars(
                        http_build_query([
                            'examination_id' =>
                                $examinationId,
                            'examination_class_id' =>
                                $examinationClassId,
                            'pupil_id' =>
                                $pupilId,
                        ])
                    ) ?>"
                    target="_blank"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-printer-fill me-2"></i>
                    Print Report Card
                </a>

            </div>

        </section>

        <!-- PUPIL PROFILE -->

        <section class="pupil-result-profile">

            <div class="pupil-result-identity">

                <div class="pupil-result-photo">

                    <img
                        src="<?= htmlspecialchars($photoPath) ?>"
                        alt="<?= htmlspecialchars($fullName) ?>"
                        onerror="
                            this.src='../assets/images/default-avatar.png'
                        "
                    >

                </div>

                <div>

                    <span>Pupil Result Profile</span>

                    <h3>
                        <?= htmlspecialchars($fullName) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $profile['admission_number']
                        ) ?>
                    </p>

                </div>

            </div>

            <div class="pupil-result-details">

                <div>
                    <small>Class</small>

                    <strong>
                        <?= htmlspecialchars(
                            $profile['class_name']
                        ) ?>

                        <?= !empty($profile['stream_name'])
                            ? ' – ' .
                                htmlspecialchars(
                                    $profile['stream_name']
                                )
                            : '' ?>
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
                    <small>Academic Year</small>

                    <strong>
                        <?= htmlspecialchars(
                            $profile['year_name']
                        ) ?>
                    </strong>
                </div>

                <div>
                    <small>Term</small>

                    <strong>
                        <?= htmlspecialchars(
                            $profile['term_name']
                        ) ?>
                    </strong>
                </div>

            </div>

        </section>

        <!-- PERFORMANCE SUMMARY -->

        <section class="row g-4 mb-4">

            <div class="col-sm-6 col-xl-3">

                <article class="result-summary-card">

                    <span class="result-summary-icon">
                        <i class="bi bi-calculator-fill"></i>
                    </span>

                    <div>
                        <small>Total Marks</small>

                        <strong>
                            <?= number_format(
                                $totalObtained,
                                1
                            ) ?>
                            /
                            <?= number_format(
                                $totalMaximum,
                                1
                            ) ?>
                        </strong>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="result-summary-card">

                    <span class="result-summary-icon">
                        <i class="bi bi-percent"></i>
                    </span>

                    <div>
                        <small>Average</small>

                        <strong>
                            <?= number_format(
                                $average,
                                1
                            ) ?>%
                        </strong>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="result-summary-card">

                    <span class="result-summary-icon">
                        <i class="bi bi-award-fill"></i>
                    </span>

                    <div>
                        <small>Aggregate Points</small>

                        <strong>
                            <?= number_format(
                                $totalPoints,
                                0
                            ) ?>
                        </strong>
                    </div>

                </article>

            </div>

            <div class="col-sm-6 col-xl-3">

                <article class="result-summary-card">

                    <span class="result-summary-icon">
                        <i class="bi bi-trophy-fill"></i>
                    </span>

                    <div>
                        <small>Class Position</small>

                        <strong>
                            <?= $classPosition !== null
                                ? (
                                    $classPosition .
                                    ' / ' .
                                    $totalClassPupils
                                )
                                : '—' ?>
                        </strong>
                    </div>

                </article>

            </div>

        </section>

        <!-- SUBJECT RESULTS -->

        <section class="dashboard-card mb-4">

            <div class="dashboard-card-header">

                <div>
                    <h3>Subject Results</h3>

                    <p>
                        Marks, grades, points and teacher remarks
                    </p>
                </div>

                <span class="status-badge status-active">
                    <?= number_format(
                        $subjectsWithMarks
                    ) ?>
                    subjects marked
                </span>

            </div>

            <div class="table-responsive">

                <table class="table dashboard-table align-middle">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Maximum</th>
                            <th>Mark</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Points</th>
                            <th>Remark</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($subjectResults === []): ?>

                        <tr>
                            <td colspan="8">

                                <div class="empty-state">
                                    <i class="bi bi-journal-x"></i>

                                    <strong>
                                        No subjects found
                                    </strong>

                                    <span>
                                        No subjects are assigned
                                        to this examination class.
                                    </span>
                                </div>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach (
                            $subjectResults as
                            $index => $subjectResult
                        ): ?>

                            <?php
                            $percentage =
                                $subjectResult['percentage'];

                            $isAbsent =
                                $subjectResult['is_absent'];
                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1 ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $subjectResult[
                                                'subject_name'
                                            ]
                                        ) ?>
                                    </strong>

                                    <small class="d-block text-muted">
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
                                        1
                                    ) ?>
                                </td>

                                <td>

                                    <?php if ($isAbsent): ?>

                                        <span class="result-absent-mark">
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

                                        <span class="text-muted">
                                            —
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if (
                                        $percentage !== null
                                    ): ?>

                                        <span
                                            class="subject-result-score
                                            <?= subjectResultStatus(
                                                (float) $percentage
                                            ) ?>"
                                        >
                                            <?= number_format(
                                                (float) $percentage,
                                                1
                                            ) ?>%
                                        </span>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            —
                                        </span>

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

                                <td>
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

                </table>

            </div>

        </section>

        <!-- ATTENDANCE AND COMMENTS -->

        <form
            action="save-report-card.php"
            method="POST"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token']
                ) ?>"
            >

            <input
                type="hidden"
                name="examination_id"
                value="<?= (int) $examinationId ?>"
            >

            <input
                type="hidden"
                name="examination_class_id"
                value="<?= (int) $examinationClassId ?>"
            >

            <input
                type="hidden"
                name="pupil_id"
                value="<?= (int) $pupilId ?>"
            >

            <div class="row g-4">

                <div class="col-xl-5">

                    <section class="form-section-card h-100">

                        <div class="form-section-header">

                            <span class="form-section-icon">
                                <i class="bi bi-calendar-check-fill"></i>
                            </span>

                            <div>
                                <h3>Attendance Summary</h3>

                                <p>
                                    Confirm the pupil’s term attendance
                                </p>
                            </div>

                        </div>

                        <div class="row g-4">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Days School Open
                                </label>

                                <input
                                    type="number"
                                    name="days_open"
                                    id="daysOpen"
                                    min="0"
                                    value="<?= $daysOpen ?>"
                                    class="form-control professional-input"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Days Present
                                </label>

                                <input
                                    type="number"
                                    name="days_present"
                                    id="daysPresent"
                                    min="0"
                                    value="<?= $daysPresent ?>"
                                    class="form-control professional-input"
                                    required
                                >

                            </div>

                            <div class="col-12">

                                <div class="attendance-report-preview">

                                    <div>
                                        <small>Days Absent</small>

                                        <strong id="daysAbsent">
                                            <?= $daysAbsent ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <small>Attendance Rate</small>

                                        <strong id="attendanceRate">
                                            <?= $daysOpen > 0
                                                ? number_format(
                                                    (
                                                        $daysPresent /
                                                        $daysOpen
                                                    ) * 100,
                                                    1
                                                ) . '%'
                                                : '0.0%' ?>
                                        </strong>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>

                <div class="col-xl-7">

                    <section class="form-section-card">

                        <div class="form-section-header">

                            <span class="form-section-icon">
                                <i class="bi bi-chat-square-text-fill"></i>
                            </span>

                            <div>
                                <h3>Report Comments</h3>

                                <p>
                                    Add teacher and head teacher comments
                                </p>
                            </div>

                        </div>

                        <div class="row g-4">

                            <div class="col-12">

                                <label class="form-label">
                                    Class Teacher Comment
                                </label>

                                <textarea
                                    name="class_teacher_comment"
                                    rows="3"
                                    maxlength="1000"
                                    class="form-control professional-input"
                                ><?= htmlspecialchars(
                                    $classTeacherComment
                                ) ?></textarea>

                            </div>

                            <div class="col-12">

                                <label class="form-label">
                                    Head Teacher Comment
                                </label>

                                <textarea
                                    name="head_teacher_comment"
                                    rows="3"
                                    maxlength="1000"
                                    class="form-control professional-input"
                                    placeholder="Enter head teacher comment"
                                ><?= htmlspecialchars(
                                    $headTeacherComment
                                ) ?></textarea>

                            </div>

                        </div>

                    </section>

                </div>

            </div>

            <!-- PROMOTION -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                    </span>

                    <div>
                        <h3>Promotion Decision</h3>

                        <p>
                            Record the pupil’s progression decision
                        </p>
                    </div>

                </div>

                <div class="row g-4">

                    <div class="col-md-6">

                        <label class="form-label">
                            Promotion Status
                        </label>

                        <select
                            name="promotion_status"
                            id="promotionStatus"
                            class="form-select professional-input"
                            required
                        >

                            <?php foreach (
                                [
                                    'Pending',
                                    'Promoted',
                                    'Repeat',
                                ] as $status
                            ): ?>

                                <option
                                    value="<?= $status ?>"
                                    <?= $promotionStatus === $status
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= $status ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Next Class
                        </label>

                        <select
                            name="next_class_id"
                            id="nextClassId"
                            class="form-select professional-input"
                        >

                            <option value="">
                                Select next class
                            </option>

                            <?php foreach ($classes as $class): ?>

                                <option
                                    value="<?= (int) $class['id'] ?>"
                                    <?= (string) $nextClassId ===
                                        (string) $class['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $class['class_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="text-muted">
                            Select the class the pupil will join
                            when promoted.
                        </small>

                    </div>

                </div>

            </section>

            <div class="form-submit-bar">

                <a
                    href="results.php?<?= htmlspecialchars(
                        http_build_query([
                            'examination_id' =>
                                $examinationId,
                            'examination_class_id' =>
                                $examinationClassId,
                        ])
                    ) ?>"
                    class="btn btn-light"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn school-primary-btn module-action-button"
                >
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Save Report Information
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
    const daysOpen =
        document.getElementById('daysOpen');

    const daysPresent =
        document.getElementById('daysPresent');

    const daysAbsent =
        document.getElementById('daysAbsent');

    const attendanceRate =
        document.getElementById('attendanceRate');

    const promotionStatus =
        document.getElementById('promotionStatus');

    const nextClass =
        document.getElementById('nextClassId');

    function updateAttendance() {
        const open =
            Math.max(0, Number(daysOpen.value) || 0);

        const present =
            Math.max(0, Number(daysPresent.value) || 0);

        const absent =
            Math.max(0, open - present);

        const rate =
            open > 0
                ? (present / open) * 100
                : 0;

        daysAbsent.textContent =
            absent.toString();

        attendanceRate.textContent =
            rate.toFixed(1) + '%';

        daysPresent.setCustomValidity(
            present > open
                ? 'Days present cannot exceed days open.'
                : ''
        );
    }

    function updatePromotionFields() {
        const promoted =
            promotionStatus.value === 'Promoted';

        nextClass.required = promoted;

        if (!promoted) {
            nextClass.removeAttribute('required');
        }
    }

    daysOpen.addEventListener(
        'input',
        updateAttendance
    );

    daysPresent.addEventListener(
        'input',
        updateAttendance
    );

    promotionStatus.addEventListener(
        'change',
        updatePromotionFields
    );

    updateAttendance();
    updatePromotionFields();
});
</script>

</body>
</html>
