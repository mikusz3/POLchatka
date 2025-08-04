-- Tworzenie bazy danych POLchatka
CREATE DATABASE IF NOT EXISTS polchatka_db CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci;
USE polchatka_db;

-- Tabela użytkowników
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    birth_year INT DEFAULT NULL,
    gender ENUM('M', 'K') DEFAULT NULL,
    avatar VARCHAR(10) DEFAULT '👤',
    profile_public BOOLEAN DEFAULT TRUE,
    newsletter BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_active (is_active),
    INDEX idx_last_login (last_login)
);

-- Tabela sesji użytkowników (opcjonalnie, dla dodatkowego bezpieczeństwa)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
);

-- Tabela logów aktywności
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Tabela grup (dla przyszłego rozwoju)
CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(10) DEFAULT '👥',
    member_count INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_name (name),
    INDEX idx_public (is_public),
    INDEX idx_member_count (member_count)
);

-- Tabela postów na ścianie (dla przyszłego rozwoju)
CREATE TABLE wall_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Dodanie testowych użytkowników
INSERT INTO users (username, email, password, first_name, last_name, city, avatar, is_admin) VALUES 
('admin', 'admin@polchatka.pl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'POLchatka', 'Warszawa', '👨‍💼', TRUE),
('user', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jan', 'Kowalski', 'Kraków', '👨', FALSE),
('testuser', 'test@polchatka.pl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna', 'Nowak', 'Gdańsk', '👩', FALSE);

-- Dodanie przykładowych grup
INSERT INTO groups (name, description, icon, member_count, created_by) VALUES 
('🎵 Muzyka Polska', 'Grupa dla fanów polskiej muzyki', '🎵', 2234, 1),
('⚽ Piłka nożna', 'Dyskusje o piłce nożnej', '⚽', 1987, 1),
('🎮 Gracze', 'Społeczność graczy', '🎮', 1456, 1),
('📚 Studenci', 'Grupa dla studentów', '📚', 1234, 1),
('🎭 Fani Anime', 'Miłośnicy anime i mangi', '🎭', 987, 1);

-- Przykładowe posty na ścianie
INSERT INTO wall_posts (user_id, content) VALUES 
(2, 'Witajcie w POLchatce! To jest mój pierwszy post 😊'),
(3, 'Świetny portal! Przypomina mi stare dobre czasy 💫'),
(1, 'Witamy wszystkich nowych użytkowników! 🎉');
