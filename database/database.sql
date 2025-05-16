-- Drop database if exists (for hurtig genstart under udvikling)
DROP DATABASE IF EXISTS kollegie;

-- Create database
CREATE DATABASE IF NOT EXISTS kollegie DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci;
USE kollegie;

-- Users table for administrators and staff
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('Administrator', 'Personale') NOT NULL DEFAULT 'Personale',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Opdateret residents table (med alle students-tabel felter)
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
-- Opdateret employee table (med alle students-tabel felter)
CREATE TABLE employee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    profesion VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Foodplan table (madplan)
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

-- Allergens table (allergener)
CREATE TABLE allergens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Foodplan allergens (mange-til-mange relation) - ændret til at inkludere dag
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

-- Event participants (tilmelding til begivenheder)
CREATE TABLE event_participants (
    event_id INT NOT NULL,
    resident_id INT NOT NULL,
    signup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id, resident_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
);

-- News table (nyheder)
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

-- Ny tabel: News reads (læste nyheder)
CREATE TABLE news_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    resident_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    UNIQUE KEY (news_id, resident_id)
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
INSERT INTO users (username, password, name, email, role) VALUES 
('jesp', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Jesper Jensen', 'jesp@mercantec.dk', 'Administrator');
-- Password: password123

-- Staff user 
INSERT INTO users (username, password, name, email, role) VALUES 
('Gitte', '$2y$10$auGPeXStY/TCU.26mLO5pupwTIYu4mBhDpz0bEw75wTRsCruaCqrO', 'Gitte Ølgod', 'gitø@mercantec.dk', 'Personale');
-- Password: password123

-- Sample residents (beboere) - opdateret til kun at bruge de kolonner der findes i tabellen
INSERT INTO residents (first_name, last_name, email, phone, room_number, contact_name, contact_phone, profile_image) VALUES
('Alexander', 'Jensen', 'alexander@example.com', '+45 12 34 56 78', 'A-204', 'Marie Jensen', '+45 87 65 43 21', NULL),
('Emma', 'Nielsen', 'emma@example.com', '+45 23 45 67 89', 'B-103', 'Jens Nielsen', '+45 98 76 54 32', NULL);

-- Sample employee (ansatte) - opdateret til kun at bruge de kolonner der findes i tabellen
INSERT INTO employee (first_name, last_name, email, phone, profesion, profile_image) VALUES
('Peter', 'Olesen', 'Peter@example.com', '+45 87 65 43 21', 'pedel', NULL),
('Lene', 'schulz', 'Lene@example.com', '+45 92 83 74 16', 'rengøring', NULL);

-- Sample news
INSERT INTO news (title, content, is_featured, created_by) VALUES
('Velkommen til kollegiet', 'Vi er glade for at byde dig velkommen til vores kollegium. Her finder du information om fællesområder, vaskerum og meget mere.', 1, 1),
('Renovering af badeværelser', 'Vi skal renovere badeværelserne på 2. og 3. etage i uge 32. Der vil være midlertidige badefaciliteter i kælderen.', 0, 1),
('Sommerfest 2025', 'Vi holder sommerfest d. 15. juni 2025 kl. 15:00 i gårdhaven. Alle beboere er velkomne.', 0, 2);

-- Sample news reads
INSERT INTO news_reads (news_id, resident_id) VALUES
(1, 1), (1, 2), (2, 1);

-- Sample events
INSERT INTO events (title, description, date, time, location, max_participants, created_by) VALUES
('Filmaften', 'Vi ser filmen "Dune: Part Two" på storskærm med popcorn og sodavand. Kom og hyg med!', '2025-05-15', '20:00:00', 'Fællesrummet, Stueetagen', 25, 1),
('Brætspilsaften', 'Tag dit yndlingsbrætspil med og kom til en hyggelig aften med andre beboere.', '2025-05-22', '19:00:00', 'Fællesrummet, Stueetagen', NULL, 2),
('Fællesspisning', 'Vi laver mad sammen og spiser i fællesrummet. Alle er velkomne.', '2025-05-30', '18:00:00', 'Køkkenet, 1. etage', 20, 1);

-- Sample event participants
INSERT INTO event_participants (event_id, resident_id) VALUES
(1, 1), (1, 2), (2, 1);