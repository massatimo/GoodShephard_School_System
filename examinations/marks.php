<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$examinationSubjectId = filter_input(
    INPUT_GET,
    'examination_subject_id',
    FILTER_VALIDATE_INT
);

$examinationClassId = filter_input(
    INPUT_GET,
    'examination_class_id',
    FILTER_VALIDATE_INT
);

/*
|--------------------------------------------------------------------------
| Load examination class when only class ID is supplied
|--------------------------------------------------------------------------
*/

if (!$examinationSubjectId && $examinationClassId) {
    $classSubjectStatement = $pdo->prepare(
        'SELECT id
         FROM examination_subjects
         WHERE examination_class_id =
            :examination_class_id
           AND status = "Active"
         ORDER BY id
         LIMIT 1'
    );

    $classSubjectStatement->execute([
        'examination_class_id' =>
            $examinationClassId,
    ]);

    $firstSubjectId =
        $classSubjectStatement->fetchColumn();

    if ($firstSubjectId) {
        $examinationSubjectId =
            (int) $firstSubjectId;
    }
}

if (!$examinationSubjectId) {
    $_SESSION['error_message'] =
        'No examination subject has been selected.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve selected examination subject
|--------------------------------------------------------------------------
*/

$subjectStatement = $pdo->prepare(
    'SELECT
        examination_subjects.id,
        examination_subjects.examination_class_id,
        examination_subjects.subject_id,
        examination_subjects.maximum_mark,
        examination_subjects.pass_mark,
        examination_subjects.weight_percentage,
        examination_subjects.status,

        subjects.subject_name,
        subjects.subject_code,

        examination_classes.class_id,
        examination_classes.stream_id,

        classes.class_name,
        streams.stream_name,

        examinations.id AS examination_id,
        examinations.examination_name,
        examinations.status AS examination_status,

        academic_years.year_name,
        terms.term_name

     FROM examination_subjects

     INNER JOIN subjects
        ON subjects.id =
            examination_subjects.subject_id

     INNER JOIN examination_classes
        ON examination_classes.id =
            examination_subjects.examination_class_id

     INNER JOIN classes
        ON classes.id =
            examination_classes.class_id

     LEFT JOIN streams
        ON streams.id =
            examination_classes.stream_id

     INNER JOIN examinations
        ON examinations.id =
            examination_classes.examination_id

     INNER JOIN academic_years
        ON academic_years.id =
            examinations.academic_year_id

     INNER JOIN terms
        ON terms.id =
            examinations.term_id

     WHERE examination_subjects.id = :id
     LIMIT 1'
);

$subjectStatement->execute([
    'id' => $examinationSubjectId,
]);

$examinationSubject = $subjectStatement->fetch();

if (!$examinationSubject) {
    $_SESSION['error_message'] =
        'The examination subject was not found.';

    header('Location: index.php');
    exit;
}

$examinationClassId =
    (int) $examinationSubject[
        'examination_class_id'
    ];

/*
|--------------------------------------------------------------------------
| Retrieve every subject assigned to the class
|--------------------------------------------------------------------------
*/

$classSubjectsStatement = $pdo->prepare(
    'SELECT
        examination_subjects.id,
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

$classSubjectsStatement->execute([
    'examination_class_id' =>
        $examinationClassId,
]);

$classSubjects =
    $classSubjectsStatement->fetchAll();

/*
|--------------------------------------------------------------------------
| Retrieve pupils and previously entered marks
|--------------------------------------------------------------------------
*/

$pupilSql = '
    SELECT
        pupils.id,
        pupils.admission_number,
        pupils.first_name,
        pupils.middle_name,
        pupils.last_name,
        pupils.gender,

        pupil_marks.id AS pupil_mark_id,
        pupil_marks.mark_obtained,
        pupil_marks.grade,
        pupil_marks.points,
        pupil_marks.teacher_remark,
        pupil_marks.is_absent

    FROM pupils

    LEFT JOIN pupil_marks
        ON pupil_marks.pupil_id = pupils.id
       AND pupil_marks.examination_subject_id =
            :examination_subject_id

    WHERE pupils.class_id = :class_id
      AND pupils.pupil_status = "Active"
';

$pupilParameters = [
    'examination_subject_id' =>
        $examinationSubjectId,
    'class_id' =>
        $examinationSubject['class_id'],
];

if (!empty($examinationSubject['stream_id'])) {
    $pupilSql .= '
        AND pupils.stream_id = :stream_id
    ';

    $pupilParameters['stream_id'] =
        $examinationSubject['stream_id'];
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
| Load grading scale for live display
|--------------------------------------------------------------------------
*/

$gradingScales = $pdo->query(
    "SELECT
        grade_name,
        minimum_mark,
        maximum_mark,
        points,
        remark
     FROM grading_scales
     WHERE status = 'Active'
       AND applicable_level IN ('Primary', 'All')
     ORDER BY minimum_mark DESC"
)->fetchAll();

$errors = $_SESSION['form_errors'] ?? [];

unset($_SESSION['form_errors']);

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';

unset(
    $_SESSION['success_message'],
    $_SESSION['error_message']
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
        Enter Marks | Good Shepherd Primary School
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

        <?php if ($errors !== []): ?>

            <div class="alert alert-danger">

                <strong>
                    Correct the following:
                </strong>

                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

            </div>

        <?php endif; ?>

        <section class="module-heading">

            <div>
                <span class="module-label">
                    MARKS ENTRY
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $examinationSubject[
                            'subject_name'
                        ]
                    ) ?>
                </h2>

                <p>
                    <?= htmlspecialchars(
                        $examinationSubject[
                            'examination_name'
                        ]
                    ) ?>
                    ·
                    <?= htmlspecialchars(
                        $examinationSubject[
                            'class_name'
                        ]
                    ) ?>

                    <?= !empty(
                        $examinationSubject[
                            'stream_name'
                        ]
                    )
                        ? ' – ' .
                            htmlspecialchars(
                                $examinationSubject[
                                    'stream_name'
                                ]
                            )
                        : '' ?>
                </p>
            </div>

            <a
                href="setup-class.php?examination_id=<?= (int) $examinationSubject['examination_id'] ?>&examination_class_id=<?= (int) $examinationClassId ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Class Setup
            </a>

        </section>

        <?php if (
            !in_array(
                $examinationSubject[
                    'examination_status'
                ],
                ['Open', 'Draft'],
                true
            )
        ): ?>

            <div class="alert alert-warning">
                <i class="bi bi-lock-fill me-2"></i>

                This examination is
                <?= htmlspecialchars(
                    strtolower(
                        $examinationSubject[
                            'examination_status'
                        ]
                    )
                ) ?>.

                Marks cannot be changed unless it is reopened.
            </div>

        <?php endif; ?>

        <!-- SUBJECT SELECTOR -->

        <section class="marks-subject-selector">

            <div>

                <span>Class Subjects</span>

                <select
                    id="subjectSwitcher"
                    class="form-select professional-input"
                >

                    <?php foreach ($classSubjects as $subject): ?>

                        <option
                            value="<?= (int) $subject['id'] ?>"
                            <?= (int) $subject['id'] ===
                                (int) $examinationSubjectId
                                ? 'selected'
                                : '' ?>
                        >
                            <?= htmlspecialchars(
                                $subject['subject_name']
                            ) ?>

                            (<?= htmlspecialchars(
                                $subject['subject_code']
                            ) ?>)
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="marks-subject-summary">

                <div>
                    <small>Maximum Mark</small>

                    <strong>
                        <?= number_format(
                            (float) $examinationSubject[
                                'maximum_mark'
                            ],
                            2
                        ) ?>
                    </strong>
                </div>

                <div>
                    <small>Pass Mark</small>

                    <strong>
                        <?= number_format(
                            (float) $examinationSubject[
                                'pass_mark'
                            ],
                            2
                        ) ?>
                    </strong>
                </div>

                <div>
                    <small>Weight</small>

                    <strong>
                        <?= number_format(
                            (float) $examinationSubject[
                                'weight_percentage'
                            ],
                            2
                        ) ?>%
                    </strong>
                </div>

            </div>

        </section>

        <!-- MARKS FORM -->

        <form action="store-marks.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token']
                ) ?>"
            >

            <input
                type="hidden"
                name="examination_subject_id"
                value="<?= (int) $examinationSubjectId ?>"
            >

            <section class="dashboard-card">

                <div class="dashboard-card-header marks-entry-header">

                    <div>
                        <h3>Pupil Marks</h3>

                        <p>
                            <?= number_format(count($pupils)) ?>
                            active pupils loaded
                        </p>
                    </div>

                    <div class="marks-entry-legend">

                        <span>
                            Maximum:
                            <strong>
                                <?= number_format(
                                    (float) $examinationSubject[
                                        'maximum_mark'
                                    ],
                                    2
                                ) ?>
                            </strong>
                        </span>

                        <span>
                            Pass:
                            <strong>
                                <?= number_format(
                                    (float) $examinationSubject[
                                        'pass_mark'
                                    ],
                                    2
                                ) ?>
                            </strong>
                        </span>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table dashboard-table align-middle">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Admission Number</th>
                                <th>Pupil Name</th>
                                <th>Gender</th>
                                <th>Absent</th>
                                <th>Mark</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Points</th>
                                <th>Teacher Remark</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($pupils === []): ?>

                            <tr>
                                <td colspan="10">

                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>

                                        <strong>
                                            No pupils found
                                        </strong>

                                        <span>
                                            Confirm that active pupils
                                            are assigned to this class
                                            and stream.
                                        </span>
                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $pupils as $index => $pupil
                            ): ?>

                                <?php
                                $pupilId = (int) $pupil['id'];

                                $fullName = trim(
                                    $pupil['first_name'] . ' ' .
                                    ($pupil['middle_name'] ?? '') . ' ' .
                                    $pupil['last_name']
                                );

                                $existingMark =
                                    $pupil['mark_obtained'];

                                $isAbsent =
                                    (int) (
                                        $pupil['is_absent']
                                        ?? 0
                                    ) === 1;
                                ?>

                                <tr
                                    class="marks-entry-row"
                                    data-pupil-id="<?= $pupilId ?>"
                                >

                                    <td><?= $index + 1 ?></td>

                                    <td class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $pupil[
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
                                            $pupil['gender']
                                        ) ?>
                                    </td>

                                    <td>

                                        <div class="form-check form-switch">

                                            <input
                                                class="form-check-input absent-checkbox"
                                                type="checkbox"
                                                name="is_absent[<?= $pupilId ?>]"
                                                value="1"
                                                <?= $isAbsent
                                                    ? 'checked'
                                                    : '' ?>
                                            >

                                        </div>

                                    </td>

                                    <td>

                                        <input
                                            type="number"
                                            name="marks[<?= $pupilId ?>]"
                                            min="0"
                                            max="<?= htmlspecialchars(
                                                (string) $examinationSubject[
                                                    'maximum_mark'
                                                ]
                                            ) ?>"
                                            step="0.01"
                                            value="<?= $existingMark !== null
                                                ? htmlspecialchars(
                                                    (string) $existingMark
                                                )
                                                : '' ?>"
                                            class="form-control mark-input"
                                            <?= $isAbsent
                                                ? 'disabled'
                                                : '' ?>
                                        >

                                    </td>

                                    <td>
                                        <span class="calculated-percentage">
                                            —
                                        </span>
                                    </td>

                                    <td>
                                        <span class="calculated-grade">
                                            <?= htmlspecialchars(
                                                $pupil['grade']
                                                ?: '—'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="calculated-points">
                                            <?= $pupil['points'] !== null
                                                ? htmlspecialchars(
                                                    (string) $pupil['points']
                                                )
                                                : '—' ?>
                                        </span>
                                    </td>

                                    <td>

                                        <input
                                            type="text"
                                            name="teacher_remarks[<?= $pupilId ?>]"
                                            value="<?= htmlspecialchars(
                                                (string) (
                                                    $pupil[
                                                        'teacher_remark'
                                                    ] ?? ''
                                                )
                                            ) ?>"
                                            class="form-control marks-remark-input"
                                            placeholder="Optional"
                                        >

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

            <?php if ($pupils !== []): ?>

                <div class="form-submit-bar">

                    <a
                        href="setup-class.php?examination_id=<?= (int) $examinationSubject['examination_id'] ?>&examination_class_id=<?= (int) $examinationClassId ?>"
                        class="btn btn-light"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn school-primary-btn module-action-button"
                        <?= !in_array(
                            $examinationSubject[
                                'examination_status'
                            ],
                            ['Open', 'Draft'],
                            true
                        )
                            ? 'disabled'
                            : '' ?>
                    >
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Save Marks
                    </button>

                </div>

            <?php endif; ?>

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
    const maximumMark =
        <?= json_encode(
            (float) $examinationSubject['maximum_mark']
        ) ?>;

    const gradingScales =
        <?= json_encode($gradingScales) ?>;

    const subjectSwitcher =
        document.getElementById('subjectSwitcher');

    subjectSwitcher?.addEventListener('change', () => {
        window.location.href =
            'marks.php?examination_subject_id=' +
            encodeURIComponent(subjectSwitcher.value);
    });

    function calculateGrade(percentage) {
        for (const scale of gradingScales) {
            const minimum = Number(scale.minimum_mark);
            const maximum = Number(scale.maximum_mark);

            if (
                percentage >= minimum &&
                percentage <= maximum
            ) {
                return {
                    grade: scale.grade_name,
                    points: scale.points,
                };
            }
        }

        return {
            grade: '—',
            points: '—',
        };
    }

    document
        .querySelectorAll('.marks-entry-row')
        .forEach((row) => {
            const markInput =
                row.querySelector('.mark-input');

            const absentCheckbox =
                row.querySelector('.absent-checkbox');

            const percentageOutput =
                row.querySelector(
                    '.calculated-percentage'
                );

            const gradeOutput =
                row.querySelector('.calculated-grade');

            const pointsOutput =
                row.querySelector('.calculated-points');

            function updateCalculation() {
                if (absentCheckbox.checked) {
                    markInput.disabled = true;
                    markInput.value = '';

                    percentageOutput.textContent = 'Absent';
                    gradeOutput.textContent = 'ABS';
                    pointsOutput.textContent = '—';

                    return;
                }

                markInput.disabled = false;

                if (markInput.value === '') {
                    percentageOutput.textContent = '—';
                    gradeOutput.textContent = '—';
                    pointsOutput.textContent = '—';

                    return;
                }

                const mark = Number(markInput.value);

                if (
                    Number.isNaN(mark) ||
                    mark < 0 ||
                    mark > maximumMark
                ) {
                    percentageOutput.textContent = 'Invalid';
                    gradeOutput.textContent = '—';
                    pointsOutput.textContent = '—';

                    return;
                }

                const percentage =
                    maximumMark > 0
                        ? (mark / maximumMark) * 100
                        : 0;

                const gradeResult =
                    calculateGrade(percentage);

                percentageOutput.textContent =
                    percentage.toFixed(1) + '%';

                gradeOutput.textContent =
                    gradeResult.grade;

                pointsOutput.textContent =
                    gradeResult.points ?? '—';
            }

            markInput.addEventListener(
                'input',
                updateCalculation
            );

            absentCheckbox.addEventListener(
                'change',
                updateCalculation
            );

            updateCalculation();
        });
});
</script>

</body>
</html>