-- ============================================================
-- FRESH INSTALL. Does NOT create a database (Hostinger-ready):
-- hPanel > Databases > create one, open phpMyAdmin, SELECT it, then Import this file.
-- ============================================================

CREATE TABLE users (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(100) NOT NULL,
  email     VARCHAR(150) NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,
  address   VARCHAR(255) NULL,
  role      ENUM('user','librarian','chief_librarian','it_staff','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB;

CREATE TABLE books (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  author      VARCHAR(150) NOT NULL,
  isbn        VARCHAR(20)  NOT NULL,
  fee_per_day DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- One row per PHYSICAL copy. copy_code is the identifier (BK<bookId>-<n>).
CREATE TABLE book_copies (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  book_id   INT NOT NULL,
  copy_code VARCHAR(30) NOT NULL UNIQUE,
  status    ENUM('available','borrowed','lost','damaged') NOT NULL DEFAULT 'available',
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE borrowed_books (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  book_id       INT NOT NULL,
  copy_id       INT NULL,
  date_borrowed DATE NOT NULL,
  due_date      DATE NOT NULL,
  date_returned DATE NULL,
  fee           DECIMAL(10,2) NOT NULL DEFAULT 0,
  penalty       DECIMAL(10,2) NOT NULL DEFAULT 0,
  status        ENUM('borrowed','returned') NOT NULL DEFAULT 'borrowed',
  date_paid     DATE NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (book_id) REFERENCES books(id),
  FOREIGN KEY (copy_id) REFERENCES book_copies(id)
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  book_id     INT NOT NULL,
  status      ENUM('pending','ready','claimed','cancelled') NOT NULL DEFAULT 'pending',
  reserved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
) ENGINE=InnoDB;

-- Email 2FA codes (stored hashed, short-lived)
CREATE TABLE login_otps (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  code_hash  VARCHAR(255) NOT NULL,
  attempts   TINYINT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sign-ups wait here until the emailed code is entered (then a users row is created)
CREATE TABLE pending_signups (
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

-- Remembers which reminder emails were sent so members are not spammed
CREATE TABLE notification_log (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  user_id INT NOT NULL,
  type    ENUM('due_soon','overdue','fee') NOT NULL,
  sent_on DATE NOT NULL,
  UNIQUE KEY (loan_id, type, sent_on),
  FOREIGN KEY (loan_id) REFERENCES borrowed_books(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Default System Admin. 2FA codes are emailed to this address, so CHANGE IT to an
-- inbox you can read.  Default password: Admin@12345  (change it after first login)
INSERT INTO users (name, email, password, role)
VALUES ('Administrator', 'admin@sanbeda-alabang.edu.ph', '$2y$10$aD5unu5iw1FvtHeYf7Mk0.gFPviO87unI/b1Hi6j90D/hNFpfiYEi', 'admin');
