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

$staffId = filter_var(
    $_POST['staff_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$staffId || $staffId < 1) {
    $_SESSION['error_message'] = 'Invalid staff member selected.';

    header('Location: index.php');
    exit;
}

if (
    !hash_equals(
        $_SESSION['csrf_token'] ?? '',
        $_POST['csrf_token'] ?? ''
    )
) {
    exit('Invalid request. Refresh the form and try again.');
}

$existingStatement = $pdo->prepare(
    'SELECT id, photo
     FROM staff
     WHERE id = :id
     LIMIT 1'
);

$existingStatement->execute([
    'id' => $staffId,
]);

$existingStaff = $existingStatement->fetch();

if (!$existingStaff) {
    $_SESSION['error_message'] = 'The staff member was not found.';

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
    'staff_number' => 'Staff number',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'gender' => 'Gender',
    'phone' => 'Phone number',
    'staff_category' => 'Staff category',
    'designation' => 'Designation',
    'employment_type' => 'Employment type',
    'employment_status' => 'Employment status',
];

foreach ($requiredFields as $field => $label) {
    if (($input[$field] ?? '') === '') {
        $errors[] = $label . ' is required.';
    }
}

if (
    ($input['email'] ?? '') !== '' &&
    !filter_var($input['email'], FILTER_VALIDATE_EMAIL)
) {
    $errors[] = 'Enter a valid email address.';
}

$duplicateStatement = $pdo->prepare(
    'SELECT id
     FROM staff
     WHERE staff_number = :staff_number
       AND id <> :id
     LIMIT 1'
);

$duplicateStatement->execute([
    'staff_number' => $input['staff_number'] ?? '',
    'id' => $staffId,
]);

if ($duplicateStatement->fetch()) {
    $errors[] = 'That staff number belongs to another staff member.';
}

$newPhotoPath = $existingStaff['photo'];

if (
    isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The photograph could not be uploaded.';
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
            $fileName =
                bin2hex(random_bytes(16)) .
                '.' .
                $allowedTypes[$mimeType];

            $uploadDirectory =
                __DIR__ . '/../uploads/staff/';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            if (
                move_uploaded_file(
                    $_FILES['photo']['tmp_name'],
                    $uploadDirectory . $fileName
                )
            ) {
                $newPhotoPath =
                    'uploads/staff/' . $fileName;
            } else {
                $errors[] = 'The photograph could not be saved.';
            }
        }
    }
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;

    header('Location: edit.php?id=' . $staffId);
    exit;
}

function optionalStaffUpdate(
    array $input,
    string $field
): ?string {
    $value = $input[$field] ?? '';

    return $value !== '' ? $value : null;
}

$fullName = trim(
    $input['first_name'] . ' ' .
    ($input['middle_name'] ?? '') . ' ' .
    $input['last_name']
);

$sql = '
    UPDATE staff SET
        staff_number = :staff_number,
        first_name = :first_name,
        middle_name = :middle_name,
        last_name = :last_name,
        full_name = :full_name,
        gender = :gender,
        date_of_birth = :date_of_birth,
        nationality = :nationality,
        religion = :religion,
        national_id_number = :national_id_number,
        marital_status = :marital_status,
        phone = :phone,
        alternative_phone = :alternative_phone,
        email = :email,
        district = :district,
        sub_county = :sub_county,
        village = :village,
        home_address = :home_address,
        staff_category = :staff_category,
        department = :department,
        designation = :designation,
        position = :position,
        employment_type = :employment_type,
        appointment_date = :appointment_date,
        qualification = :qualification,
        specialization = :specialization,
        teaching_registration_number =
            :teaching_registration_number,
        tin_number = :tin_number,
        nssf_number = :nssf_number,
        bank_name = :bank_name,
        bank_account_name = :bank_account_name,
        bank_account_number = :bank_account_number,
        next_of_kin_name = :next_of_kin_name,
        next_of_kin_phone = :next_of_kin_phone,
        next_of_kin_relationship = :next_of_kin_relationship,
        emergency_contact_name = :emergency_contact_name,
        emergency_contact_phone = :emergency_contact_phone,
        medical_information = :medical_information,
        photo = :photo,
        employment_status = :employment_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = :id
';

try {
    $statement = $pdo->prepare($sql);

    $statement->execute([
        'staff_number' => $input['staff_number'],
        'first_name' => $input['first_name'],
        'middle_name' =>
            optionalStaffUpdate($input, 'middle_name'),
        'last_name' => $input['last_name'],
        'full_name' => $fullName,
        'gender' => $input['gender'],
        'date_of_birth' =>
            optionalStaffUpdate($input, 'date_of_birth'),
        'nationality' =>
            optionalStaffUpdate($input, 'nationality')
            ?? 'Ugandan',
        'religion' =>
            optionalStaffUpdate($input, 'religion'),
        'national_id_number' =>
            optionalStaffUpdate(
                $input,
                'national_id_number'
            ),
        'marital_status' =>
            optionalStaffUpdate($input, 'marital_status'),
        'phone' => $input['phone'],
        'alternative_phone' =>
            optionalStaffUpdate(
                $input,
                'alternative_phone'
            ),
        'email' =>
            optionalStaffUpdate($input, 'email'),
        'district' =>
            optionalStaffUpdate($input, 'district'),
        'sub_county' =>
            optionalStaffUpdate($input, 'sub_county'),
        'village' =>
            optionalStaffUpdate($input, 'village'),
        'home_address' =>
            optionalStaffUpdate($input, 'home_address'),
        'staff_category' => $input['staff_category'],
        'department' =>
            optionalStaffUpdate($input, 'department'),
        'designation' => $input['designation'],
        'position' => $input['designation'],
        'employment_type' => $input['employment_type'],
        'appointment_date' =>
            optionalStaffUpdate($input, 'appointment_date'),
        'qualification' =>
            optionalStaffUpdate($input, 'qualification'),
        'specialization' =>
            optionalStaffUpdate($input, 'specialization'),
        'teaching_registration_number' =>
            optionalStaffUpdate(
                $input,
                'teaching_registration_number'
            ),
        'tin_number' =>
            optionalStaffUpdate($input, 'tin_number'),
        'nssf_number' =>
            optionalStaffUpdate($input, 'nssf_number'),
        'bank_name' =>
            optionalStaffUpdate($input, 'bank_name'),
        'bank_account_name' =>
            optionalStaffUpdate(
                $input,
                'bank_account_name'
            ),
        'bank_account_number' =>
            optionalStaffUpdate(
                $input,
                'bank_account_number'
            ),
        'next_of_kin_name' =>
            optionalStaffUpdate(
                $input,
                'next_of_kin_name'
            ),
        'next_of_kin_phone' =>
            optionalStaffUpdate(
                $input,
                'next_of_kin_phone'
            ),
        'next_of_kin_relationship' =>
            optionalStaffUpdate(
                $input,
                'next_of_kin_relationship'
            ),
        'emergency_contact_name' =>
            optionalStaffUpdate(
                $input,
                'emergency_contact_name'
            ),
        'emergency_contact_phone' =>
            optionalStaffUpdate(
                $input,
                'emergency_contact_phone'
            ),
        'medical_information' =>
            optionalStaffUpdate(
                $input,
                'medical_information'
            ),
        'photo' => $newPhotoPath,
        'employment_status' =>
            $input['employment_status'],
        'id' => $staffId,
    ]);

    if (
        $newPhotoPath !== $existingStaff['photo'] &&
        !empty($existingStaff['photo'])
    ) {
        $oldPhoto =
            __DIR__ . '/../' .
            ltrim((string) $existingStaff['photo'], '/');

        if (is_file($oldPhoto)) {
            unlink($oldPhoto);
        }
    }

    $_SESSION['success_message'] =
        'The staff record was updated successfully.';

    header('Location: view.php?id=' . $staffId);
    exit;
} catch (PDOException $exception) {
    exit(
        '<h2>Database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}