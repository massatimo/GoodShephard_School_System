USE goodshepherd_school;

CREATE TABLE IF NOT EXISTS teacher_allocations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    staff_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    stream_id INT UNSIGNED NULL,
    subject_id INT UNSIGNED NULL,

    is_class_teacher TINYINT(1) NOT NULL DEFAULT 0,

    status ENUM(
        'Active',
        'Inactive'
    ) NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_teacher_allocation_staff
        FOREIGN KEY (staff_id)
        REFERENCES staff(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teacher_allocation_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teacher_allocation_term
        FOREIGN KEY (term_id)
        REFERENCES terms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teacher_allocation_class
        FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teacher_allocation_stream
        FOREIGN KEY (stream_id)
        REFERENCES streams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_teacher_allocation_subject
        FOREIGN KEY (subject_id)
        REFERENCES subjects(id)
        ON DELETE SET NULL,

    CONSTRAINT unique_teacher_assignment
        UNIQUE (
            staff_id,
            academic_year_id,
            term_id,
            class_id,
            stream_id,
            subject_id
        )
);