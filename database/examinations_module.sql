USE goodshepherd_school;

CREATE TABLE IF NOT EXISTS examination_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    type_name VARCHAR(100) NOT NULL UNIQUE,

    type_code VARCHAR(30) NOT NULL UNIQUE,

    default_weight DECIMAL(5,2)
        NOT NULL DEFAULT 100.00,

    description VARCHAR(255) NULL,

    status ENUM(
        'Active',
        'Inactive'
    ) NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS examinations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    examination_type_id INT UNSIGNED NOT NULL,

    academic_year_id INT UNSIGNED NOT NULL,

    term_id INT UNSIGNED NOT NULL,

    examination_name VARCHAR(150) NOT NULL,

    start_date DATE NULL,

    end_date DATE NULL,

    status ENUM(
        'Draft',
        'Open',
        'Closed',
        'Published'
    ) NOT NULL DEFAULT 'Draft',

    description VARCHAR(255) NULL,

    created_by INT UNSIGNED NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_examination_type
        FOREIGN KEY (examination_type_id)
        REFERENCES examination_types(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_examination_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_examination_term
        FOREIGN KEY (term_id)
        REFERENCES terms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_examination_creator
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT unique_examination_name
        UNIQUE (
            academic_year_id,
            term_id,
            examination_name
        )
);

CREATE TABLE IF NOT EXISTS examination_classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    examination_id INT UNSIGNED NOT NULL,

    class_id INT UNSIGNED NOT NULL,

    stream_id INT UNSIGNED NULL,

    status ENUM(
        'Active',
        'Inactive'
    ) NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_exam_class_examination
        FOREIGN KEY (examination_id)
        REFERENCES examinations(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_exam_class_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_exam_class_stream
        FOREIGN KEY (stream_id)
        REFERENCES streams(id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS examination_subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    examination_class_id INT UNSIGNED NOT NULL,

    subject_id INT UNSIGNED NOT NULL,

    maximum_mark DECIMAL(6,2)
        NOT NULL DEFAULT 100.00,

    pass_mark DECIMAL(6,2)
        NOT NULL DEFAULT 50.00,

    weight_percentage DECIMAL(5,2)
        NOT NULL DEFAULT 100.00,

    status ENUM(
        'Active',
        'Inactive'
    ) NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_exam_subject_class
        FOREIGN KEY (examination_class_id)
        REFERENCES examination_classes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_exam_subject_subject
        FOREIGN KEY (subject_id)
        REFERENCES subjects(id)
        ON DELETE CASCADE,

    CONSTRAINT unique_exam_class_subject
        UNIQUE (
            examination_class_id,
            subject_id
        )
);

CREATE TABLE IF NOT EXISTS pupil_marks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    examination_subject_id INT UNSIGNED NOT NULL,

    pupil_id INT UNSIGNED NOT NULL,

    mark_obtained DECIMAL(6,2) NULL,

    grade VARCHAR(10) NULL,

    points DECIMAL(5,2) NULL,

    teacher_remark VARCHAR(255) NULL,

    is_absent TINYINT(1)
        NOT NULL DEFAULT 0,

    entered_by INT UNSIGNED NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pupil_mark_exam_subject
        FOREIGN KEY (examination_subject_id)
        REFERENCES examination_subjects(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pupil_mark_pupil
        FOREIGN KEY (pupil_id)
        REFERENCES pupils(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pupil_mark_user
        FOREIGN KEY (entered_by)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT unique_pupil_subject_mark
        UNIQUE (
            examination_subject_id,
            pupil_id
        )
);

CREATE TABLE IF NOT EXISTS grading_scales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    grade_name VARCHAR(10) NOT NULL,

    minimum_mark DECIMAL(6,2) NOT NULL,

    maximum_mark DECIMAL(6,2) NOT NULL,

    points DECIMAL(5,2) NULL,

    remark VARCHAR(100) NULL,

    applicable_level ENUM(
        'Nursery',
        'Primary',
        'All'
    ) NOT NULL DEFAULT 'Primary',

    status ENUM(
        'Active',
        'Inactive'
    ) NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT unique_grade_range
        UNIQUE (
            grade_name,
            applicable_level
        )
);

INSERT IGNORE INTO examination_types (
    type_name,
    type_code,
    default_weight,
    description,
    status
) VALUES
    (
        'Beginning of Term Examination',
        'BOT',
        20.00,
        'Assessment conducted at the beginning of the term',
        'Active'
    ),
    (
        'Continuous Assessment',
        'CA',
        20.00,
        'Ongoing classroom assessment',
        'Active'
    ),
    (
        'Mid-Term Examination',
        'MID',
        30.00,
        'Assessment conducted in the middle of the term',
        'Active'
    ),
    (
        'End of Term Examination',
        'EOT',
        50.00,
        'Final assessment conducted at the end of the term',
        'Active'
    ),
    (
        'Mock Examination',
        'MOCK',
        100.00,
        'Preparatory examination, normally for Primary Seven',
        'Active'
    );

    INSERT IGNORE INTO grading_scales (
    grade_name,
    minimum_mark,
    maximum_mark,
    points,
    remark,
    applicable_level,
    status
) VALUES
    ('D1', 90.00, 100.00, 1.00, 'Excellent', 'Primary', 'Active'),
    ('D2', 80.00, 89.99, 2.00, 'Very Good', 'Primary', 'Active'),
    ('C3', 70.00, 79.99, 3.00, 'Good', 'Primary', 'Active'),
    ('C4', 60.00, 69.99, 4.00, 'Credit', 'Primary', 'Active'),
    ('C5', 55.00, 59.99, 5.00, 'Credit', 'Primary', 'Active'),
    ('C6', 50.00, 54.99, 6.00, 'Credit', 'Primary', 'Active'),
    ('P7', 45.00, 49.99, 7.00, 'Pass', 'Primary', 'Active'),
    ('P8', 40.00, 44.99, 8.00, 'Pass', 'Primary', 'Active'),
    ('F9', 0.00, 39.99, 9.00, 'Fail', 'Primary', 'Active');

    