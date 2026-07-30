<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (
    !hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token'] ?? ''
    )
) {
    exit('Invalid request. Refresh the page and try again.');
}

$examinationSubjectId = filter_var(
    $_POST['examination_subject_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$examinationSubjectId) {
    $_SESSION['error_message'] =
        'Invalid examination subject selected.';

    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve examination subject
|--------------------------------------------------------------------------
*/

$subjectStatement = $pdo->prepare(
    'SELECT
        examination_subjects.id,
        examination_subjects.maximum_mark,
        examination_subjects.examination_class_id,
        examinations.status AS examination_status

     FROM examination_subjects

     INNER JOIN examination_classes
        ON examination_classes.id =
            examination_subjects.examination_class_id

     INNER JOIN examinations
        ON examinations.id =
            examination_classes.examination_id

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

if (
    !in_array(
        $examinationSubject['examination_status'],
        ['Draft', 'Open'],
        true
    )
) {
    $_SESSION['error_message'] =
        'Marks cannot be changed because this examination ' .
        'is not open for marks entry.';

    header(
        'Location: marks.php?examination_subject_id=' .
        $examinationSubjectId
    );
    exit;
}

$maximumMark =
    (float) $examinationSubject['maximum_mark'];

$marks = $_POST['marks'] ?? [];
$absentPupils = $_POST['is_absent'] ?? [];
$teacherRemarks =
    $_POST['teacher_remarks'] ?? [];

if (!is_array($marks)) {
    $marks = [];
}

if (!is_array($absentPupils)) {
    $absentPupils = [];
}

if (!is_array($teacherRemarks)) {
    $teacherRemarks = [];
}

/*
|--------------------------------------------------------------------------
| Get the valid pupils for this examination class
|--------------------------------------------------------------------------
*/

$classStatement = $pdo->prepare(
    'SELECT
        examination_classes.class_id,
        examination_classes.stream_id
     FROM examination_classes
     WHERE examination_classes.id =
        :examination_class_id
     LIMIT 1'
);

$classStatement->execute([
    'examination_class_id' =>
        $examinationSubject[
            'examination_class_id'
        ],
]);

$examClass = $classStatement->fetch();

if (!$examClass) {
    $_SESSION['error_message'] =
        'The examination class was not found.';

    header('Location: index.php');
    exit;
}

$pupilSql = '
    SELECT id
    FROM pupils
    WHERE class_id = :class_id
      AND pupil_status = "Active"
';

$pupilParameters = [
    'class_id' => $examClass['class_id'],
];

if (!empty($examClass['stream_id'])) {
    $pupilSql .= '
        AND stream_id = :stream_id
    ';

    $pupilParameters['stream_id'] =
        $examClass['stream_id'];
}

$pupilStatement = $pdo->prepare($pupilSql);
$pupilStatement->execute($pupilParameters);

$validPupilIds = array_map(
    'intval',
    $pupilStatement->fetchAll(PDO::FETCH_COLUMN)
);

/*
|--------------------------------------------------------------------------
| Retrieve grading scale
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

function calculatePupilGrade(
    float $percentage,
    array $gradingScales
): array {
    foreach ($gradingScales as $scale) {
        if (
            $percentage >=
                (float) $scale['minimum_mark'] &&
            $percentage <=
                (float) $scale['maximum_mark']
        ) {
            return [
                'grade' =>
                    (string) $scale['grade_name'],
                'points' =>
                    $scale['points'] !== null
                        ? (float) $scale['points']
                        : null,
                'remark' =>
                    (string) ($scale['remark'] ?? ''),
            ];
        }
    }

    return [
        'grade' => null,
        'points' => null,
        'remark' => '',
    ];
}

$errors = [];
$preparedMarks = [];

foreach ($validPupilIds as $pupilId) {
    $isAbsent =
        isset($absentPupils[$pupilId]) ? 1 : 0;

    $teacherRemark = trim(
        (string) (
            $teacherRemarks[$pupilId] ?? ''
        )
    );

    if ($isAbsent === 1) {
        $preparedMarks[] = [
            'pupil_id' => $pupilId,
            'mark_obtained' => null,
            'grade' => 'ABS',
            'points' => null,
            'teacher_remark' =>
                $teacherRemark !== ''
                    ? $teacherRemark
                    : 'Absent',
            'is_absent' => 1,
        ];

        continue;
    }

    $rawMark = $marks[$pupilId] ?? '';

    if (
        $rawMark === '' ||
        $rawMark === null
    ) {
        /*
         * A blank mark is ignored. Existing marks are retained.
         */
        continue;
    }

    if (!is_numeric($rawMark)) {
        $errors[] =
            'One or more entered marks are not numeric.';
        break;
    }

    $markObtained = (float) $rawMark;

    if (
        $markObtained < 0 ||
        $markObtained > $maximumMark
    ) {
        $errors[] =
            'Marks must be between 0 and ' .
            number_format($maximumMark, 2) . '.';
        break;
    }

    $percentage =
        $maximumMark > 0
            ? ($markObtained / $maximumMark) * 100
            : 0;

    $gradeResult = calculatePupilGrade(
        $percentage,
        $gradingScales
    );

    $preparedMarks[] = [
        'pupil_id' => $pupilId,
        'mark_obtained' => $markObtained,
        'grade' => $gradeResult['grade'],
        'points' => $gradeResult['points'],
        'teacher_remark' =>
            $teacherRemark !== ''
                ? $teacherRemark
                : $gradeResult['remark'],
        'is_absent' => 0,
    ];
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;

    header(
        'Location: marks.php?examination_subject_id=' .
        $examinationSubjectId
    );
    exit;
}

try {
    $pdo->beginTransaction();

    $saveStatement = $pdo->prepare(
        'INSERT INTO pupil_marks (
            examination_subject_id,
            pupil_id,
            mark_obtained,
            grade,
            points,
            teacher_remark,
            is_absent,
            entered_by
        ) VALUES (
            :examination_subject_id,
            :pupil_id,
            :mark_obtained,
            :grade,
            :points,
            :teacher_remark,
            :is_absent,
            :entered_by
        )
        ON DUPLICATE KEY UPDATE
            mark_obtained =
                VALUES(mark_obtained),
            grade =
                VALUES(grade),
            points =
                VALUES(points),
            teacher_remark =
                VALUES(teacher_remark),
            is_absent =
                VALUES(is_absent),
            entered_by =
                VALUES(entered_by),
            updated_at =
                CURRENT_TIMESTAMP'
    );

    foreach ($preparedMarks as $mark) {
        $saveStatement->execute([
            'examination_subject_id' =>
                $examinationSubjectId,
            'pupil_id' => $mark['pupil_id'],
            'mark_obtained' =>
                $mark['mark_obtained'],
            'grade' => $mark['grade'],
            'points' => $mark['points'],
            'teacher_remark' =>
                $mark['teacher_remark'],
            'is_absent' =>
                $mark['is_absent'],
            'entered_by' =>
                (int) $_SESSION['user_id'],
        ]);
    }

    $pdo->commit();

    $_SESSION['success_message'] =
        count($preparedMarks) .
        ' pupil mark record(s) saved successfully.';

    header(
        'Location: marks.php?examination_subject_id=' .
        $examinationSubjectId
    );
    exit;
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    exit(
        '<h2>Marks database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}