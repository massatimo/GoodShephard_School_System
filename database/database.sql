CREATE DATABASE IF NOT EXISTS goodshepherd_school
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE goodshepherd_school;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM(
        'administrator',
        'headteacher',
        'bursar',
        'teacher'
    ) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE pupils (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admission_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(80) NOT NULL,
    middle_name VARCHAR(80) NULL,
    last_name VARCHAR(80) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE NULL,
    class_name VARCHAR(30) NOT NULL,
    stream_name VARCHAR(30) NULL,
    district VARCHAR(100) NULL,
    village VARCHAR(100) NULL,
    guardian_name VARCHAR(150) NULL,
    guardian_phone VARCHAR(30) NULL,
    orphan_status ENUM(
        'Not Orphan',
        'Single Orphan',
        'Double Orphan'
    ) DEFAULT 'Not Orphan',
    pupil_status ENUM(
        'Active',
        'Transferred',
        'Completed',
        'Dropped'
    ) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE staff (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_number VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    position VARCHAR(100) NOT NULL,
    employment_status ENUM(
        'Active',
        'On Leave',
        'Inactive'
    ) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fee_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pupil_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM(
        'Cash',
        'Bank',
        'Mobile Money',
        'Other'
    ) NOT NULL,
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    payment_date DATE NOT NULL,
    recorded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payment_pupil
        FOREIGN KEY (pupil_id)
        REFERENCES pupils(id),

    CONSTRAINT fk_payment_user
        FOREIGN KEY (recorded_by)
        REFERENCES users(id)
);

CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_category VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM(
        'Cash',
        'Bank',
        'Mobile Money',
        'Other'
    ) NOT NULL,
    expense_date DATE NOT NULL,
    reference_number VARCHAR(100) NULL,
    recorded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_expense_user
        FOREIGN KEY (recorded_by)
        REFERENCES users(id)
);