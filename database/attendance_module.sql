USE goodshepherd_school;

CREATE TABLE IF NOT EXISTS attendance_registers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    academic_year_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    stream_id INT UNSIGNED NULL,

    attendance_date DATE NOT NULL,

    session ENUM(
        'Morning',
        'Afternoon'
    ) NOT NULL DEFAULT 'Morning',

    recorded_by INT UNSIGNED NOT NULL,

    notes VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_attendance_register_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_attendance_register_term
        FOREIGN KEY (term_id)
        REFERENCES terms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_attendance_register_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_attendance_register_stream
        FOREIGN KEY (stream_id)
        REFERENCES streams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_attendance_register_user
        FOREIGN KEY (recorded_by)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT unique_attendance_register
        UNIQUE (
            academic_year_id,
            term_id,
            class_id,
            stream_id,
            attendance_date,
            session
        )
);

CREATE TABLE IF NOT EXISTS pupil_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    attendance_register_id INT UNSIGNED NOT NULL,
    pupil_id INT UNSIGNED NOT NULL,

    attendance_status ENUM(
        'Present',
        'Absent',
        'Late',
        'Excused'
    ) NOT NULL DEFAULT 'Present',

    remarks VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pupil_attendance_register
        FOREIGN KEY (attendance_register_id)
        REFERENCES attendance_registers(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_pupil_attendance_pupil
        FOREIGN KEY (pupil_id)
        REFERENCES pupils(id)
        ON DELETE CASCADE,

    CONSTRAINT unique_pupil_attendance
        UNIQUE (
            attendance_register_id,
            pupil_id
        )
);