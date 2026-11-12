-- ============================================================
-- STEP 2 UPGRADE: per-copy tracking.  Run AFTER migration.sql.
-- Back up your database first! Needs MariaDB 10.2+ / MySQL 8+ (any current XAMPP).
-- ============================================================

CREATE TABLE book_copies (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  book_id   INT NOT NULL,
  copy_code VARCHAR(30) NOT NULL UNIQUE,
  status    ENUM('available','borrowed','lost','damaged') NOT NULL DEFAULT 'available',
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE borrowed_books
  ADD COLUMN copy_id INT NULL AFTER book_id,
  ADD FOREIGN KEY (copy_id) REFERENCES book_copies(id);

-- Numbers 1..1000 (temporary helper)
CREATE TEMPORARY TABLE nums (n INT PRIMARY KEY);
INSERT INTO nums (n)
SELECT a.d + 10*b.d + 100*c.d + 1 FROM
  (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
  (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
  (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c;

-- One copy row per physical copy = copies on the shelf + copies currently out on loan
INSERT INTO book_copies (book_id, copy_code)
SELECT b.id, CONCAT('BK', LPAD(b.id, 4, '0'), '-', LPAD(nums.n, 2, '0'))
FROM books b
JOIN nums ON nums.n <= b.copies + (SELECT COUNT(*) FROM borrowed_books x WHERE x.book_id = b.id AND x.status = 'borrowed');

-- Attach each ACTIVE loan to one of its book's copies and mark that copy borrowed
UPDATE borrowed_books bb
JOIN (SELECT id, ROW_NUMBER() OVER (PARTITION BY book_id ORDER BY id) AS rn
      FROM borrowed_books WHERE status = 'borrowed') x ON x.id = bb.id
JOIN book_copies c ON c.copy_code = CONCAT('BK', LPAD(bb.book_id, 4, '0'), '-', LPAD(x.rn, 2, '0'))
SET bb.copy_id = c.id, c.status = 'borrowed';

-- Old returned loans keep copy_id NULL (copies were not tracked then).
-- The per-book counter is replaced by counting available copies.
ALTER TABLE books DROP COLUMN copies;
