-- ============================================================
-- UPGRADE an EXISTING database (select it in phpMyAdmin first) (from the single-file version)
-- Back up your database first!
-- ============================================================

-- 1) Phone numbers are no longer collected
ALTER TABLE users DROP COLUMN phone;

-- 2) Table for emailed 2FA codes
CREATE TABLE IF NOT EXISTS login_otps (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  code_hash  VARCHAR(255) NOT NULL,
  attempts   TINYINT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3) Every account now needs a REAL email address, because the login code is
--    emailed to it. Check yours, especially the admin account:
-- SELECT id, name, email, role FROM users;
-- UPDATE users SET email = 'you@yourdomain.com' WHERE role = 'admin';
