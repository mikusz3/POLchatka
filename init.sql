-- POLchatka — Pełna inicjalizacja bazy danych (Beta 0.9)
-- Użyj w phpMyAdmin: Importuj ten plik do polchatka_db

CREATE DATABASE IF NOT EXISTS polchatka_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE polchatka_db;

-- Użytkownicy
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar VARCHAR(10) DEFAULT '👤',
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  is_banned TINYINT(1) NOT NULL DEFAULT 0,
  last_login TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posty
CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  visibility ENUM('public','friends','private') NOT NULL DEFAULT 'public',
  likes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Znajomości
CREATE TABLE IF NOT EXISTS friendships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  friend_id INT NOT NULL,
  status ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  accepted_at TIMESTAMP NULL,
  UNIQUE KEY uniq_pair (user_id, friend_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wiadomości prywatne
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  content TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_receiver (receiver_id, is_read),
  INDEX idx_sender (sender_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personalizacja profilu
CREATE TABLE IF NOT EXISTS profile_customization (
  user_id INT PRIMARY KEY,
  theme_color VARCHAR(20) NULL,
  background_url VARCHAR(300) NULL,
  about_me VARCHAR(1000) NULL,
  links_json JSON NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gry
CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  description VARCHAR(2000) NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'inne',
  file_ext ENUM('zip','7z','swf') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tagi gier
CREATE TABLE IF NOT EXISTS game_tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tag VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS game_tag_map (
  game_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (game_id, tag_id),
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES game_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DANE TESTOWE
-- Hasło do wszystkich kont: "password"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================

INSERT IGNORE INTO users (username, email, password_hash, avatar, is_admin) VALUES
('admin',  'admin@polchatka.pl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '👨‍💼', 1),
('janek',  'janek@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '👨', 0),
('ania',   'ania@example.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '👩', 0),
('kasia',  'kasia@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '👧', 0),
('bartek', 'bartek@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '🧑', 0);

INSERT IGNORE INTO posts (user_id, content) VALUES
(1, 'Witajcie na POLchatce! To jest oficjalny portal 🎉'),
(2, 'Hej, to mój pierwszy post! Pozdrawiam wszystkich 😊'),
(3, 'Czy ktoś pamięta Poszkole.pl? Wspomnienia... 💫'),
(4, 'POLchatka jest super! Przypomina stare dobre czasy NK 🙌'),
(5, 'Siema! Szukam znajomych z Krakowa 🏰');

INSERT IGNORE INTO friendships (user_id, friend_id, status, accepted_at) VALUES
(2, 3, 'accepted', NOW()),
(2, 4, 'accepted', NOW()),
(3, 5, 'pending', NULL),
(4, 5, 'accepted', NOW());

INSERT IGNORE INTO messages (sender_id, receiver_id, content) VALUES
(2, 3, 'Hej Ania! Co słychać? 😊'),
(3, 2, 'Cześć Janek! Wszystko dobrze, a u Ciebie?'),
(2, 3, 'Super! Widziałaś ten nowy portal?'),
(4, 5, 'Bartek, dodaj mnie do znajomych!'),
(5, 4, 'Jasne, już dodaję! 👍');
