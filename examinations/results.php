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

if (!$examinationId || $examinationId < 1) {
    $_SESSION['error_message'] =
        'Invalid examination selected.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve examination
|--------------------------------------------------------------------------
*/

$examStatement = $pdo->prepare(
    'SELECT
        examinations.id,
        examinations.examination_name,
        examinations.status,
        examinations.start_date,
        examinations.end_date,

        examination_types.type_name,
        examination_types.type_code,

        academic_years.year_name,
        terms.term_name

     FROM examinations

     INNER JOIN examination_types
        ON examination_types.id =
            examinations.examination_type_id

     INNER JOIN academic_years
        ON academic_years.id =
            examinations.academic_year_id

     INNER JOIN terms
        ON terms.id =
            examinations.term_id

     WHERE examinations.id = :id
     LIMIT 1'
);

$examStatement->execute([
    'id' => $examinationId,
]);

$examination = $examStatement->fetch();

if (!$examination) {
    $_SESSION['error_message'] =
        'The examination was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve assigned examination classes
|--------------------------------------------------------------------------
*/

$classStatement = $pdo->prepare(
    'SELECT
        examination_classes.id,
        examination_classes.class_id,
        examination_classes.stream_id,

        classes.class_name,
        classes.class_code,
        classes.class_level,

        streams.stream_name

     FROM examination_classes

     INNER JOIN classes
        ON classes.id =
            examination_classes.class_id

     LEFT JOIN streams
        ON streams.id =
            examination_classes.stream_id

     WHERE examination_classes.examination_id =
        :examination_id
       AND examination_classes.status = "Active"

     ORDER BY
        classes.class_level,
        streams.stream_name'
);

$classStatement->execute([
    'examination_id' => $examinationId,
]);

$examinationClasses = $classStatement->fetchAll();

/*
|--------------------------------------------------------------------------
| Automatically select first class
|--------------------------------------------------------------------------
*/

if (!$examinationClassId && $examinationClasses !== []) {
    $examinationClassId =
        (int) $examinationClasses[0]['id'];
}

/*
|--------------------------------------------------------------------------
| Confirm selected class
|--------------------------------------------------------------------------
*/

$selectedClass = null;

foreach ($examinationClasses as $examClass) {
    if (
        (int) $examClass['id'] ===
        (int) $examinationClassId
    ) {
        $selectedClass = $examClass;
        break;
    }
}

if (!$selectedClass && $examinationClasses !== []) {
    $_SESSION['error_message'] =
        'The selected class does not belong to this examination.';

    header(
        'Location: results.php?examination_id=' .
        $examinationId
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve subjects assigned to the selected class
|--------------------------------------------------------------------------
*/

$subjects = [];
$resultRows = [];

if ($selectedClass) {
    $subjectStatement = $pdo->prepare(
        'SELECT
            examination_subjects.id,
            examination_subjects.subject_id,
            examination_subjects.maximum_mark,
            examination_subjects.pass_mark,
            examination_subjects.weight_percentage,

            subjects.subject_name,
            subjects.subject_code

         FROM examination_subjects

         INNER JOIN subjects
            ON subjects.id =
                examination_subjects.subject_id

         WHERE examination_subjects.examination_class_id =
            :examination_class_id
           AND examination_subjects.status = "Active"

         ORDER BY subjects.subject_name'
    );

    $subjectStatement->execute([
        'examination_class_id' =>
            $examinationClassId,
    ]);

    $subjects = $subjectStatement->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | Retrieve pupils in class and stream
    |--------------------------------------------------------------------------
    */

    $pupilSql = '
        SELECT
            pupils.id,
            pupils.admission_number,
            pupils.first_name,
            pupils.middle_name,
            pupils.last_name,
            pupils.gender
        FROM pupils
        WHERE pupils.class_id = :class_id
          AND pupils.pupil_status = "Active"
    ';

    $pupilParameters = [
        'class_id' => $selectedClass['class_id'],
    ];

    if (!empty($selectedClass['stream_id'])) {
        $pupilSql .= '
            AND pupils.stream_id = :stream_id
        ';

        $pupilParameters['stream_id'] =
            $selectedClass['stream_id'];
    }

    $pupilSql .= '
        ORDER BY
            pupils.first_name,
            pupils.middle_name,
            pupils.last_name
    ';

    $pupilStatement = $pdo->prepare($pupilSql);
    $pupilStatement->execute($pupilParameters);

    $pupils = $pupilStatement->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | Retrieve every stored mark for this examination class
    |--------------------------------------------------------------------------
    */

    $markStatement = $pdo->prepare(
        'SELECT
            pupil_marks.pupil_id,
            pupil_marks.examination_subject_id,
            pupil_marks.mark_obtained,
            pupil_marks.grade,
            pupil_marks.points,
            pupil_marks.teacher_remark,
            pupil_marks.is_absent

         FROM pupil_marks

         INNER JOIN examination_subjects
            ON examination_subjects.id =
                pupil_marks.examination_subject_id

         WHERE examination_subjects.examination_class_id =
            :examination_class_id'
    );

    $markStatement->execute([
        'examination_class_id' =>
            $examinationClassId,
    ]);

    $markRecords = $markStatement->fetchAll();

    $marksByPupil = [];

    foreach ($markRecords as $mark) {
        $marksByPupil[(int) $mark['pupil_id']]
            [(int) $mark['examination_subject_id']] = $mark;
    }

    /*
    |--------------------------------------------------------------------------
    | Build pupil result rows
    |--------------------------------------------------------------------------
    */

    foreach ($pupils as $pupil) {
        $pupilId = (int) $pupil['id'];

        $subjectResults = [];
        $totalMarkObtained = 0.0;
        $totalMaximumMark = 0.0;
        $totalPoints = 0.0;
        $subjectsWithMarks = 0;
        $absentSubjects = 0;

        foreach ($subjects as $subject) {
            $subjectId = (int) $subject['id'];

            $mark =
                $marksByPupil[$pupilId][$subjectId]
                ?? null;

            $maximumMark =
                (float) $subject['maximum_mark'];

            $totalMaximumMark += $maximumMark;

            if (!$mark) {
                $subjectResults[$subjectId] = [
                    'mark' => null,
                    'grade' => null,
                    'points' => null,
                    'is_absent' => false,
                ];

                continue;
            }

            $isAbsent =
                (int) $mark['is_absent'] === 1;

            if ($isAbsent) {
                $absentSubjects++;

                $subjectResults[$subjectId] = [
                    'mark' => null,
                    'grade' => 'ABS',
                    'points' => null,
                    'is_absent' => true,
                ];

                continue;
            }

            if ($mark['mark_obtained'] !== null) {
                $markObtained =
                    (float) $mark['mark_obtained'];

                $totalMarkObtained += $markObtained;
                $subjectsWithMarks++;

                if ($mark['points'] !== null) {
                    $totalPoints +=
                        (float) $mark['points'];
                }

                $subjectResults[$subjectId] = [
                    'mark' => $markObtained,
                    'grade' => $mark['grade'],
                    'points' => $mark['points'],
                    'is_absent' => false,
                ];
            }
        }

        $percentage =
            $totalMaximumMark > 0
                ? (
                    $totalMarkObtained /
                    $totalMaximumMark
                ) * 100
                : 0;

        $resultRows[] = [
            'pupil' => $pupil,
            'subjects' => $subjectResults,
            'total_mark' => $totalMarkObtained,
            'total_maximum' => $totalMaximumMark,
            'percentage' => $percentage,
            'average' =>
                count($subjects) > 0
                    ? $percentage
                    : 0,
            'total_points' => $totalPoints,
            'subjects_with_marks' =>
                $subjectsWithMarks,
            'absent_subjects' =>
                $absentSubjects,
            'position' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sort by total percentage
    |--------------------------------------------------------------------------
    */

    usort(
        $resultRows,
        static function (
            array $first,
            array $second
        ): int {
            $percentageComparison =
                $second['percentage']
                <=> $first['percentage'];

            if ($percentageComparison !== 0) {
                return $percentageComparison;
            }

            return $second['total_mark']
                <=> $first['total_mark'];
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Assign positions with tied positions
    |--------------------------------------------------------------------------
    */

    $previousPercentage = null;
    $previousTotal = null;
    $currentPosition = 0;

    foreach ($resultRows as $index => &$row) {
        if (
            $previousPercentage === null ||
            round(
                (float) $row['percentage'],
                4
            ) !== round(
                (float) $previousPercentage,
                4
            ) ||
            round(
                (float) $row['total_mark'],
                4
            ) !== round(
                (float) $previousTotal,
                4
            )
        ) {
            $currentPosition = $index + 1;
        }

        $row['position'] = $currentPosition;

        $previousPercentage =
            $row['percentage'];

        $previousTotal =
            $row['total_mark'];
    }

    unset($row);
}

/*
|--------------------------------------------------------------------------
| Class statistics
|--------------------------------------------------------------------------
*/

$classAverage = 0.0;
$highestPercentage = 0.0;
$lowestPercentage = 0.0;
$passedPupils = 0;

if ($resultRows !== []) {
    $percentages = array_column(
        $resultRows,
        'percentage'
    );

    $classAverage =
        array_sum($percentages) /
        count($percentages);

    $highestPercentage =
        max($percentages);

    $lowestPercentage =
        min($percentages);

    foreach ($resultRows as $row) {
        if ((float) $row['percentage'] >= 50) {
            $passedPupils++;
        }
    }
}

function resultPerformanceClass(
    float $percentage
): string {
    if ($percentage >= 80) {
        return 'result-performance-excellent';
    }

    if ($percentage >= 65) {
        return 'result-performance-good';
    }

    if ($percentage >= 50) {
        return 'result-performance-pass';
    }

    return 'result-performance-fail';
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
        Examination Results | Royal Kindergarten and Primary School
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
                    EXAMINATION ANALYTICS
                </span>

                <h2>Class Results</h2>

                <p>
                    <?= htmlspecialchars(
                        $examination['examination_name']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $examination['year_name']
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $examination['term_name']
                    ) ?>
                </p>
            </div>

            <div class="profile-heading-actions">

                <a
                    href="view.php?id=<?= (int) $examinationId ?>"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Back
                </a>

                <?php if ($selectedClass): ?>

                    <a
                        href="print-results.php?<?= htmlspecialchars(
                            http_build_query([
                                'examination_id' =>
                                    $examinationId,
                                'examination_class_id' =>
                                    $examinationClassId,
                            ])
                        ) ?>"
                        target="_blank"
                        class="btn school-primary-btn module-action-button"
                    >
                        <i class="bi bi-printer-fill me-2"></i>
                        Print Mark Sheet
                    </a>
                    <a
    href="subject-analysis.php?examination_id=<?= (int) $examinationId ?>"
    class="btn btn-outline-primary"
>
    <i class="bi bi-pie-chart-fill me-2"></i>
    Subject Analysis
</a>

                <?php endif; ?>

            </div>

        </section>

        <?php if ($examinationClasses === []): ?>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                No classes have been assigned to this examination.
            </div>

        <?php else: ?>

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-building-fill"></i>
                    </span>

                    <div>
                        <h3>Select Examination Class</h3>
                        <p>Choose the class result sheet to display</p>
                    </div>

                </div>

                <form
                    method="GET"
                    action=""
                    class="row g-4"
                >

                    <input
                        type="hidden"
                        name="examination_id"
                        value="<?= (int) $examinationId ?>"
                    >

                    <div class="col-md-9">

                        <select
                            name="examination_class_id"
                            class="form-select professional-input"
                            required
                        >
                            <?php foreach (
                                $examinationClasses as $examClass
                            ): ?>

                                <option
                                    value="<?= (int) $examClass['id'] ?>"
                                    <?= (int) $examinationClassId ===
                                        (int) $examClass['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $examClass['class_name']
                                    ) ?>

                                    <?= !empty(
                                        $examClass['stream_name']
                                    )
                                        ? ' – ' .
                                            htmlspecialchars(
                                                $examClass[
                                                    'stream_name'
                                                ]
                                            )
                                        : ' – All Streams' ?>
                                </option>

                            <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="col-md-3">

                        <button
                            type="submit"
                            class="btn school-primary-btn w-100"
                        >
                            Load Results
                        </button>

                    </div>

                </form>

            </section>

        <?php endif; ?>

        <?php if ($selectedClass): ?>

            <section class="examination-class-banner">

                <div>
                    <span>Class Result Sheet</span>

                    <h3>
                        <?= htmlspecialchars(
                            $selectedClass['class_name']
                        ) ?>

                        <?= !empty($selectedClass['stream_name'])
                            ? ' – ' .
                                htmlspecialchars(
                                    $selectedClass['stream_name']
                                )
                            : '' ?>
                    </h3>

                    <p>
                        <?= number_format(count($subjects)) ?>
                        subjects and
                        <?= number_format(count($resultRows)) ?>
                        active pupils.
                    </p>
                </div>

            </section>

            <section class="row g-4 mb-4">

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-present">
                            <i class="bi bi-trophy-fill"></i>
                        </span>

                        <div>
                            <small>Highest Score</small>

                            <strong>
                                <?= number_format(
                                    $highestPercentage,
                                    1
                                ) ?>%
                            </strong>
                        </div>

                    </article>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-excused">
                            <i class="bi bi-graph-up-arrow"></i>
                        </span>

                        <div>
                            <small>Class Average</small>

                            <strong>
                                <?= number_format(
                                    $classAverage,
                                    1
                                ) ?>%
                            </strong>
                        </div>

                    </article>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-late">
                            <i class="bi bi-person-check-fill"></i>
                        </span>

                        <div>
                            <small>Pupils Passed</small>

                            <strong>
                                <?= number_format(
                                    $passedPupils
                                ) ?>
                            </strong>
                        </div>

                    </article>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <article class="attendance-summary-card">

                        <span class="attendance-summary-icon attendance-absent">
                            <i class="bi bi-graph-down-arrow"></i>
                        </span>

                        <div>
                            <small>Lowest Score</small>

                            <strong>
                                <?= number_format(
                                    $lowestPercentage,
                                    1
                                ) ?>%
                            </strong>
                        </div>

                    </article>
                </div>

            </section>

            <section class="dashboard-card">

                <div class="dashboard-card-header">

                    <div>
                        <h3>Class Mark Sheet</h3>

                        <p>
                            Subject marks, totals, averages and positions
                        </p>
                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table dashboard-table examination-results-table">

                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Admission Number</th>
                                <th>Pupil Name</th>

                                <?php foreach ($subjects as $subject): ?>
                                    <th
                                        title="<?= htmlspecialchars(
                                            $subject['subject_name']
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $subject['subject_code']
                                        ) ?>
                                    </th>
                                <?php endforeach; ?>

                                <th>Total</th>
                                <th>Average</th>
                                <th>Points</th>
                                <th>Performance</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($resultRows === []): ?>

                            <tr>
                                <td
                                    colspan="<?= count($subjects) + 8 ?>"
                                >
                                    <div class="empty-state">
                                        <i class="bi bi-bar-chart"></i>

                                        <strong>
                                            No pupils found
                                        </strong>

                                        <span>
                                            No active pupils are assigned
                                            to this class or stream.
                                        </span>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($resultRows as $row): ?>

                                <?php
                                $pupil = $row['pupil'];

                                $fullName = trim(
                                    $pupil['first_name'] . ' ' .
                                    ($pupil['middle_name'] ?? '') . ' ' .
                                    $pupil['last_name']
                                );

                                $percentage =
                                    (float) $row['percentage'];
                                ?>

                                <tr>

                                    <td>
                                        <span class="result-position-badge">
                                            <?= (int) $row['position'] ?>
                                        </span>
                                    </td>

                                    <td class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $pupil[
                                                'admission_number'
                                            ]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($fullName) ?>
                                    </td>

                                    <?php foreach (
                                        $subjects as $subject
                                    ): ?>

                                        <?php
                                        $subjectResult =
                                            $row['subjects'][
                                                (int) $subject['id']
                                            ] ?? null;
                                        ?>

                                        <td>
                                            <?php if (
                                                !$subjectResult ||
                                                (
                                                    $subjectResult['mark']
                                                    === null &&
                                                    !$subjectResult[
                                                        'is_absent'
                                                    ]
                                                )
                                            ): ?>

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            <?php elseif (
                                                $subjectResult[
                                                    'is_absent'
                                                ]
                                            ): ?>

                                                <span class="result-absent-mark">
                                                    ABS
                                                </span>

                                            <?php else: ?>

                                                <strong>
                                                    <?= number_format(
                                                        (float) $subjectResult[
                                                            'mark'
                                                        ],
                                                        1
                                                    ) ?>
                                                </strong>

                                                <small class="d-block text-muted">
                                                    <?= htmlspecialchars(
                                                        $subjectResult[
                                                            'grade'
                                                        ]
                                                        ?: '—'
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>
                                        </td>

                                    <?php endforeach; ?>

                                    <td class="fw-bold">
                                        <?= number_format(
                                            (float) $row['total_mark'],
                                            1
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $percentage,
                                            1
                                        ) ?>%
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $row['total_points'],
                                            1
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="result-performance-badge
                                            <?= resultPerformanceClass(
                                                $percentage
                                            ) ?>"
                                        >
                                            <?= $percentage >= 80
                                                ? 'Excellent'
                                                : (
                                                    $percentage >= 65
                                                        ? 'Good'
                                                        : (
                                                            $percentage >= 50
                                                                ? 'Pass'
                                                                : 'Needs Improvement'
                                                        )
                                                ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            href="pupil-result.php?<?= htmlspecialchars(
                                                http_build_query([
                                                    'examination_id' =>
                                                        $examinationId,
                                                    'examination_class_id' =>
                                                        $examinationClassId,
                                                    'pupil_id' =>
                                                        $pupil['id'],
                                                ])
                                            ) ?>"
                                            class="table-action-button"
                                            title="View pupil result"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php endif; ?>

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