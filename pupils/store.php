<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);



require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    $csrfToken === '' ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)
) {
    exit('Invalid form request. Refresh the page and try again.');
}

$input = array_map(
    static fn ($value) => is_string($value)
        ? trim($value)
        : $value,
    $_POST
);

$errors = [];

$requiredFields = [
    'admission_date' => 'Admission date',
    'admission_type' => 'Admission type',
    'class_id' => 'Class',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'gender' => 'Gender',
    'orphan_status' => 'Orphan status',
];

foreach ($requiredFields as $field => $label) {
    if (($input[$field] ?? '') === '') {
        $errors[] = $label . ' is required.';
    }
}

if (
    isset($input['gender']) &&
    !in_array($input['gender'], ['Male', 'Female'], true)
) {
    $errors[] = 'Select a valid gender.';
}

$allowedOrphanStatuses = [
    'Not Orphan',
    'Single Orphan - Father Deceased',
    'Single Orphan - Mother Deceased',
    'Double Orphan',
];

if (
    isset($input['orphan_status']) &&
    !in_array(
        $input['orphan_status'],
        $allowedOrphanStatuses,
        true
    )
) {
    $errors[] = 'Select a valid orphan status.';
}

$className = null;
$classCode = null;
$streamName = null;

if (($input['class_id'] ?? '') !== '') {
    $classStatement = $pdo->prepare(
        "SELECT class_name, class_code
         FROM classes
         WHERE id = :class_id
           AND status = 'Active'
         LIMIT 1"
    );

    $classStatement->execute([
        'class_id' => (int) $input['class_id'],
    ]);

    $class = $classStatement->fetch();

    if ($class === false) {
        $errors[] = 'Select a valid class.';
    } else {
        $className = $class['class_name'];
        $classCode = $class['class_code'];
    }
}

if (($input['stream_id'] ?? '') !== '') {
    $streamStatement = $pdo->prepare(
        "SELECT stream_name
         FROM streams
         WHERE id = :stream_id
           AND class_id = :class_id
           AND status = 'Active'
         LIMIT 1"
    );

    $streamStatement->execute([
        'stream_id' => (int) $input['stream_id'],
        'class_id' => (int) ($input['class_id'] ?? 0),
    ]);

    $streamName = $streamStatement->fetchColumn();

    if ($streamName === false) {
        $streamName = null;
        $errors[] = 'Select a valid stream for the chosen class.';
    }
}

$photoPath = null;

if (
    isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The pupil photograph could not be uploaded.';
    } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        $errors[] = 'The pupil photograph must not exceed 2 MB.';
    } else {
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($_FILES['photo']['tmp_name']);

        if (!isset($allowedMimeTypes[$mimeType])) {
            $errors[] = 'The photograph must be JPG or PNG.';
        } else {
            $extension = $allowedMimeTypes[$mimeType];
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

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
                $photoPath = 'uploads/pupils/' . $fileName;
            } else {
                $errors[] = 'The photograph could not be saved.';
            }
        }
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    header('Location: create.php');
    exit;
}
$admissionPrefix = sprintf(
    '%s/GS/%s/',
    date('y'),
    strtoupper((string) $classCode)
);

$sequenceStatement = $pdo->prepare(
    "SELECT COALESCE(
        MAX(
            CAST(
                SUBSTRING_INDEX(admission_number, '/', -1)
                AS UNSIGNED
            )
        ),
        0
     ) + 1
     FROM pupils
     WHERE class_id = :class_id
       AND admission_number LIKE :admission_prefix"
);

$sequenceStatement->execute([
    'class_id' => (int) $input['class_id'],
    'admission_prefix' => $admissionPrefix . '%',
]);

$admissionNumber = $admissionPrefix . str_pad(
    (string) $sequenceStatement->fetchColumn(),
    3,
    '0',
    STR_PAD_LEFT
);

$sql = '
    INSERT INTO pupils (
        admission_number,
        emis_number,
        lin_number,
        first_name,
        middle_name,
        last_name,
        gender,
        date_of_birth,
        nationality,
        religion,
        class_id,
        stream_id,
        class_name,
        stream_name,
        admission_date,
        admission_type,
        former_school,
        district,
        county,
        sub_county,
        parish,
        village,
        home_address,
        father_name,
        father_phone,
        father_occupation,
        mother_name,
        mother_phone,
        mother_occupation,
        guardian_name,
        guardian_phone,
        guardian_relationship,
        orphan_status,
        blood_group,
        medical_condition,
        allergies,
        has_disability,
        disability_type,
        special_needs,
        emergency_contact_name,
        emergency_contact_phone,
        emergency_contact_relationship,
        photo,
        pupil_status
    ) VALUES (
        :admission_number,
        :emis_number,
        :lin_number,
        :first_name,
        :middle_name,
        :last_name,
        :gender,
        :date_of_birth,
        :nationality,
        :religion,
        :class_id,
        :stream_id,
        :class_name,
        :stream_name,
        :admission_date,
        :admission_type,
        :former_school,
        :district,
        :county,
        :sub_county,
        :parish,
        :village,
        :home_address,
        :father_name,
        :father_phone,
        :father_occupation,
        :mother_name,
        :mother_phone,
        :mother_occupation,
        :guardian_name,
        :guardian_phone,
        :guardian_relationship,
        :orphan_status,
        :blood_group,
        :medical_condition,
        :allergies,
        :has_disability,
        :disability_type,
        :special_needs,
        :emergency_contact_name,
        :emergency_contact_phone,
        :emergency_contact_relationship,
        :photo,
        :pupil_status
    )
';

$statement = $pdo->prepare($sql);

$statement->execute([
    'admission_number' => $admissionNumber,
    'emis_number' => $input['emis_number'] ?: null,
    'lin_number' => $input['lin_number'] ?: null,
    'first_name' => $input['first_name'],
    'middle_name' => $input['middle_name'] ?: null,
    'last_name' => $input['last_name'],
    'gender' => $input['gender'],
    'date_of_birth' => $input['date_of_birth'] ?: null,
    'nationality' => $input['nationality'] ?: 'Ugandan',
    'religion' => $input['religion'] ?: null,
    'class_id' => (int) $input['class_id'],
    'stream_id' => $input['stream_id'] !== ''
        ? (int) $input['stream_id']
        : null,
    'class_name' => $className,
    'stream_name' => $streamName,
    'admission_date' => $input['admission_date'],
    'admission_type' => $input['admission_type'],
    'former_school' => $input['former_school'] ?: null,
    'district' => $input['district'] ?: null,
    'county' => $input['county'] ?: null,
    'sub_county' => $input['sub_county'] ?: null,
    'parish' => $input['parish'] ?: null,
    'village' => $input['village'] ?: null,
    'home_address' => $input['home_address'] ?: null,
    'father_name' => $input['father_name'] ?: null,
    'father_phone' => $input['father_phone'] ?: null,
    'father_occupation' => $input['father_occupation'] ?: null,
    'mother_name' => $input['mother_name'] ?: null,
    'mother_phone' => $input['mother_phone'] ?: null,
    'mother_occupation' => $input['mother_occupation'] ?: null,
    'guardian_name' => $input['guardian_name'] ?: null,
    'guardian_phone' => $input['guardian_phone'] ?: null,
    'guardian_relationship' =>
        $input['guardian_relationship'] ?: null,
    'orphan_status' => $input['orphan_status'],
    'blood_group' => $input['blood_group'] ?: null,
    'medical_condition' => $input['medical_condition'] ?: null,
    'allergies' => $input['allergies'] ?: null,
    'has_disability' => isset($_POST['has_disability']) ? 1 : 0,
    'disability_type' => $input['disability_type'] ?? null,
    'special_needs' => $input['special_needs'] ?: null,
    'emergency_contact_name' =>
        $input['emergency_contact_name'] ?: null,
    'emergency_contact_phone' =>
        $input['emergency_contact_phone'] ?: null,
    'emergency_contact_relationship' =>
        $input['emergency_contact_relationship'] ?: null,
    'photo' => $photoPath,
    'pupil_status' => 'Active',
]);

$_SESSION['success_message'] =
    'The pupil was registered successfully.';

header('Location: index.php');
exit;
