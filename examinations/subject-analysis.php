<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Validate examination
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

$examinationSubjectId = filter_input(
    INPUT_GET,
    'examination_subject_id',
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

$examinationStatement = $pdo->prepare(
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

$examinationStatement->execute([
    'id' => $examinationId,
]);

$examination = $examinationStatement->fetch();

if (!$examination) {
    $_SESSION['error_message'] =
        'The selected examination was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve examination classes
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

if (
    !$examinationClassId &&
    $examinationClasses !== []
) {
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

if (
    $examinationClassId &&
    !$selectedClass
) {
    $_SESSION['error_message'] =
        'The selected class does not belong to this examination.';

    header(
        'Location: subject-analysis.php?' .
        http_build_query([
            'examination_id' => $examinationId,
        ])
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve subjects assigned to selected class
|--------------------------------------------------------------------------
*/

$subjects = [];

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
}

/*
|--------------------------------------------------------------------------
| Automatically select first subject
|--------------------------------------------------------------------------
*/

if (
    !$examinationSubjectId &&
    $subjects !== []
) {
    $examinationSubjectId =
        (int) $subjects[0]['id'];
}

/*
|--------------------------------------------------------------------------
| Confirm selected subject
|--------------------------------------------------------------------------
*/

$selectedSubject = null;

foreach ($subjects as $subject) {
    if (
        (int) $subject['id'] ===
        (int) $examinationSubjectId
    ) {
        $selectedSubject = $subject;
        break;
    }
}

if (
    $examinationSubjectId &&
    !$selectedSubject
) {
    $_SESSION['error_message'] =
        'The selected subject does not belong to this examination class.';

    header(
        'Location: subject-analysis.php?' .
        http_build_query([
            'examination_id' =>
                $examinationId,
            'examination_class_id' =>
                $examinationClassId,
        ])
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve pupils and marks
|--------------------------------------------------------------------------
*/

$rankedResults = [];
$gradeDistribution = [];
$totalActivePupils = 0;
$totalMarked = 0;
$totalAbsent = 0;
$totalPassed = 0;
$totalFailed = 0;
$highestMark = 0.0;
$lowestMark = 0.0;
$averageMark = 0.0;
$passRate = 0.0;
$failureRate = 0.0;

if (
    $selectedClass &&
    $selectedSubject
) {
    $resultSql = '
        SELECT
            pupils.id AS pupil_id,
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
            $selectedClass['class_id'],
    ];

    if (!empty($selectedClass['stream_id'])) {
        $resultSql .= '
            AND pupils.stream_id = :stream_id
        ';

        $resultParameters['stream_id'] =
            $selectedClass['stream_id'];
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

    $subjectPupilResults =
        $resultStatement->fetchAll();

    $totalActivePupils =
        count($subjectPupilResults);

    $validMarks = [];

    foreach (
        $subjectPupilResults as $result
    ) {
        $isAbsent =
            (int) ($result['is_absent'] ?? 0) === 1;

        $result['is_absent'] = $isAbsent;
        $result['position'] = null;

        if ($isAbsent) {
            $totalAbsent++;

            $rankedResults[] = $result;
            continue;
        }

        if ($result['mark_obtained'] === null) {
            $rankedResults[] = $result;
            continue;
        }

        $mark =
            (float) $result['mark_obtained'];

        $result['mark_obtained'] = $mark;

        $validMarks[] = $mark;
        $totalMarked++;

        if (
            $mark >=
            (float) $selectedSubject['pass_mark']
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

        if (!isset($gradeDistribution[$grade])) {
            $gradeDistribution[$grade] = 0;
        }

        $gradeDistribution[$grade]++;

        $rankedResults[] = $result;
    }

    if ($validMarks !== []) {
        $highestMark = max($validMarks);
        $lowestMark = min($validMarks);

        $averageMark =
            array_sum($validMarks) /
            count($validMarks);
    }

    if ($totalMarked > 0) {
        $passRate =
            ($totalPassed / $totalMarked) * 100;

        $failureRate =
            ($totalFailed / $totalMarked) * 100;
    }

    /*
    |--------------------------------------------------------------------------
    | Sort pupils by subject mark
    |--------------------------------------------------------------------------
    */

    usort(
        $rankedResults,
        static function (
            array $first,
            array $second
        ): int {
            $firstAbsent =
                (bool) $first['is_absent'];

            $secondAbsent =
                (bool) $second['is_absent'];

            $firstHasMark =
                $first['mark_obtained'] !== null;

            $secondHasMark =
                $second['mark_obtained'] !== null;

            if (
                $firstHasMark &&
                !$secondHasMark
            ) {
                return -1;
            }

            if (
                !$firstHasMark &&
                $secondHasMark
            ) {
                return 1;
            }

            if (
                $firstAbsent &&
                !$secondAbsent
            ) {
                return 1;
            }

            if (
                !$firstAbsent &&
                $secondAbsent
            ) {
                return -1;
            }

            if (
                !$firstHasMark &&
                !$secondHasMark
            ) {
                return strcmp(
                    (string) $first['first_name'],
                    (string) $second['first_name']
                );
            }

            return
                (float) $second['mark_obtained']
                <=>
                (float) $first['mark_obtained'];
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Assign tied positions
    |--------------------------------------------------------------------------
    */

    $positionCounter = 0;
    $currentPosition = 0;
    $previousMark = null;

    foreach ($rankedResults as &$result) {
        if (
            $result['mark_obtained'] === null ||
            $result['is_absent']
        ) {
            $result['position'] = null;
            continue;
        }

        $positionCounter++;

        $currentMark =
            (float) $result['mark_obtained'];

        if (
            $previousMark === null ||
            round($currentMark, 4) !==
            round($previousMark, 4)
        ) {
            $currentPosition =
                $positionCounter;
        }

        $result['position'] =
            $currentPosition;

        $previousMark =
            $currentMark;
    }

    unset($result);

    /*
    |--------------------------------------------------------------------------
    | Sort grade distribution
    |--------------------------------------------------------------------------
    */

    uksort(
        $gradeDistribution,
        static function (
            string $first,
            string $second
        ): int {
            preg_match(
                '/(\d+)/',
                $first,
                $firstMatch
            );

            preg_match(
                '/(\d+)/',
                $second,
                $secondMatch
            );

            $firstNumber =
                isset($firstMatch[1])
                    ? (int) $firstMatch[1]
                    : 999;

            $secondNumber =
                isset($secondMatch[1])
                    ? (int) $secondMatch[1]
                    : 999;

            return $firstNumber
                <=> $secondNumber;
        }
    );
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function subjectMarkClass(
    ?float $mark,
    float $passMark
): string {
    if ($mark === null) {
        return 'subject-mark-empty';
    }

    if ($mark >= 80) {
        return 'subject-mark-excellent';
    }

    if ($mark >= $passMark) {
        return 'subject-mark-pass';
    }

    return 'subject-mark-fail';
}

function subjectPerformanceDescription(
    float $average
): string {
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

$performanceDescription =
    subjectPerformanceDescription(
        $averageMark
    );
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
        Subject Analysis | Royal Kindergarten and Primary School
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

                <h2>Subject Analysis</h2>

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

                <?php if (
                    $selectedClass &&
                    $selectedSubject
                ): ?>

                    <a
                        href="print-subject-analysis.php?<?= htmlspecialchars(
                            http_build_query([
                                'examination_id' =>
                                    $examinationId,
                                'examination_class_id' =>
                                    $examinationClassId,
                                'examination_subject_id' =>
                                    $examinationSubjectId,
                            ])
                        ) ?>"
                        target="_blank"
                        class="btn school-primary-btn module-action-button"
                    >
                        <i class="bi bi-printer-fill me-2"></i>
                        Print Analysis
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
                        <i class="bi bi-funnel-fill"></i>
                    </span>

                    <div>
                        <h3>Select Class and Subject</h3>

                        <p>
                            Choose the examination class and subject to analyse
                        </p>
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

                    <div class="col-md-5">

                        <label class="form-label">
                            Examination Class
                        </label>

                        <select
                            name="examination_class_id"
                            id="analysisClass"
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

                    <div class="col-md-5">

                        <label class="form-label">
                            Subject
                        </label>

                        <select
                            name="examination_subject_id"
                            class="form-select professional-input"
                            required
                        >

                            <?php if ($subjects === []): ?>

                                <option value="">
                                    No subjects assigned
                                </option>

                            <?php else: ?>

                                <?php foreach (
                                    $subjects as $subject
                                ): ?>

                                    <option
                                        value="<?= (int) $subject['id'] ?>"
                                        <?= (int) $examinationSubjectId ===
                                            (int) $subject['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $subject['subject_name']
                                        ) ?>

                                        (
                                        <?= htmlspecialchars(
                                            $subject['subject_code']
                                        ) ?>
                                        )
                                    </option>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </select>

                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn school-primary-btn w-100"
                        >
                            Analyse
                        </button>

                    </div>

                </form>

            </section>

        <?php endif; ?>

        <?php if (
            $selectedClass &&
            $selectedSubject
        ): ?>

            <section class="subject-analysis-banner">

                <div>

                    <span>Subject Performance Report</span>

                    <h3>
                        <?= htmlspecialchars(
                            $selectedSubject['subject_name']
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $selectedClass['class_name']
                        ) ?>

                        <?= !empty(
                            $selectedClass['stream_name']
                        )
                            ? ' – ' .
                                htmlspecialchars(
                                    $selectedClass[
                                        'stream_name'
                                    ]
                                )
                            : '' ?>

                        · Maximum mark:
                        <?= number_format(
                            (float) $selectedSubject[
                                'maximum_mark'
                            ],
                            0
                        ) ?>

                        · Pass mark:
                        <?= number_format(
                            (float) $selectedSubject[
                                'pass_mark'
                            ],
                            0
                        ) ?>
                    </p>

                </div>

                <span class="subject-performance-label">
                    <?= htmlspecialchars(
                        $performanceDescription
                    ) ?>
                </span>

            </section>

            <section class="row g-4 mb-4">

                <div class="col-sm-6 col-xl-3">

                    <article class="subject-analysis-card">

                        <span class="subject-analysis-icon">
                            <i class="bi bi-trophy-fill"></i>
                        </span>

                        <div>
                            <small>Highest Mark</small>

                            <strong>
                                <?= $totalMarked > 0
                                    ? number_format(
                                        $highestMark,
                                        1
                                    )
                                    : '—' ?>
                            </strong>
                        </div>

                    </article>

                </div>

                <div class="col-sm-6 col-xl-3">

                    <article class="subject-analysis-card">

                        <span class="subject-analysis-icon">
                            <i class="bi bi-graph-down-arrow"></i>
                        </span>

                        <div>
                            <small>Lowest Mark</small>

                            <strong>
                                <?= $totalMarked > 0
                                    ? number_format(
                                        $lowestMark,
                                        1
                                    )
                                    : '—' ?>
                            </strong>
                        </div>

                    </article>

                </div>

                <div class="col-sm-6 col-xl-3">

                    <article class="subject-analysis-card">

                        <span class="subject-analysis-icon">
                            <i class="bi bi-bar-chart-fill"></i>
                        </span>

                        <div>
                            <small>Subject Average</small>

                            <strong>
                                <?= number_format(
                                    $averageMark,
                                    1
                                ) ?>
                            </strong>
                        </div>

                    </article>

                </div>

                <div class="col-sm-6 col-xl-3">

                    <article class="subject-analysis-card">

                        <span class="subject-analysis-icon">
                            <i class="bi bi-percent"></i>
                        </span>

                        <div>
                            <small>Pass Rate</small>

                            <strong>
                                <?= number_format(
                                    $passRate,
                                    1
                                ) ?>%
                            </strong>
                        </div>

                    </article>

                </div>

            </section>

            <section class="row g-4 mb-4">

                <div class="col-xl-5">

                    <article class="dashboard-card h-100">

                        <div class="dashboard-card-header">

                            <div>
                                <h3>Grade Distribution</h3>

                                <p>
                                    Number of pupils in each grade
                                </p>
                            </div>

                        </div>

                        <div class="subject-grade-list">

                            <?php if (
                                $gradeDistribution === []
                            ): ?>

                                <div class="empty-state">
                                    <i class="bi bi-bar-chart"></i>

                                    <strong>
                                        No grades available
                                    </strong>

                                    <span>
                                        Enter or calculate marks first.
                                    </span>
                                </div>

                            <?php else: ?>

                                <?php foreach (
                                    $gradeDistribution as
                                    $grade => $gradeCount
                                ): ?>

                                    <?php
                                    $gradePercentage =
                                        $totalMarked > 0
                                            ? (
                                                $gradeCount /
                                                $totalMarked
                                            ) * 100
                                            : 0;
                                    ?>

                                    <div class="subject-grade-item">

                                        <div class="subject-grade-label">

                                            <span>
                                                <?= htmlspecialchars(
                                                    $grade
                                                ) ?>
                                            </span>

                                            <strong>
                                                <?= number_format(
                                                    $gradeCount
                                                ) ?>
                                            </strong>

                                        </div>

                                        <div class="subject-grade-progress">

                                            <span
                                                style="width:
                                                <?= number_format(
                                                    $gradePercentage,
                                                    2,
                                                    '.',
                                                    ''
                                                ) ?>%"
                                            ></span>

                                        </div>

                                        <small>
                                            <?= number_format(
                                                $gradePercentage,
                                                1
                                            ) ?>%
                                        </small>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    </article>

                </div>

                <div class="col-xl-7">

                    <article class="dashboard-card h-100">

                        <div class="dashboard-card-header">

                            <div>
                                <h3>Analysis Summary</h3>

                                <p>
                                    Participation and pass statistics
                                </p>
                            </div>

                        </div>

                        <div class="subject-statistics-grid">

                            <div>
                                <small>Active Pupils</small>

                                <strong>
                                    <?= number_format(
                                        $totalActivePupils
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <small>Marks Entered</small>

                                <strong>
                                    <?= number_format(
                                        $totalMarked
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <small>Pupils Passed</small>

                                <strong>
                                    <?= number_format(
                                        $totalPassed
                                    ) ?>
                                </strong>
                            </div>

                            <div>
                                <small>Pupils Failed</small>

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

                            <div>
                                <small>Failure Rate</small>

                                <strong>
                                    <?= number_format(
                                        $failureRate,
                                        1
                                    ) ?>%
                                </strong>
                            </div>

                        </div>

                    </article>

                </div>

            </section>

            <section class="dashboard-card">

                <div class="dashboard-card-header">

                    <div>
                        <h3>Pupil Subject Ranking</h3>

                        <p>
                            Pupil performance ordered from highest to lowest
                        </p>
                    </div>

                    <span class="status-badge status-active">
                        <?= number_format(
                            $totalMarked
                        ) ?>
                        marked
                    </span>

                </div>

                <div class="table-responsive">

                    <table class="table dashboard-table align-middle">

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
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (
                            $rankedResults === []
                        ): ?>

                            <tr>
                                <td colspan="9">

                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>

                                        <strong>
                                            No pupils found
                                        </strong>

                                        <span>
                                            No active pupils belong to this class.
                                        </span>
                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $rankedResults as $result
                            ): ?>

                                <?php
                                $fullName = trim(
                                    $result['first_name'] . ' ' .
                                    ($result['middle_name'] ?? '') . ' ' .
                                    $result['last_name']
                                );

                                $mark =
                                    $result['mark_obtained']
                                    !== null
                                        ? (float) $result[
                                            'mark_obtained'
                                        ]
                                        : null;
                                ?>

                                <tr>

                                    <td>

                                        <?php if (
                                            $result['position'] !== null
                                        ): ?>

                                            <span class="result-position-badge">
                                                <?= (int) $result[
                                                    'position'
                                                ] ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                —
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td class="fw-semibold">
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

                                        <?php if (
                                            $result['is_absent']
                                        ): ?>

                                            <span class="result-absent-mark">
                                                ABS
                                            </span>

                                        <?php elseif (
                                            $mark !== null
                                        ): ?>

                                            <span
                                                class="subject-analysis-mark
                                                <?= subjectMarkClass(
                                                    $mark,
                                                    (float) $selectedSubject[
                                                        'pass_mark'
                                                    ]
                                                ) ?>"
                                            >
                                                <?= number_format(
                                                    $mark,
                                                    1
                                                ) ?>
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

                                    <td>

                                        <?php if (
                                            $result['is_absent']
                                        ): ?>

                                            <span class="status-badge status-inactive">
                                                Absent
                                            </span>

                                        <?php elseif (
                                            $mark === null
                                        ): ?>

                                            <span class="status-badge status-pending">
                                                Not Marked
                                            </span>

                                        <?php elseif (
                                            $mark >=
                                            (float) $selectedSubject[
                                                'pass_mark'
                                            ]
                                        ): ?>

                                            <span class="status-badge status-active">
                                                Passed
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge status-inactive">
                                                Failed
                                            </span>

                                        <?php endif; ?>

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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const classSelect =
        document.getElementById('analysisClass');

    if (!classSelect) {
        return;
    }

    classSelect.addEventListener('change', () => {
        const examinationId =
            <?= json_encode($examinationId) ?>;

        const classId =
            classSelect.value;

        const query =
            new URLSearchParams({
                examination_id: examinationId,
                examination_class_id: classId
            });

        window.location.href =
            'subject-analysis.php?' +
            query.toString();
    });
});
</script>

</body>
</html>