-- Drop database if exists (for hurtig genstart under udvikling)
DROP DATABASE IF EXISTS kollegie;

-- Create database
CREATE DATABASE IF NOT EXISTS kollegie DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci;
USE kollegie;

-- Users table for administrators and staff (kombineret med employees)
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

-- Events table (begivenheder)
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

-- Activities log (aktivitetslog)
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
('jesp', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Jesper Jensen', 'jesp@mercantec.dk', '+45 12 34 56 78', 'Administrator', 'Administrator');

-- Staff users (alle ansatte kan nu logge ind)
INSERT INTO users (username, password, name, email, phone, role, profession) VALUES 
('gitte', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Gitte Ølgod', 'gitte@mercantec.dk', '+45 23 45 67 89', 'Personale', 'Receptionist'),
('anders', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Anders Nielsen', 'anders@mercantec.dk', '+45 34 56 78 90', 'Personale', 'Pedel'),
('mette', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Mette Jensen', 'mette@mercantec.dk', '+45 45 67 89 01', 'Personale', 'Rengøringsassistent'),
('lars', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Lars Pedersen', 'lars@mercantec.dk', '+45 56 78 90 12', 'Personale', 'Kantineleder'),
('sofie', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Sofie Hansen', 'sofie@mercantec.dk', '+45 67 89 01 23', 'Personale', 'Receptionist'),
('mikkel', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Mikkel Larsen', 'mikkel@mercantec.dk', '+45 78 90 12 34', 'Personale', 'IT-ansvarlig'),
('anne', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Anne Sørensen', 'anne@mercantec.dk', '+45 89 01 23 45', 'Personale', 'Køkkenleder'),
('thomas', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Thomas Andersen', 'thomas@mercantec.dk', '+45 90 12 34 56', 'Personale', 'Viceværtmester'),
('maria', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Maria Kristensen', 'maria@mercantec.dk', '+45 01 23 45 67', 'Personale', 'Social koordinator'),
('peter', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Peter Madsen', 'peter@mercantec.dk', '+45 12 34 56 78', 'Personale', 'Sikkerhedsansvarlig');
-- Password for alle: password123

-- Sample residents data
INSERT INTO residents (first_name, last_name, email, phone, room_number, contact_name, contact_phone, profile_image) VALUES
('Alexander', 'Jensen', 'alexander@example.com', '+45 12 34 56 78', 'A-204', 'Marie Jensen', '+45 87 65 43 21', NULL),
('Emma', 'Nielsen', 'emma@example.com', '+45 23 45 67 89', 'B-103', 'Jens Nielsen', '+45 98 76 54 32', NULL),
('Frederik', 'Hansen', 'frederik@example.com', '+45 34 56 78 90', 'A-105', 'Lars Hansen', '+45 76 54 32 10', NULL),
('Isabella', 'Larsen', 'isabella@example.com', '+45 45 67 89 01', 'B-207', 'Morten Larsen', '+45 65 43 21 09', NULL),
('William', 'Andersen', 'william@example.com', '+45 56 78 90 12', 'C-301', 'Susanne Andersen', '+45 54 32 10 98', NULL);

-- News data
INSERT INTO news (title, content, is_featured, created_by) VALUES
('Velkommen til kollegiet', 'Vi er glade for at byde dig velkommen til vores kollegium. Her finder du information om fællesområder, vaskerum og meget mere.', 1, 1),
('Renovering af badeværelser', 'Vi skal renovere badeværelserne på 2. og 3. etage i uge 32. Der vil være midlertidige badefaciliteter i kælderen.', 0, 1),
('Sommerfest 2025', 'Vi holder sommerfest d. 15. juni 2025 kl. 15:00 i gårdhaven. Alle beboere er velkomne.', 0, 2),
('Ny madplan klar', 'Den nye madplan for næste uge er nu klar. Tjek den ud under madplan-sektionen.', 0, 7),
('WiFi opdatering', 'Vi opgraderer WiFi netværket i weekenden. Der kan være kortere afbrydelser lørdag morgen.', 0, 6);

-- News reads data
INSERT INTO news_reads (news_id, resident_id) VALUES
(1, 1), (1, 2), (2, 1), (3, 2), (4, 1);

-- Events data
INSERT INTO events (title, description, date, time, location, max_participants, created_by) VALUES
('Filmaften', 'Vi ser filmen "Dune: Part Two" på storskærm med popcorn og sodavand. Kom og hyg med!', '2025-05-30', '20:00:00', 'Fællesrummet, Stueetagen', 25, 1),
('Brætspilsaften', 'Tag dit yndlingsbrætspil med og kom til en hyggelig aften med andre beboere.', '2025-05-30', '19:00:00', 'Fællesrummet, Stueetagen', NULL, 2),
('Fællesspisning', 'Vi laver mad sammen og spiser i fællesrummet. Alle er velkomne.', '2025-05-31', '18:00:00', 'Køkkenet, 1. etage', 20, 1),
('Yoga i haven', 'Kom til gratis yoga session i kollegiets have. Medbring egen måtte.', '2025-06-01', '10:00:00', 'Haven', 15, 9),
('Gaming turnering', 'FIFA turnering med præmier. Tilmelding på plads.', '2025-06-02', '19:00:00', 'Spillerummet', 16, 6);

-- Event participants data
INSERT INTO event_participants (event_id, resident_id) VALUES
(1, 1), (1, 2), (1, 3), (2, 1), (2, 4), (3, 2), (3, 5), (4, 1), (4, 3), (5, 2), (5, 4), (5, 5);