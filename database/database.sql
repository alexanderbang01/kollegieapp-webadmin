-- Drop database if exists (for hurtig genstart under udvikling)
DROP DATABASE IF EXISTS kollegie;

-- Create database
CREATE DATABASE IF NOT EXISTS kollegie DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci;
USE kollegie;

-- Users table for administrators and staff
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Store hashed passwords
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Residents table (beboere)
CREATE TABLE residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    floor VARCHAR(10) NOT NULL,
    education VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Foodplan table (madplan)
CREATE TABLE foodplan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week_number INT NOT NULL,
    year INT NOT NULL,
    monday_dish VARCHAR(100) NOT NULL,
    monday_description TEXT,
    tuesday_dish VARCHAR(100) NOT NULL,
    tuesday_description TEXT,
    wednesday_dish VARCHAR(100) NOT NULL,
    wednesday_description TEXT,
    thursday_dish VARCHAR(100) NOT NULL,
    thursday_description TEXT,
    serving_time TIME NOT NULL DEFAULT '18:00:00',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY (week_number, year) -- Sikrer at der kun er én række per uge per år
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
    status ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Event categories (kategorier til begivenheder)
CREATE TABLE event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL -- FontAwesome ikonnavn
);

-- Event category relations (mange-til-mange)
CREATE TABLE event_category_relations (
    event_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (event_id, category_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES event_categories(id) ON DELETE CASCADE
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

-- News categories (kategorier til nyheder)
CREATE TABLE news_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL -- FontAwesome ikonnavn
);

-- News category relations (mange-til-mange)
CREATE TABLE news_category_relations (
    news_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (news_id, category_id),
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES news_categories(id) ON DELETE CASCADE
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

-- Standard event categories
INSERT INTO event_categories (name, icon) VALUES 
('Social', 'fas fa-users'), 
('Sport', 'fas fa-running'), 
('Fest', 'fas fa-glass-cheers'), 
('Film', 'fas fa-film'), 
('Møde', 'fas fa-comments');

-- Standard news categories
INSERT INTO news_categories (name, icon) VALUES 
('Vigtige meddelelser', 'fas fa-exclamation-circle'), 
('Begivenheder', 'fas fa-calendar-alt'), 
('Faciliteter', 'fas fa-building'), 
('Vedligeholdelse', 'fas fa-tools'), 
('Generelle nyheder', 'fas fa-newspaper');

-- Standard admin user
INSERT INTO users (username, password, name, email, role) VALUES 
('admin', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'Admin Jensen', 'admin@example.com', 'admin');
-- Password: admin123

-- Eksempel på madplan for en uge
INSERT INTO foodplan (
    week_number, 
    year, 
    monday_dish, 
    monday_description, 
    tuesday_dish, 
    tuesday_description, 
    wednesday_dish, 
    wednesday_description, 
    thursday_dish, 
    thursday_description, 
    created_by
) VALUES (
    18, 
    2025, 
    'Lasagne med salat', 
    'Hjemmelavet lasagne med oksekød og grøn salat', 
    'Kylling i karry', 
    'Kylling i karrysauce med ris og nanbrød', 
    'Pasta Carbonara', 
    'Klassisk italiensk ret med bacon, æg og parmesan', 
    'Taco torsdag', 
    'Tacos med oksekød, salsa, guacamole og tilbehør', 
    1
);

-- Tilføj allergener til madplanen
INSERT INTO foodplan_allergens (foodplan_id, allergen_id, day_of_week) VALUES 
(1, 1, 'monday'), -- Gluten i mandag
(1, 2, 'monday'), -- Laktose i mandag
(1, 1, 'tuesday'), -- Gluten i tirsdag
(1, 1, 'wednesday'), -- Gluten i onsdag
(1, 2, 'wednesday'), -- Laktose i onsdag
(1, 4, 'wednesday'), -- Æg i onsdag
(1, 1, 'thursday'), -- Gluten i torsdag
(1, 2, 'thursday'); -- Laktose i torsdag