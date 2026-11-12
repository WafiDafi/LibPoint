-- ============================================================
-- STEP 3 UPGRADE: staff roles, email-verified sign-up, notifications.
-- Run AFTER migration.sql and migration_copies.sql. Back up first!
-- ============================================================

-- Roles: user = Member; librarian; chief_librarian; it_staff and admin (identical permissions)
ALTER TABLE users MODIFY role ENUM('user','librarian','chief_librarian','it_staff','admin') NOT NULL DEFAULT 'user';

CREATE TABLE IF NOT EXISTS pending_signups (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(150) NOT NULL UNIQUE,
  name          VARCHAR(100) NOT NULL,
  address       VARCHAR(255) NULL,
  password_hash VARCHAR(255) NOT NULL,
  code_hash     VARCHAR(255) NOT NULL,
  attempts      TINYINT NOT NULL DEFAULT 0,
  expires_at    DATETIME NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_log (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  user_id INT NOT NULL,
  type    ENUM('due_soon','overdue','fee') NOT NULL,
  sent_on DATE NOT NULL,
  UNIQUE KEY (loan_id, type, sent_on),
  FOREIGN KEY (loan_id) REFERENCES borrowed_books(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- The system now only accepts @sanbeda-alabang.edu.ph addresses (login AND sign-up).
-- Find accounts that would be locked out, and fix them BEFORE going live:
--   SELECT id, name, email, role FROM users WHERE email NOT LIKE '%@sanbeda-alabang.edu.ph';
--   UPDATE users SET email = 'real.name@sanbeda-alabang.edu.ph' WHERE id = 1;
-- Promote staff (they must have registered first):
--   UPDATE users SET role = 'chief_librarian' WHERE email = '...';
