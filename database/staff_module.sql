USE goodshepherd_school;

ALTER TABLE staff
    ADD COLUMN first_name VARCHAR(80) NULL
        AFTER staff_number,

    ADD COLUMN middle_name VARCHAR(80) NULL
        AFTER first_name,

    ADD COLUMN last_name VARCHAR(80) NULL
        AFTER middle_name,

    ADD COLUMN date_of_birth DATE NULL
        AFTER gender,

    ADD COLUMN nationality VARCHAR(80)
        NOT NULL DEFAULT 'Ugandan'
        AFTER date_of_birth,

    ADD COLUMN religion VARCHAR(80) NULL
        AFTER nationality,

    ADD COLUMN national_id_number VARCHAR(40) NULL
        AFTER religion,

    ADD COLUMN marital_status ENUM(
        'Single',
        'Married',
        'Divorced',
        'Widowed',
        'Separated'
    ) NULL
        AFTER national_id_number,

    ADD COLUMN alternative_phone VARCHAR(30) NULL
        AFTER phone,

    ADD COLUMN district VARCHAR(100) NULL
        AFTER email,

    ADD COLUMN sub_county VARCHAR(100) NULL
        AFTER district,

    ADD COLUMN village VARCHAR(100) NULL
        AFTER sub_county,

    ADD COLUMN home_address TEXT NULL
        AFTER village,

    ADD COLUMN staff_category ENUM(
        'Teaching Staff',
        'Non-Teaching Staff',
        'Administration'
    ) NOT NULL DEFAULT 'Teaching Staff'
        AFTER home_address,

    ADD COLUMN department VARCHAR(100) NULL
        AFTER staff_category,

    ADD COLUMN designation VARCHAR(120) NULL
        AFTER department,

    ADD COLUMN employment_type ENUM(
        'Permanent',
        'Contract',
        'Part-Time',
        'Volunteer',
        'Probation'
    ) NOT NULL DEFAULT 'Permanent'
        AFTER designation,

    ADD COLUMN appointment_date DATE NULL
        AFTER employment_type,

    ADD COLUMN qualification VARCHAR(180) NULL
        AFTER appointment_date,

    ADD COLUMN specialization VARCHAR(150) NULL
        AFTER qualification,

    ADD COLUMN teaching_registration_number VARCHAR(80) NULL
        AFTER specialization,

    ADD COLUMN tin_number VARCHAR(50) NULL
        AFTER teaching_registration_number,

    ADD COLUMN nssf_number VARCHAR(50) NULL
        AFTER tin_number,

    ADD COLUMN bank_name VARCHAR(100) NULL
        AFTER nssf_number,

    ADD COLUMN bank_account_name VARCHAR(150) NULL
        AFTER bank_name,

    ADD COLUMN bank_account_number VARCHAR(80) NULL
        AFTER bank_account_name,

    ADD COLUMN next_of_kin_name VARCHAR(150) NULL
        AFTER bank_account_number,

    ADD COLUMN next_of_kin_phone VARCHAR(30) NULL
        AFTER next_of_kin_name,

    ADD COLUMN next_of_kin_relationship VARCHAR(80) NULL
        AFTER next_of_kin_phone,

    ADD COLUMN emergency_contact_name VARCHAR(150) NULL
        AFTER next_of_kin_relationship,

    ADD COLUMN emergency_contact_phone VARCHAR(30) NULL
        AFTER emergency_contact_name,

    ADD COLUMN medical_information TEXT NULL
        AFTER emergency_contact_phone,

    ADD COLUMN photo VARCHAR(255) NULL
        AFTER medical_information,

    ADD COLUMN updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
        AFTER created_at;

        ALTER TABLE staff
    MODIFY full_name VARCHAR(150) NULL,
    MODIFY position VARCHAR(100) NULL;

    ALTER TABLE staff
MODIFY employment_status ENUM(
    'Active',
    'On Leave',
    'Suspended',
    'Retired',
    'Resigned',
    'Terminated',
    'Inactive'
) NOT NULL DEFAULT 'Active';