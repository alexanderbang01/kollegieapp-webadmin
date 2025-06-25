-- Drop database if exists (for hurtig genstart under udvikling)
DROP DATABASE IF EXISTS kollegie;

-- Create database
CREATE DATABASE IF NOT EXISTS kollegie DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci;
USE kollegie;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('Administrator', 'Personale') NOT NULL DEFAULT 'Personale',
    profession VARCHAR(255) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Residents table
CREATE TABLE residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    contact_name VARCHAR(100),
    contact_phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Foodplan table
CREATE TABLE foodplan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week_number INT NOT NULL,
    year INT NOT NULL,
    monday_dish VARCHAR(100) NULL,
    monday_description TEXT,
    monday_vegetarian TINYINT(1) NOT NULL DEFAULT 0,
    tuesday_dish VARCHAR(100) NULL,
    tuesday_description TEXT,
    tuesday_vegetarian TINYINT(1) NOT NULL DEFAULT 0,
    wednesday_dish VARCHAR(100) NULL,
    wednesday_description TEXT,
    wednesday_vegetarian TINYINT(1) NOT NULL DEFAULT 0,
    thursday_dish VARCHAR(100) NULL,
    thursday_description TEXT,
    thursday_vegetarian TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (week_number, year)
);

-- Allergens table
CREATE TABLE allergens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Foodplan allergens
CREATE TABLE foodplan_allergens (
    foodplan_id INT NOT NULL,
    allergen_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday') NOT NULL,
    PRIMARY KEY (foodplan_id, allergen_id, day_of_week),
    FOREIGN KEY (foodplan_id) REFERENCES foodplan(id) ON DELETE CASCADE,
    FOREIGN KEY (allergen_id) REFERENCES allergens(id) ON DELETE CASCADE
);

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    location VARCHAR(100) NOT NULL,
    max_participants INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Event participants
CREATE TABLE event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    resident_id INT NOT NULL,
    signup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (event_id, resident_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- News table
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- News reads
CREATE TABLE news_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    resident_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    UNIQUE KEY (news_id, resident_id)
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('staff', 'resident') NOT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('staff', 'resident') NOT NULL,
    content TEXT NOT NULL,
    encryption_iv VARCHAR(32) DEFAULT NULL,
    is_encrypted TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id, sender_type),
    INDEX idx_recipient (recipient_id, recipient_type),
    INDEX idx_created (created_at),
    INDEX idx_read (read_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('event', 'news', 'message') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    related_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notification reads table
CREATE TABLE notification_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    resident_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    UNIQUE KEY (notification_id, resident_id)
);

-- Activities log
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    resident_id INT DEFAULT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
);

-- Standard allergens
INSERT INTO allergens (name) VALUES 
('Gluten'), ('Laktose'), ('Nødder'), ('Æg'), ('Soja'), ('Fisk'), ('Skaldyr'), ('Selleri'), ('Sennep');

-- Admin user
INSERT INTO users (username, password, name, email, phone, role, profession) VALUES 
('java', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Jannie Vedel Andersen', 'java@mercantec.dk', '+45 21 67 69 92', 'Administrator', 'Kollegiekoordinator');

-- Staff users (alle ansatte kan nu logge ind)
INSERT INTO users (username, password, name, email, phone, role, profession) VALUES 
('krpr', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Kristian Primholdt', 'krpr@mercantec.dk', '+45 89 50 33 00', 'Personale', 'Kollegiekonsulent'),
('sosc', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Søren Schou', 'sosc@mercantec.dk', '+45 23 36 53 73', 'Personale', 'Kollegiekonsulent'),
('pefi', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Pernille Bach Filtenborg', 'pefi@mercantec.dk', '+45 40 54 13 75', 'Personale', 'Kollegiekonsulent'),
('leds', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Lene Dalsgaard Sørensen', 'leds@mercantec.dk', '+45 89 50 33 00', 'Personale', 'Rengøringsassistent'),
('betm', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Bethina Didriksen', 'betm@mercantec.dk', '+45 89 50 33 00', 'Personale', 'Rengøringassistent'),
('kpto', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Kim P. Tougaard', 'kpto@mercantec.dk', '+45 30 63 78 36', 'Personale', 'Driftsassistent'),
('ahej', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Anne Hejselbæk', 'ahej@mercantec.dk', '+45 89 50 33 00', 'Personale', 'Kok'),
('gisl', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Gitte Steenberg Lynggaard', 'gisl@mercantec.dk', '+45 89 50 33 00', 'Personale', 'Kok'),
('dala', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Dan Aas Laursen', 'dala@mercantec.dk', '+45 21 78 98 07', 'Personale', 'Uddannelsesleder'),
('kumi', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Kurt Mikkelsen', 'kumi@mercantec.dk', '+45 89 50 33 00', 'Personale', 'Bedstefar');
-- Password for alle: password123