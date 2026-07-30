<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        examinations.academic_year_id,
        examinations.term_id,
        academic_years.year_name,
        terms.term_name,
        examination_types.type_name
     FROM examinations
     INNER JOIN academic_years
        ON academic_years.id =
            examinations.academic_year_id
     INNER JOIN terms
        ON terms.id =
            examinations.term_id
     INNER JOIN examination_types
        ON examination_types.id =
            examinations.examination_type_id
     WHERE examinations.id = :id
     LIMIT 1'
);

$examinationStatement->execute([
    'id' => $examinationId,
]);

$examination = $examinationStatement->fetch();

if (!$examination) {
    $_SESSION['error_message'] =
        'The examination was not found.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Load classes, streams and subjects
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

$streams = $pdo->query(
    "SELECT
        id,
        class_id,
        stream_name,
        stream_code
     FROM streams
     WHERE status = 'Active'
     ORDER BY stream_name"
)->fetchAll();

$subjects = $pdo->query(
    "SELECT
        id,
        subject_name,
        subject_code,
        subject_category,
        applicable_level
     FROM subjects
     WHERE status = 'Active'
     ORDER BY subject_name"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Retrieve existing examination class when managing subjects
|--------------------------------------------------------------------------
*/

$selectedExaminationClass = null;
$assignedSubjects = [];

if ($examinationClassId) {
    $selectedClassStatement = $pdo->prepare(
        'SELECT
            examination_classes.*,
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
         WHERE examination_classes.id = :id
           AND examination_classes.examination_id =
                :examination_id
         LIMIT 1'
    );

    $selectedClassStatement->execute([
        'id' => $examinationClassId,
        'examination_id' => $examinationId,
    ]);

    $selectedExaminationClass =
        $selectedClassStatement->fetch();

    if (!$selectedExaminationClass) {
        $_SESSION['error_message'] =
            'The selected examination class was not found.';

        header(
            'Location: view.php?id=' . $examinationId
        );
        exit;
    }

    $assignedSubjectStatement = $pdo->prepare(
        'SELECT
            examination_subjects.id,
            examination_subjects.subject_id,
            examination_subjects.maximum_mark,
            examination_subjects.pass_mark,
            examination_subjects.weight_percentage,
            examination_subjects.status,
            subjects.subject_name,
            subjects.subject_code
         FROM examination_subjects
         INNER JOIN subjects
            ON subjects.id =
                examination_subjects.subject_id
         WHERE examination_subjects.examination_class_id =
            :examination_class_id
         ORDER BY subjects.subject_name'
    );

    $assignedSubjectStatement->execute([
        'examination_class_id' =>
            $examinationClassId,
    ]);

    $assignedSubjects =
        $assignedSubjectStatement->fetchAll();
}

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];

unset(
    $_SESSION['form_errors'],
    $_SESSION['old_input']
);

function setupOld(
    array $old,
    string $field,
    string $default = ''
): string {
    return htmlspecialchars(
        (string) ($old[$field] ?? $default)
    );
}

function setupSelected(
    array $old,
    string $field,
    string|int|null $value
): string {
    return (string) ($old[$field] ?? '')
        === (string) $value
        ? 'selected'
        : '';
}

function setupSubjectChecked(
    array $old,
    int $subjectId
): string {
    $selectedSubjects = $old['subject_ids'] ?? [];

    if (!is_array($selectedSubjects)) {
        return '';
    }

    return in_array(
        (string) $subjectId,
        array_map('strval', $selectedSubjects),
        true
    ) ? 'checked' : '';
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
        Assign Examination Class | Good Shepherd Primary School
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
                    EXAMINATION SETUP
                </span>

                <h2>
                    <?= $selectedExaminationClass
                        ? 'Manage Examination Subjects'
                        : 'Assign Class and Subjects' ?>
                </h2>

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

            <a
                href="view.php?id=<?= (int) $examination['id'] ?>"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-left me-2"></i>
                Back to Examination
            </a>

        </section>

        <?php if ($errors !== []): ?>

            <div class="alert alert-danger">

                <strong>
                    Correct the following information:
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

        <?php if ($examination['status'] === 'Published'): ?>

            <div class="alert alert-warning">
                <i class="bi bi-lock-fill me-2"></i>

                This examination has already been published.
                Its class and subject setup should not be changed.
            </div>

        <?php endif; ?>

        <?php if (!$selectedExaminationClass): ?>

            <form action="store-class.php" method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token']
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="create_class"
                >

                <input
                    type="hidden"
                    name="examination_id"
                    value="<?= (int) $examination['id'] ?>"
                >

                <!-- CLASS AND STREAM -->

                <section class="form-section-card">

                    <div class="form-section-header">

                        <span class="form-section-icon">
                            <i class="bi bi-building-fill"></i>
                        </span>

                        <div>
                            <h3>Class and Stream</h3>

                            <p>
                                Select the class participating
                                in the examination
                            </p>
                        </div>

                    </div>

                    <div class="row g-4">

                        <div class="col-md-6">

                            <label class="form-label">
                                Class *
                            </label>

                            <select
                                name="class_id"
                                id="classId"
                                class="form-select professional-input"
                                required
                            >
                                <option value="">
                                    Select class
                                </option>

                                <?php foreach ($classes as $class): ?>

                                    <option
                                        value="<?= (int) $class['id'] ?>"
                                        <?= setupSelected(
                                            $old,
                                            'class_id',
                                            $class['id']
                                        ) ?>
                                    >
                                        <?= htmlspecialchars(
                                            $class['class_name']
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Stream
                            </label>

                            <select
                                name="stream_id"
                                id="streamId"
                                class="form-select professional-input"
                            >
                                <option value="">
                                    All streams / No stream
                                </option>

                                <?php foreach ($streams as $stream): ?>

                                    <option
                                        value="<?= (int) $stream['id'] ?>"
                                        data-class-id="<?= (int) $stream[
                                            'class_id'
                                        ] ?>"
                                        <?= setupSelected(
                                            $old,
                                            'stream_id',
                                            $stream['id']
                                        ) ?>
                                    >
                                        <?= htmlspecialchars(
                                            $stream['stream_name']
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <small class="text-muted">
                                Select a stream when marks will be
                                entered separately for each stream.
                            </small>

                        </div>

                    </div>

                </section>

                <!-- SUBJECT SELECTION -->

                <section class="form-section-card">

                    <div class="form-section-header">

                        <span class="form-section-icon">
                            <i class="bi bi-book-fill"></i>
                        </span>

                        <div>
                            <h3>Select Subjects</h3>

                            <p>
                                Choose all subjects examined
                                in this class
                            </p>
                        </div>

                    </div>

                    <div class="subject-selection-toolbar">

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success"
                            id="selectAllSubjects"
                        >
                            Select All
                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="clearAllSubjects"
                        >
                            Clear All
                        </button>

                    </div>

                    <div class="exam-subject-selection-grid">

                        <?php foreach ($subjects as $subject): ?>

                            <label class="exam-subject-option">

                                <input
                                    type="checkbox"
                                    name="subject_ids[]"
                                    value="<?= (int) $subject['id'] ?>"
                                    class="exam-subject-checkbox"
                                    <?= setupSubjectChecked(
                                        $old,
                                        (int) $subject['id']
                                    ) ?>
                                >

                                <span class="exam-subject-checkmark">
                                    <i class="bi bi-check-lg"></i>
                                </span>

                                <span class="exam-subject-option-content">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $subject['subject_name']
                                        ) ?>
                                    </strong>

                                    <small>
                                        <?= htmlspecialchars(
                                            $subject['subject_code']
                                        ) ?>
                                        ·
                                        <?= htmlspecialchars(
                                            $subject[
                                                'subject_category'
                                            ]
                                        ) ?>
                                    </small>

                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </section>

                <!-- DEFAULT MARK SETTINGS -->

                <section class="form-section-card">

                    <div class="form-section-header">

                        <span class="form-section-icon">
                            <i class="bi bi-sliders"></i>
                        </span>

                        <div>
                            <h3>Default Mark Settings</h3>

                            <p>
                                These values will apply to every
                                selected subject
                            </p>
                        </div>

                    </div>

                    <div class="row g-4">

                        <div class="col-md-4">

                            <label class="form-label">
                                Maximum Mark *
                            </label>

                            <input
                                type="number"
                                name="maximum_mark"
                                min="1"
                                max="1000"
                                step="0.01"
                                class="form-control professional-input"
                                value="<?= setupOld(
                                    $old,
                                    'maximum_mark',
                                    '100'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Pass Mark *
                            </label>

                            <input
                                type="number"
                                name="pass_mark"
                                min="0"
                                max="1000"
                                step="0.01"
                                class="form-control professional-input"
                                value="<?= setupOld(
                                    $old,
                                    'pass_mark',
                                    '40'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Weight Percentage *
                            </label>

                            <div class="input-group">

                                <input
                                    type="number"
                                    name="weight_percentage"
                                    min="0.01"
                                    max="100"
                                    step="0.01"
                                    class="form-control professional-input"
                                    value="<?= setupOld(
                                        $old,
                                        'weight_percentage',
                                        '100'
                                    ) ?>"
                                    required
                                >

                                <span class="input-group-text">
                                    %
                                </span>

                            </div>

                        </div>

                    </div>

                </section>

                <div class="form-submit-bar">

                    <a
                        href="view.php?id=<?= (int) $examination['id'] ?>"
                        class="btn btn-light"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn school-primary-btn module-action-button"
                        <?= $examination['status'] === 'Published'
                            ? 'disabled'
                            : '' ?>
                    >
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Assign Class and Subjects
                    </button>

                </div>

            </form>

        <?php else: ?>

            <!-- EXISTING CLASS INFORMATION -->

            <section class="examination-class-banner">

                <div>
                    <span>Assigned Examination Class</span>

                    <h3>
                        <?= htmlspecialchars(
                            $selectedExaminationClass[
                                'class_name'
                            ]
                        ) ?>

                        <?= !empty(
                            $selectedExaminationClass[
                                'stream_name'
                            ]
                        )
                            ? ' – ' .
                                htmlspecialchars(
                                    $selectedExaminationClass[
                                        'stream_name'
                                    ]
                                )
                            : '' ?>
                    </h3>

                    <p>
                        Manage the subjects and mark settings
                        for this class.
                    </p>
                </div>

                <a
                    href="marks.php?examination_class_id=<?= (int) $selectedExaminationClass['id'] ?>"
                    class="btn school-primary-btn"
                >
                    <i class="bi bi-pencil-square me-2"></i>
                    Enter Marks
                </a>

            </section>

            <!-- ADD ONE SUBJECT -->

            <section class="form-section-card">

                <div class="form-section-header">

                    <span class="form-section-icon">
                        <i class="bi bi-bookmark-plus-fill"></i>
                    </span>

                    <div>
                        <h3>Add Subject</h3>

                        <p>
                            Add another subject to this examination class
                        </p>
                    </div>

                </div>

                <form
                    action="store-class.php"
                    method="POST"
                    class="row g-4"
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
                        name="action"
                        value="add_subject"
                    >

                    <input
                        type="hidden"
                        name="examination_id"
                        value="<?= (int) $examination['id'] ?>"
                    >

                    <input
                        type="hidden"
                        name="examination_class_id"
                        value="<?= (int) $selectedExaminationClass['id'] ?>"
                    >

                    <div class="col-md-4">

                        <label class="form-label">
                            Subject *
                        </label>

                        <select
                            name="subject_id"
                            class="form-select professional-input"
                            required
                        >
                            <option value="">
                                Select subject
                            </option>

                            <?php foreach ($subjects as $subject): ?>

                                <option
                                    value="<?= (int) $subject['id'] ?>"
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

                    <div class="col-md-2">

                        <label class="form-label">
                            Maximum Mark *
                        </label>

                        <input
                            type="number"
                            name="maximum_mark"
                            min="1"
                            step="0.01"
                            value="100"
                            class="form-control professional-input"
                            required
                        >

                    </div>

                    <div class="col-md-2">

                        <label class="form-label">
                            Pass Mark *
                        </label>

                        <input
                            type="number"
                            name="pass_mark"
                            min="0"
                            step="0.01"
                            value="40"
                            class="form-control professional-input"
                            required
                        >

                    </div>

                    <div class="col-md-2">

                        <label class="form-label">
                            Weight %
                        </label>

                        <input
                            type="number"
                            name="weight_percentage"
                            min="0.01"
                            max="100"
                            step="0.01"
                            value="100"
                            class="form-control professional-input"
                            required
                        >

                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <button
                            type="submit"
                            class="btn school-primary-btn w-100"
                            <?= $examination['status'] === 'Published'
                                ? 'disabled'
                                : '' ?>
                        >
                            Add Subject
                        </button>

                    </div>

                </form>

            </section>

            <!-- ASSIGNED SUBJECTS -->

            <section class="dashboard-card">

                <div class="dashboard-card-header">

                    <div>
                        <h3>Assigned Subjects</h3>

                        <p>
                            <?= number_format(
                                count($assignedSubjects)
                            ) ?>
                            subjects configured
                        </p>
                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table dashboard-table align-middle">

                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Code</th>
                                <th>Maximum Mark</th>
                                <th>Pass Mark</th>
                                <th>Weight</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($assignedSubjects === []): ?>

                            <tr>
                                <td colspan="7">

                                    <div class="empty-state">
                                        <i class="bi bi-journal-x"></i>

                                        <strong>
                                            No subjects assigned
                                        </strong>

                                        <span>
                                            Add a subject using
                                            the form above.
                                        </span>
                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $assignedSubjects as $subject
                            ): ?>

                                <tr>

                                    <td class="fw-semibold">
                                        <?= htmlspecialchars(
                                            $subject['subject_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $subject['subject_code']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $subject[
                                                'maximum_mark'
                                            ],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $subject[
                                                'pass_mark'
                                            ],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $subject[
                                                'weight_percentage'
                                            ],
                                            2
                                        ) ?>%
                                    </td>

                                    <td>
                                        <span class="status-badge status-active">
                                            <?= htmlspecialchars(
                                                $subject['status']
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            href="marks.php?examination_subject_id=<?= (int) $subject['id'] ?>"
                                            class="table-action-button"
                                            title="Enter subject marks"
                                        >
                                            <i class="bi bi-pencil-square"></i>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const classSelect = document.getElementById('classId');
    const streamSelect = document.getElementById('streamId');

    if (classSelect && streamSelect) {
        const streamOptions = Array.from(streamSelect.options);

        function filterStreams() {
            const selectedClass = classSelect.value;

            streamOptions.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                option.hidden =
                    option.dataset.classId !== selectedClass;
            });

            const selectedOption =
                streamSelect.options[streamSelect.selectedIndex];

            if (
                selectedOption &&
                selectedOption.value !== '' &&
                selectedOption.dataset.classId !== selectedClass
            ) {
                streamSelect.value = '';
            }
        }

        classSelect.addEventListener('change', filterStreams);
        filterStreams();
    }

    const subjectCheckboxes =
        document.querySelectorAll('.exam-subject-checkbox');

    document
        .getElementById('selectAllSubjects')
        ?.addEventListener('click', () => {
            subjectCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });
        });

    document
        .getElementById('clearAllSubjects')
        ?.addEventListener('click', () => {
            subjectCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
        });
});
</script>

</body>
</html>