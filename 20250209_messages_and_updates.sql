-- ============================================================
-- Migration: messages table + schema updates for Beta
-- Date: 2025-02-09
-- ============================================================

-- Tabela wiadomości prywatnych
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

-- Upewnij się, że tabela friendships istnieje
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

-- Upewnij się, że tabela posts istnieje
CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  visibility ENUM('public','friends','private') NOT NULL DEFAULT 'public',
  likes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dodaj kolumny avatar i is_banned jeśli nie istnieją (bezpieczne ALTER)
-- MySQL 8 obsługuje IF NOT EXISTS w ADD COLUMN
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(10) DEFAULT '👤';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) NOT NULL DEFAULT 0;

-- Przykładowe dane testowe (bezpieczne - ignoruje duplikaty)
INSERT IGNORE INTO users (username, email, password_hash, is_admin) VALUES
('admin', 'admin@polchatka.pl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('janek', 'janek@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('ania', 'ania@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('kasia', 'kasia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
('bartek', 'bartek@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- Przykładowe posty
INSERT IGNORE INTO posts (user_id, content) VALUES
(1, 'Witajcie na POLchatce! To jest oficjalny portal 🎉'),
(2, 'Hej, to mój pierwszy post! Pozdrawiam wszystkich 😊'),
(3, 'Czy ktoś pamięta Poszkole.pl? Wspomnienia... 💫'),
(4, 'POLchatka jest super! Przypomina stare dobre czasy NK 🙌'),
(5, 'Siema! Szukam znajomych z Krakowa 🏰');

-- Przykładowe znajomości
INSERT IGNORE INTO friendships (user_id, friend_id, status, accepted_at) VALUES
(2, 3, 'accepted', NOW()),
(2, 4, 'accepted', NOW()),
(3, 5, 'pending', NULL),
(4, 5, 'accepted', NOW());

-- Przykładowe wiadomości
INSERT IGNORE INTO messages (sender_id, receiver_id, content) VALUES
(2, 3, 'Hej Ania! Co słychać? 😊'),
(3, 2, 'Cześć Janek! Wszystko dobrze, a u Ciebie?'),
(2, 3, 'Super! Widziałaś ten nowy portal?'),
(4, 5, 'Bartek, dodaj mnie do znajomych!'),
(5, 4, 'Jasne, już dodaję! 👍');
