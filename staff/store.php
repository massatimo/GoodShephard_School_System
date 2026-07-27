<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
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

$checkNumber = $pdo->prepare(
    'SELECT id
     FROM staff
     WHERE staff_number = :staff_number
     LIMIT 1'
);

$checkNumber->execute([
    'staff_number' => $input['staff_number'] ?? '',
]);

if ($checkNumber->fetch()) {
    $errors[] = 'That staff number is already registered.';
}

if (
    ($input['email'] ?? '') !== '' &&
    !filter_var($input['email'], FILTER_VALIDATE_EMAIL)
) {
    $errors[] = 'Enter a valid email address.';
}

$photoPath = null;

if (
    isset($_FILES['photo']) &&
    $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The staff photograph could not be uploaded.';
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
                __DIR__ . '/../uploads/staff/';

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
                $photoPath =
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

    header('Location: create.php');
    exit;
}

function optionalStaffInput(
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
$school='GS';

$count=$pdo->query("
SELECT COUNT(*)+1
FROM staff
")->fetchColumn();

$staffNumber=
$school.'/S/'.
str_pad($count,3,'0',STR_PAD_LEFT);

$sql = '
    INSERT INTO staff (
       $staffNumber,
        first_name,
        middle_name,
        last_name,
        full_name,
        gender,
        date_of_birth,
        nationality,
        religion,
        national_id_number,
        marital_status,
        phone,
        alternative_phone,
        email,
        district,
        sub_county,
        village,
        home_address,
        staff_category,
        department,
        designation,
        position,
        employment_type,
        appointment_date,
        qualification,
        specialization,
        teaching_registration_number,
        tin_number,
        nssf_number,
        bank_name,
        bank_account_name,
        bank_account_number,
        next_of_kin_name,
        next_of_kin_phone,
        next_of_kin_relationship,
        emergency_contact_name,
        emergency_contact_phone,
        medical_information,
        photo,
        employment_status
    ) VALUES (
        :staff_number,
        :first_name,
        :middle_name,
        :last_name,
        :full_name,
        :gender,
        :date_of_birth,
        :nationality,
        :religion,
        :national_id_number,
        :marital_status,
        :phone,
        :alternative_phone,
        :email,
        :district,
        :sub_county,
        :village,
        :home_address,
        :staff_category,
        :department,
        :designation,
        :position,
        :employment_type,
        :appointment_date,
        :qualification,
        :specialization,
        :teaching_registration_number,
        :tin_number,
        :nssf_number,
        :bank_name,
        :bank_account_name,
        :bank_account_number,
        :next_of_kin_name,
        :next_of_kin_phone,
        :next_of_kin_relationship,
        :emergency_contact_name,
        :emergency_contact_phone,
        :medical_information,
        :photo,
        :employment_status
    )
';

try {
    $statement = $pdo->prepare($sql);

    $statement->execute([
        'staff_number' => $input['staff_number'],
        'first_name' => $input['first_name'],
        'middle_name' =>
            optionalStaffInput($input, 'middle_name'),
        'last_name' => $input['last_name'],
        'full_name' => $fullName,
        'gender' => $input['gender'],
        'date_of_birth' =>
            optionalStaffInput($input, 'date_of_birth'),
        'nationality' =>
            optionalStaffInput($input, 'nationality')
            ?? 'Ugandan',
        'religion' =>
            optionalStaffInput($input, 'religion'),
        'national_id_number' =>
            optionalStaffInput(
                $input,
                'national_id_number'
            ),
        'marital_status' =>
            optionalStaffInput($input, 'marital_status'),
        'phone' => $input['phone'],
        'alternative_phone' =>
            optionalStaffInput(
                $input,
                'alternative_phone'
            ),
        'email' =>
            optionalStaffInput($input, 'email'),
        'district' =>
            optionalStaffInput($input, 'district'),
        'sub_county' =>
            optionalStaffInput($input, 'sub_county'),
        'village' =>
            optionalStaffInput($input, 'village'),
        'home_address' =>
            optionalStaffInput($input, 'home_address'),
        'staff_category' => $input['staff_category'],
        'department' =>
            optionalStaffInput($input, 'department'),
        'designation' => $input['designation'],
        'position' => $input['designation'],
        'employment_type' => $input['employment_type'],
        'appointment_date' =>
            optionalStaffInput($input, 'appointment_date'),
        'qualification' =>
            optionalStaffInput($input, 'qualification'),
        'specialization' =>
            optionalStaffInput($input, 'specialization'),
        'teaching_registration_number' =>
            optionalStaffInput(
                $input,
                'teaching_registration_number'
            ),
        'tin_number' =>
            optionalStaffInput($input, 'tin_number'),
        'nssf_number' =>
            optionalStaffInput($input, 'nssf_number'),
        'bank_name' =>
            optionalStaffInput($input, 'bank_name'),
        'bank_account_name' =>
            optionalStaffInput(
                $input,
                'bank_account_name'
            ),
        'bank_account_number' =>
            optionalStaffInput(
                $input,
                'bank_account_number'
            ),
        'next_of_kin_name' =>
            optionalStaffInput(
                $input,
                'next_of_kin_name'
            ),
        'next_of_kin_phone' =>
            optionalStaffInput(
                $input,
                'next_of_kin_phone'
            ),
        'next_of_kin_relationship' =>
            optionalStaffInput(
                $input,
                'next_of_kin_relationship'
            ),
        'emergency_contact_name' =>
            optionalStaffInput(
                $input,
                'emergency_contact_name'
            ),
        'emergency_contact_phone' =>
            optionalStaffInput(
                $input,
                'emergency_contact_phone'
            ),
        'medical_information' =>
            optionalStaffInput(
                $input,
                'medical_information'
            ),
        'photo' => $photoPath,
        'employment_status' =>
            $input['employment_status'],
    ]);

    $_SESSION['success_message'] =
        'The staff member was registered successfully.';

    header('Location: index.php');
    exit;
} catch (PDOException $exception) {
    if ($photoPath !== null) {
        $uploadedPhoto =
            __DIR__ . '/../' . $photoPath;

        if (is_file($uploadedPhoto)) {
            unlink($uploadedPhoto);
        }
    }

    exit(
        '<h2>Database error</h2><pre>' .
        htmlspecialchars($exception->getMessage()) .
        '</pre>'
    );
}