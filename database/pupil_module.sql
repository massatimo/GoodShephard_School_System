USE goodshepherd_school;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL UNIQUE,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    class_level INT NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS streams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    stream_name VARCHAR(50) NOT NULL,
    stream_code VARCHAR(20) NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_stream_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE CASCADE,

    CONSTRAINT unique_class_stream
        UNIQUE (class_id, stream_name)
);
INSERT IGNORE INTO classes (
    class_name,
    class_code,
    class_level
) VALUES
    ('Baby Class', 'BC', 1),
    ('Middle Class', 'MC', 2),
    ('Top Class', 'TC', 3),
    ('Primary One', 'P1', 4),
    ('Primary Two', 'P2', 5),
    ('Primary Three', 'P3', 6),
    ('Primary Four', 'P4', 7),
    ('Primary Five', 'P5', 8),
    ('Primary Six', 'P6', 9),
    ('Primary Seven', 'P7', 10);

    INSERT IGNORE INTO streams (
    class_id,
    stream_name,
    stream_code
)
SELECT id, 'Stream A', CONCAT(class_code, '-A')
FROM classes;

INSERT IGNORE INTO streams (
    class_id,
    stream_name,
    stream_code
)
SELECT id, 'Stream B', CONCAT(class_code, '-B')
FROM classes;
ALTER TABLE pupils
    ADD COLUMN emis_number VARCHAR(60) NULL
        AFTER admission_number,

    ADD COLUMN lin_number VARCHAR(60) NULL
        AFTER emis_number,

    ADD COLUMN nationality VARCHAR(80)
        NOT NULL DEFAULT 'Ugandan'
        AFTER date_of_birth,

    ADD COLUMN religion VARCHAR(80) NULL
        AFTER nationality,

    ADD COLUMN class_id INT UNSIGNED NULL
        AFTER religion,

    ADD COLUMN stream_id INT UNSIGNED NULL
        AFTER class_id,

    ADD COLUMN admission_date DATE NULL
        AFTER stream_id,

    ADD COLUMN admission_type ENUM(
        'New Admission',
        'Transfer',
        'Re-admission'
    ) NOT NULL DEFAULT 'New Admission'
        AFTER admission_date,

    ADD COLUMN former_school VARCHAR(180) NULL
        AFTER admission_type,

    ADD COLUMN district VARCHAR(100) NULL
        AFTER former_school,

    ADD COLUMN county VARCHAR(100) NULL
        AFTER district,

    ADD COLUMN sub_county VARCHAR(100) NULL
        AFTER county,

    ADD COLUMN parish VARCHAR(100) NULL
        AFTER sub_county,

    ADD COLUMN village VARCHAR(100) NULL
        AFTER parish,

    ADD COLUMN home_address TEXT NULL
        AFTER village,

    ADD COLUMN father_name VARCHAR(150) NULL
        AFTER home_address,

    ADD COLUMN father_phone VARCHAR(30) NULL
        AFTER father_name,

    ADD COLUMN father_occupation VARCHAR(120) NULL
        AFTER father_phone,

    ADD COLUMN mother_name VARCHAR(150) NULL
        AFTER father_occupation,

    ADD COLUMN mother_phone VARCHAR(30) NULL
        AFTER mother_name,

    ADD COLUMN mother_occupation VARCHAR(120) NULL
        AFTER mother_phone,

    ADD COLUMN guardian_name VARCHAR(150) NULL
        AFTER mother_occupation,

    ADD COLUMN guardian_phone VARCHAR(30) NULL
        AFTER guardian_name,

    ADD COLUMN guardian_relationship VARCHAR(80) NULL
        AFTER guardian_phone,

    ADD COLUMN orphan_status ENUM(
        'Not Orphan',
        'Single Orphan - Father Deceased',
        'Single Orphan - Mother Deceased',
        'Double Orphan'
    ) NOT NULL DEFAULT 'Not Orphan'
        AFTER guardian_relationship,

    ADD COLUMN blood_group VARCHAR(10) NULL
        AFTER orphan_status,

    ADD COLUMN medical_condition TEXT NULL
        AFTER blood_group,

    ADD COLUMN allergies TEXT NULL
        AFTER medical_condition,

    ADD COLUMN has_disability TINYINT(1)
        NOT NULL DEFAULT 0
        AFTER allergies,

    ADD COLUMN disability_type VARCHAR(150) NULL
        AFTER has_disability,

    ADD COLUMN special_needs TEXT NULL
        AFTER disability_type,

    ADD COLUMN emergency_contact_name VARCHAR(150) NULL
        AFTER special_needs,

    ADD COLUMN emergency_contact_phone VARCHAR(30) NULL
        AFTER emergency_contact_name,

    ADD COLUMN emergency_contact_relationship VARCHAR(80) NULL
        AFTER emergency_contact_phone,

    ADD COLUMN photo VARCHAR(255) NULL
        AFTER emergency_contact_relationship;
        
        ALTER TABLE pupils
    ADD CONSTRAINT fk_pupil_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE SET NULL,

    ADD CONSTRAINT fk_pupil_stream
        FOREIGN KEY (stream_id)
        REFERENCES streams(id)
        ON DELETE SET NULL;