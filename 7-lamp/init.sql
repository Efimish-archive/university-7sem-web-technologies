SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  reset_token CHAR(64) NULL UNIQUE,
  reset_expires DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS posts (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  author_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS menu_items (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  url VARCHAR(255) NOT NULL,
  order_num INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS images (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  original_path VARCHAR(500) NOT NULL,
  thumb_path VARCHAR(500) NOT NULL,
  user_id INT NOT NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_images_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO menu_items (title, url, order_num)
SELECT seed.title, seed.url, seed.order_num
FROM (
  SELECT 'Главная' AS title, '/' AS url, 1 AS order_num
  UNION ALL SELECT 'Блог', '/blog.php', 2
  UNION ALL SELECT 'Галерея', '/gallery.php', 3
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM menu_items existing WHERE existing.title = seed.title);

INSERT INTO posts (title, content)
SELECT seed.title, seed.content
FROM (
  SELECT 'Первый пост' AS title, 'Содержимое первого поста.' AS content
  UNION ALL SELECT 'Второй пост', 'Содержимое второго поста.'
  UNION ALL SELECT 'Третий пост', 'Демонстрационная запись для поиска и постраничного вывода.'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM posts existing WHERE existing.title = seed.title);
-- Вход администратора: admin@example.com / password
INSERT IGNORE INTO users (email, password_hash, name) VALUES
  ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');
