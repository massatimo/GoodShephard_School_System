USE goodshepherd_school;

CREATE TABLE IF NOT EXISTS academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year_name VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('Active', 'Closed') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    term_name ENUM(
        'Term One',
        'Term Two',
        'Term Three'
    ) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('Active', 'Closed') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_term_academic_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON DELETE CASCADE,

    CONSTRAINT unique_year_term
        UNIQUE (academic_year_id, term_name)
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(30) NOT NULL UNIQUE,
    subject_category ENUM(
        'Core',
        'Optional',
        'Co-curricular'
    ) NOT NULL DEFAULT 'Core',
    applicable_level ENUM(
        'Nursery',
        'Lower Primary',
        'Upper Primary',
        'All'
    ) NOT NULL DEFAULT 'All',
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO academic_years (
    year_name,
    start_date,
    end_date,
    is_current,
    status
) VALUES (
    '2026',
    '2026-02-02',
    '2026-12-04',
    1,
    'Active'
);

INSERT IGNORE INTO terms (
    academic_year_id,
    term_name,
    start_date,
    end_date,
    is_current,
    status
)
SELECT
    id,
    'Term One',
    '2026-02-02',
    '2026-05-01',
    0,
    'Closed'
FROM academic_years
WHERE year_name = '2026';

INSERT IGNORE INTO terms (
    academic_year_id,
    term_name,
    start_date,
    end_date,
    is_current,
    status
)
SELECT
    id,
    'Term Two',
    '2026-05-25',
    '2026-08-21',
    1,
    'Active'
FROM academic_years
WHERE year_name = '2026';

INSERT IGNORE INTO terms (
    academic_year_id,
    term_name,
    start_date,
    end_date,
    is_current,
    status
)
SELECT
    id,
    'Term Three',
    '2026-09-14',
    '2026-12-04',
    0,
    'Active'
FROM academic_years
WHERE year_name = '2026';

INSERT IGNORE INTO subjects (
    subject_name,
    subject_code,
    subject_category,
    applicable_level,
    status
) VALUES
    ('English Language', 'ENG', 'Core', 'All', 'Active'),
    ('Mathematics', 'MTC', 'Core', 'All', 'Active'),
    ('Integrated Science', 'SCI', 'Core', 'Upper Primary', 'Active'),
    ('Social Studies', 'SST', 'Core', 'Upper Primary', 'Active'),
    ('Religious Education', 'RE', 'Core', 'All', 'Active'),
    ('Literacy I', 'LIT1', 'Core', 'Lower Primary', 'Active'),
    ('Literacy II', 'LIT2', 'Core', 'Lower Primary', 'Active'),
    ('Local Language', 'LLG', 'Core', 'Lower Primary', 'Active'),
    ('Creative Performing Arts', 'CPA', 'Co-curricular', 'All', 'Active'),
    ('Physical Education', 'PE', 'Co-curricular', 'All', 'Active'),
    ('Computer Studies', 'ICT', 'Optional', 'Upper Primary', 'Active');

    