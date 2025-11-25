-- Migration: ensure posts table matches expected schema
CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  visibility ENUM('public','friends','private') NOT NULL DEFAULT 'public',
  likes INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Align existing installations
ALTER TABLE posts
  MODIFY content TEXT NOT NULL,
  ADD COLUMN IF NOT EXISTS visibility ENUM('public','friends','private') NOT NULL DEFAULT 'public' AFTER content,
  ADD COLUMN IF NOT EXISTS likes INT UNSIGNED NOT NULL DEFAULT 0 AFTER visibility;
