-- ============================================================
-- SAMPLE DATA. Run AFTER database.sql (select your database in phpMyAdmin first).
-- Does not touch login_otps, pending_signups or notification_log (they stay empty).
-- Dates are relative to the day you import, so overdue / due-soon examples stay current.
-- Logins:  admin@sanbeda-alabang.edu.ph / Admin@12345   |   every account below / Password@123
--   lia.librarian (Librarian), carlo.chief (Chief Librarian), ian.it (IT Staff),
--   maria.santos, juan.delacruz, ana.reyes, paolo.garcia, bea.mendoza, rico.villanueva (Members)
--   (each @sanbeda-alabang.edu.ph)
-- NOTE: login 2FA codes go to these addresses, so replace with real inboxes to log in as them.
-- ============================================================

INSERT INTO users (id, name, email, password, address, role) VALUES
(2,'Lia Librarian','lia.librarian@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Library Desk, SBCA Alabang','librarian'),
(3,'Carlo Chief Librarian','carlo.chief@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Library Office, SBCA Alabang','chief_librarian'),
(4,'Ian IT Staff','ian.it@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','IT Office, SBCA Alabang','it_staff'),
(5,'Maria Santos','maria.santos@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Alabang, Muntinlupa City','user'),
(6,'Juan Dela Cruz','juan.delacruz@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Putatan, Muntinlupa City','user'),
(7,'Ana Reyes','ana.reyes@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Las Piñas City','user'),
(8,'Paolo Garcia','paolo.garcia@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Bacoor, Cavite','user'),
(9,'Bea Mendoza','bea.mendoza@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Parañaque City','user'),
(10,'Rico Villanueva','rico.villanueva@sanbeda-alabang.edu.ph','$2y$10$ItGcTuFTkYpQsQxrrvnPF.XXTDzjQhQoUeJSwnRWS2UxVb9LKCaXW','Alabang, Muntinlupa City','user');

INSERT INTO books (id, title, author, isbn, fee_per_day) VALUES
(1,'Noli Me Tangere','José Rizal','9789712712012',3.00),
(2,'El Filibusterismo','José Rizal','9789712712029',3.00),
(3,'To Kill a Mockingbird','Harper Lee','9780061120084',5.00),
(4,'1984','George Orwell','9780451524935',5.00),
(5,'The Great Gatsby','F. Scott Fitzgerald','9780743273565',4.00),
(6,'Introduction to Algorithms','Thomas H. Cormen','9780262033848',8.00),
(7,'Clean Code','Robert C. Martin','9780132350884',7.00),
(8,'Database System Concepts','Abraham Silberschatz','9780078022159',8.00),
(9,'Fundamentals of Physics','David Halliday','9781118230718',8.00),
(10,'Principles of Economics','N. Gregory Mankiw','9781305585126',6.00),
(11,'The Pragmatic Programmer','Andrew Hunt','9780135957059',7.00),
(12,'Harry Potter and the Sorcerer''s Stone','J. K. Rowling','9780590353427',5.00);

-- one row per physical copy; Book ID = BK<book id>-<copy no>
INSERT INTO book_copies (book_id, copy_code, status) VALUES
(1,'BK0001-01','borrowed'),
(1,'BK0001-02','available'),
(1,'BK0001-03','available'),
(2,'BK0002-01','available'),
(2,'BK0002-02','lost'),
(2,'BK0002-03','available'),
(3,'BK0003-01','borrowed'),
(3,'BK0003-02','available'),
(3,'BK0003-03','damaged'),
(4,'BK0004-01','borrowed'),
(4,'BK0004-02','available'),
(4,'BK0004-03','available'),
(5,'BK0005-01','available'),
(5,'BK0005-02','available'),
(6,'BK0006-01','borrowed'),
(6,'BK0006-02','available'),
(6,'BK0006-03','available'),
(7,'BK0007-01','borrowed'),
(7,'BK0007-02','available'),
(8,'BK0008-01','available'),
(8,'BK0008-02','available'),
(9,'BK0009-01','available'),
(9,'BK0009-02','available'),
(10,'BK0010-01','available'),
(10,'BK0010-02','available'),
(10,'BK0010-03','available'),
(11,'BK0011-01','available'),
(11,'BK0011-02','available'),
(12,'BK0012-01','borrowed'),
(12,'BK0012-02','available'),
(12,'BK0012-03','available'),
(12,'BK0012-04','available');

-- loans: 6 active (2 overdue, 1 due tomorrow) and 6 returned (3 with penalties: 2 unpaid, 1 paid)
INSERT INTO borrowed_books (id, user_id, book_id, copy_id, date_borrowed, due_date, date_returned, fee, penalty, status, date_paid) VALUES
(1,5,4,(SELECT id FROM book_copies WHERE copy_code='BK0004-01'),DATE_SUB(CURDATE(), INTERVAL 3 DAY),DATE_ADD(CURDATE(), INTERVAL 4 DAY),NULL,35.00,0.00,'borrowed',NULL),
(2,6,6,(SELECT id FROM book_copies WHERE copy_code='BK0006-01'),DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_SUB(CURDATE(), INTERVAL 3 DAY),NULL,56.00,0.00,'borrowed',NULL),
(3,7,7,(SELECT id FROM book_copies WHERE copy_code='BK0007-01'),DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 1 DAY),NULL,21.00,0.00,'borrowed',NULL),
(4,5,1,(SELECT id FROM book_copies WHERE copy_code='BK0001-01'),DATE_SUB(CURDATE(), INTERVAL 8 DAY),DATE_ADD(CURDATE(), INTERVAL 6 DAY),NULL,42.00,0.00,'borrowed',NULL),
(5,8,3,(SELECT id FROM book_copies WHERE copy_code='BK0003-01'),DATE_SUB(CURDATE(), INTERVAL 20 DAY),DATE_SUB(CURDATE(), INTERVAL 6 DAY),NULL,70.00,0.00,'borrowed',NULL),
(6,9,12,(SELECT id FROM book_copies WHERE copy_code='BK0012-01'),CURDATE(),DATE_ADD(CURDATE(), INTERVAL 7 DAY),NULL,35.00,0.00,'borrowed',NULL),
(7,6,2,(SELECT id FROM book_copies WHERE copy_code='BK0002-01'),DATE_SUB(CURDATE(), INTERVAL 40 DAY),DATE_SUB(CURDATE(), INTERVAL 33 DAY),DATE_SUB(CURDATE(), INTERVAL 33 DAY),21.00,0.00,'returned',NULL),
(8,7,5,(SELECT id FROM book_copies WHERE copy_code='BK0005-01'),DATE_SUB(CURDATE(), INTERVAL 35 DAY),DATE_SUB(CURDATE(), INTERVAL 28 DAY),DATE_SUB(CURDATE(), INTERVAL 24 DAY),28.00,40.00,'returned',NULL),
(9,10,8,(SELECT id FROM book_copies WHERE copy_code='BK0008-01'),DATE_SUB(CURDATE(), INTERVAL 30 DAY),DATE_SUB(CURDATE(), INTERVAL 23 DAY),DATE_SUB(CURDATE(), INTERVAL 20 DAY),56.00,30.00,'returned',DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
(10,5,9,(SELECT id FROM book_copies WHERE copy_code='BK0009-01'),DATE_SUB(CURDATE(), INTERVAL 25 DAY),DATE_SUB(CURDATE(), INTERVAL 18 DAY),DATE_SUB(CURDATE(), INTERVAL 18 DAY),56.00,0.00,'returned',NULL),
(11,8,10,(SELECT id FROM book_copies WHERE copy_code='BK0010-01'),DATE_SUB(CURDATE(), INTERVAL 50 DAY),DATE_SUB(CURDATE(), INTERVAL 43 DAY),DATE_SUB(CURDATE(), INTERVAL 40 DAY),42.00,30.00,'returned',DATE_SUB(CURDATE(), INTERVAL 38 DAY)),
(12,9,11,(SELECT id FROM book_copies WHERE copy_code='BK0011-01'),DATE_SUB(CURDATE(), INTERVAL 15 DAY),DATE_SUB(CURDATE(), INTERVAL 8 DAY),DATE_SUB(CURDATE(), INTERVAL 5 DAY),49.00,30.00,'returned',NULL);

INSERT INTO reservations (id, user_id, book_id, status, reserved_at) VALUES
(1,5,3,'pending',DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2,6,12,'pending',DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3,7,6,'ready',DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4,10,4,'claimed',DATE_SUB(NOW(), INTERVAL 12 DAY)),
(5,8,5,'cancelled',DATE_SUB(NOW(), INTERVAL 9 DAY)),
(6,9,7,'pending',DATE_SUB(NOW(), INTERVAL 0 DAY));
