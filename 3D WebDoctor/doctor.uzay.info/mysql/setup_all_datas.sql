CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(35) NOT NULL,
    surname VARCHAR(45) NOT NULL,
    date_of_birth VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL UNIQUE,
    password2 VARCHAR(255) NOT NULL,
    password3 VARCHAR(255) NOT NULL,
    password4 VARCHAR(255) NOT NULL,
    information VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT (UTC_TIMESTAMP + INTERVAL 3 HOUR)
) CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

CREATE TABLE Ills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    medicine VARCHAR(15) NOT NULL,
    brand VARCHAR(65) NOT NULL,
    name VARCHAR(35) NOT NULL,
    surname VARCHAR(45) NOT NULL,
    information VARCHAR(500) NOT NULL,
    age VARCHAR(20) NOT NULL,
    weight DOUBLE NOT NULL,
    dose VARCHAR(21) NOT NULL,
    daily_amount VARCHAR(14) NOT NULL,
    discomfort VARCHAR(100) NOT NULL,
    sub_discomfort VARCHAR(100) NOT NULL,
    recommendation VARCHAR(527),
    guidance VARCHAR(200) NOT NULL,
    urgency VARCHAR(1) NOT NULL,
    pharmacy VARCHAR(100) NOT NULL,
    types VARCHAR(9) NOT NULL,
    conclusion_rationale VARCHAR(373) NOT NULL,
    detail_conclusion_rationale VARCHAR(500),
    result VARCHAR(1) NOT NULL,
    created_at TIMESTAMP DEFAULT (UTC_TIMESTAMP + INTERVAL 3 HOUR)
) CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

CREATE TABLE pharmacies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    confirmation_code VARCHAR(255) NOT NULL UNIQUE,
    cupboard_code VARCHAR(255) NOT NULL UNIQUE,
    medicine_order VARCHAR(4) NOT NULL,
    updated_at TIMESTAMP DEFAULT (UTC_TIMESTAMP + INTERVAL 3 HOUR),
    created_at TIMESTAMP DEFAULT (UTC_TIMESTAMP + INTERVAL 3 HOUR)
) CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

CREATE TABLE pharmacist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharmacy VARCHAR(100) NOT NULL,
    pharmacist_name VARCHAR(35) NOT NULL,
    pharmacist_surname VARCHAR(45) NOT NULL,
    pharmacist_password VARCHAR(255) NOT NULL UNIQUE,
    pharmacist_password2 VARCHAR(255) NOT NULL,
    pharmacist_password3 VARCHAR(255) NOT NULL,
    pharmacist_password4 VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT (UTC_TIMESTAMP + INTERVAL 3 HOUR)
) CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;
