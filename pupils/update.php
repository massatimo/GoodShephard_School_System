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

$pupilId = filter_var(
    $_POST['pupil_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$pupilId || $pupilId < 1) {
    $_SESSION['error_message'] = 'Invalid pupil selected.';

    header('Location: index.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    $csrfToken === '' ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)
) {
    exit('Invalid request. Refresh the form and try again.');
}

$existingStatement = $pdo->prepare(
    'SELECT id, admission_number, photo
     FROM pupils
     WHERE id = :id
     LIMIT 1'
);

$existingStatement->execute([
    'id' => $pupilId,
]);

$existingPupil = $existingStatement->fetch();

if (!$existingPupil) {
    $_SESSION['error_message'] = 'The pupil was not found.';

    header('Location: index.php');
    exit;
}

$input = [];

foreach ($_POST as $key => $value) {
    $input[$key] = is_string($value)
        ? trim($value)
        : $value;
}

$errors = [];

$requiredFields = [
    'admission_number' => 'Admission number',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'gender' => 'Gender',
    'class_id' => 'Class',
    'admission_date' => 'Admission date',
    'admission_type' => 'Admission type',
    'orphan_status' => 'Orphan status',
    'pupil_status' => 'Pupil status',
];

foreach ($requiredFields as $field => $label) {
    if (($input[$field] ?? '') === '') {
        $errors[] = $label . ' is required.';
    }
}

$duplicateStatement = $pdo->prepare(
    'SELECT id
     FROM pupils
     WHERE admission_number = :admission_number
       AND id <> :id
     LIMIT 1'
);

$duplicateStatement->execute([
    'admission_number' => $input['admission_number'] ?? '',
    'id' => $pupilId,
]);

if ($duplicateStatement->fetch()) {
    $errors[] = 'That admission number belongs to another pupil.';
}

$newPhotoPath = $existingPupil['photo'];

if (
    isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The pupil photograph could not be uploaded.';
    } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        $errors[] = 'The photograph must not exceed 2 MB.';
    } else {
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        $mimeType = $fileInfo->file(
            $_FILES['photo']['tmp_name']
        );

        if (!isset($allowedTypes[$mimeType])) {
            $errors[] = 'The photograph must be JPG or PNG.';
        } else {
            $extension = $allowedTypes[$mimeType];

            $fileName =
                bin2hex(random_bytes(16)) . '.' . $extension;

            $uploadDirectory =
                __DIR__ . '/../uploads/pupils/';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $destination = $uploadDirectory . $fileName;

            if (
                move_uploaded_file(
                    $_FILES['photo']['tmp_name'],
                    $destination
                )
            ) {
                $newPhotoPath =
                    'uploads/pupils/' . $fileName;
            } else {
                $errors[] = 'The photograph could not be saved.';
            }
        }
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    header('Location: edit.php?id=' . $pupilId);
    exit;
}

$sql = '
    UPDATE pupils SET
        admission_number = :admission_number,
        emis_number = :emis_number,
        lin_number = :lin_number,
        first_name = :first_name,
        middle_name = :middle_name,
        last_name = :last_name,
        gender = :gender,
        date_of_birth = :date_of_birth,
        nationality = :nationality,
        religion = :religion,
        class_id = :class_id,
        stream_id = :stream_id,
        admission_date = :admission_date,
        admission_type = :admission_type,
        former_school = :former_school,
        district = :district,
        county = :county,
        sub_county = :sub_county,
        parish = :parish,
        village = :village,
        home_address = :home_address,
        father_name = :father_name,
        father_phone = :father_phone,
        father_occupation = :father_occupation,
        mother_name = :mother_name,
        mother_phone = :mother_phone,
        mother_occupation = :mother_occupation,
        guardian_name = :guardian_name,
        guardian_phone = :guardian_phone,
        guardian_relationship = :guardian_relationship,
        orphan_status = :orphan_status,
        blood_group = :blood_group,
        medical_condition = :medical_condition,
        allergies = :allergies,
        has_disability = :has_disability,
        disability_type = :disability_type,
        special_needs = :special_needs,
        emergency_contact_name = :emergency_contact_name,
        emergency_contact_phone = :emergency_contact_phone,
        emergency_contact_relationship =
            :emergency_contact_relationship,
        photo = :photo,
        pupil_status = :pupil_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = :id
';

function optionalInput(array $input, string $field): ?string
{
    $value = $input[$field] ?? '';

    return $value !== '' ? $value : null;
}

try {
    $statement = $pdo->prepare($sql);

    $statement->execute([
        'admission_number' => $input['admission_number'],
        'emis_number' => optionalInput($input, 'emis_number'),
        'lin_number' => optionalInput($input, 'lin_number'),
        'first_name' => $input['first_name'],
        'middle_name' => optionalInput($input, 'middle_name'),
        'last_name' => $input['last_name'],
        'gender' => $input['gender'],
        'date_of_birth' => optionalInput($input, 'date_of_birth'),
        'nationality' => optionalInput($input, 'nationality')
            ?? 'Ugandan',
        'religion' => optionalInput($input, 'religion'),
        'class_id' => (int) $input['class_id'],
        'stream_id' => ($input['stream_id'] ?? '') !== ''
            ? (int) $input['stream_id']
            : null,
        'admission_date' => $input['admission_date'],
        'admission_type' => $input['admission_type'],
        'former_school' => optionalInput($input, 'former_school'),
        'district' => optionalInput($input, 'district'),
        'county' => optionalInput($input, 'county'),
        'sub_county' => optionalInput($input, 'sub_county'),
        'parish' => optionalInput($input, 'parish'),
        'village' => optionalInput($input, 'village'),
        'home_address' => optionalInput($input, 'home_address'),
        'father_name' => optionalInput($input, 'father_name'),
        'father_phone' => optionalInput($input, 'father_phone'),
        'father_occupation' =>
            optionalInput($input, 'father_occupation'),
        'mother_name' => optionalInput($input, 'mother_name'),
        'mother_phone' => optionalInput($input, 'mother_phone'),
        'mother_occupation' =>
            optionalInput($input, 'mother_occupation'),
        'guardian_name' => optionalInput($input, 'guardian_name'),
        'guardian_phone' =>
            optionalInput($input, 'guardian_phone'),
        'guardian_relationship' =>
            optionalInput($input, 'guardian_relationship'),
        'orphan_status' => $input['orphan_status'],
        'blood_group' => optionalInput($input, 'blood_group'),
        'medical_condition' =>
            optionalInput($input, 'medical_condition'),
        'allergies' => optionalInput($input, 'allergies'),
        'has_disability' =>
            isset($_POST['has_disability']) ? 1 : 0,
        'disability_type' =>
            isset($_POST['has_disability'])
                ? optionalInput($input, 'disability_type')
                : null,
        'special_needs' => optionalInput($input, 'special_needs'),
        'emergency_contact_name' =>
            optionalInput($input, 'emergency_contact_name'),
        'emergency_contact_phone' =>
            optionalInput($input, 'emergency_contact_phone'),
        'emergency_contact_relationship' =>
            optionalInput(
                $input,
                'emergency_contact_relationship'
            ),
        'photo' => $newPhotoPath,
        'pupil_status' => $input['pupil_status'],
        'id' => $pupilId,
    ]);

    if (
        $newPhotoPath !== $existingPupil['photo'] &&
        !empty($existingPupil['photo'])
    ) {
        $oldPhoto =
            __DIR__ . '/../' .
            ltrim((string) $existingPupil['photo'], '/');

        if (is_file($oldPhoto)) {
            unlink($oldPhoto);
        }
    }

    $_SESSION['success_message'] =
        'The pupil record was updated successfully.';

    header('Location: view.php?id=' . $pupilId);
    exit;
} catch (PDOException $exception) {
    exit(
        '<h2>Database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}