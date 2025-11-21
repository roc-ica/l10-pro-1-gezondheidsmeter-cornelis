START TRANSACTION;

-- ------------------------------
-- USERS (20 users)
-- ------------------------------
INSERT INTO users (username, email, password_hash, is_admin, display_name, birthdate, gender)
VALUES
('ali01','ali01@example.com','x1',0,'Ali Hasan','2004-05-12','male'),
('sara02','sara02@example.com','x2',0,'Sara Noor','2003-09-11','female'),
('tom03','tom03@example.com','x3',0,'Tom Jansen','1999-04-20','male'),
('lina04','lina04@example.com','x4',0,'Lina Verhoef','2001-08-14','female'),
('mark05','mark05@example.com','x5',0,'Mark Visser','1998-12-05','male'),
('hana06','hana06@example.com','x6',0,'Hana Elmi','2000-03-19','female'),
('yusuf07','yusuf07@example.com','x7',0,'Yusuf Idris','2002-06-09','male'),
('dina08','dina08@example.com','x8',0,'Dina Karim','2003-11-24','female'),
('jan09','jan09@example.com','x9',0,'Jan Peeters','1997-10-17','male'),
('emma10','emma10@example.com','x10',0,'Emma Vd Berg','2005-01-02','female'),
('user11','user11@example.com','x11',0,'User Eleven','1995-07-13','male'),
('user12','user12@example.com','x12',0,'User Twelve','1996-04-22','female'),
('user13','user13@example.com','x13',0,'User Thirteen','1990-09-09','male'),
('user14','user14@example.com','x14',0,'User Fourteen','1994-03-12','female'),
('user15','user15@example.com','x15',0,'User Fifteen','1992-12-01','male'),
('user16','user16@example.com','x16',0,'User Sixteen','2001-07-30','female'),
('user17','user17@example.com','x17',0,'User Seventeen','2000-11-11','male'),
('user18','user18@example.com','x18',0,'User Eighteen','1998-01-26','female'),
('user19','user19@example.com','x19',0,'User Nineteen','2003-05-07','male'),
('admin','admin@example.com','adminhash',1,'Super Admin','1990-01-01','male');

-- ------------------------------
-- DAILY ENTRIES (60 entries)
-- ------------------------------
INSERT INTO daily_entries (user_id, entry_date, submitted_at, notes)
VALUES
(1,'2025-11-01',NOW(),'Goed gegeten'),
(1,'2025-11-02',NOW(),'Weinig geslapen'),
(1,'2025-11-03',NOW(),'Goeie dag'),

(2,'2025-11-01',NOW(),'Normaal'),
(2,'2025-11-02',NOW(),'Moe'),
(2,'2025-11-03',NOW(),'Veel bewogen'),

(3,'2025-11-01',NOW(),'Prima'),
(3,'2025-11-02',NOW(),'Slecht geslapen'),
(3,'2025-11-03',NOW(),'Rustig'),

(4,'2025-11-01',NOW(),'Sportdag'),
(4,'2025-11-02',NOW(),'Niks bijzonders'),
(4,'2025-11-03',NOW(),'Prima'),

(5,'2025-11-01',NOW(),'Veel gewerkt'),
(5,'2025-11-02',NOW(),'Filmavond'),
(5,'2025-11-03',NOW(),'Gewandeld'),

(6,'2025-11-01',NOW(),'Energievol'),
(6,'2025-11-02',NOW(),'Gezond ontbeten'),
(6,'2025-11-03',NOW(),'Sport gedaan'),

(7,'2025-11-01',NOW(),'Rustig'),
(7,'2025-11-02',NOW(),'Te veel koffie'),
(7,'2025-11-03',NOW(),'Oké'),

(8,'2025-11-01',NOW(),'Koud weer'),
(8,'2025-11-02',NOW(),'Wandeling gemaakt'),
(8,'2025-11-03',NOW(),'Thuis gebleven'),

(9,'2025-11-01',NOW(),'Prima'),
(9,'2025-11-02',NOW(),'Gedanst'),
(9,'2025-11-03',NOW(),'Goeie dag'),

(10,'2025-11-01',NOW(),'Veel gelachen'),
(10,'2025-11-02',NOW(),'Bezoek gehad'),
(10,'2025-11-03',NOW(),'Vroeg slapen');

-- ------------------------------
-- ANSWERS (300 answers total)
-- mỗi entry krijgt antwoorden voor 6 questions
-- ------------------------------
INSERT INTO answers (entry_id, question_id, answer_text, score)
SELECT e.id,
       q.id,
       FLOOR(1 + RAND()*5),
       FLOOR(1 + RAND()*3)
FROM daily_entries e
CROSS JOIN questions q
WHERE q.id <= 6;

-- ------------------------------
-- DEVICES (25)
-- ------------------------------
INSERT INTO devices (user_id, provider, device_identifier, description, connected_at)
VALUES
(1,'Fitbit','FIT-001','Tracker',NOW()),
(2,'Garmin','GAR-002','Watch',NOW()),
(3,'Apple','APL-003','Apple Watch',NOW()),
(4,'Xiaomi','XM-004','Mi Band',NOW()),
(5,'Samsung','SAM-005','Galaxy Fit',NOW()),
(6,'Fitbit','FIT-006','Tracker',NOW()),
(7,'Garmin','GAR-007','Watch',NOW()),
(8,'Apple','APL-008','Apple Watch',NOW()),
(9,'Xiaomi','XM-009','Mi Band',NOW()),
(10,'Samsung','SAM-010','Galaxy Fit',NOW()),
(11,'Fitbit','FIT-011','Tracker',NOW()),
(12,'Garmin','GAR-012','Watch',NOW()),
(13,'Apple','APL-013','Apple Watch',NOW()),
(14,'Xiaomi','XM-014','Mi Band',NOW()),
(15,'Samsung','SAM-015','Galaxy Fit',NOW()),
(16,'Fitbit','FIT-016','Tracker',NOW()),
(17,'Garmin','GAR-017','Watch',NOW()),
(18,'Apple','APL-018','Apple Watch',NOW()),
(19,'Xiaomi','XM-019','Mi Band',NOW()),
(20,'Samsung','SAM-020','Galaxy Fit',NOW()),
(1,'Fitbit','FIT-021','Extra device',NOW()),
(2,'Garmin','GAR-022','Extra device',NOW()),
(3,'Apple','APL-023','Extra device',NOW()),
(4,'Xiaomi','XM-024','Extra device',NOW()),
(5,'Samsung','SAM-025','Extra device',NOW());

-- ------------------------------
-- DEVICE METRICS (120)
-- ------------------------------
INSERT INTO device_metrics (device_id, metric_type, metric_value, captured_at)
SELECT id,
       'steps',
       FLOOR(3000 + RAND()*9000),
       NOW()
FROM devices;

INSERT INTO device_metrics (device_id, metric_type, metric_value, captured_at)
SELECT id,
       'heart_rate',
       FLOOR(55 + RAND()*60),
       NOW()
FROM devices;

-- ------------------------------
-- CHALLENGES (10)
-- ------------------------------
INSERT INTO challenges (name, description, active)
VALUES
('10k stappen','Elke dag 10.000 stappen',1),
('Gezonde voeding','Eet gezonder',1),
('Mindfulness','10 minuten ontspanning',1),
('Geen suiker','7 dagen zonder suiker',1),
('Sportweek','Elke dag sporten',1),
('Water challenge','Minimaal 6 glazen water',1),
('Sleep boost','Minimaal 7 uur slaap',1),
('Screen time beperking','< 2 uur extra',1),
('Stop roken','Rookvrije week',1),
('Focus challenge','Dagelijks planning',1);

-- ------------------------------
-- USER CHALLENGES (samples)
-- ------------------------------
INSERT INTO user_challenges (user_id, challenge_id, progress)
VALUES
(1,1,'{"days":3}'),
(2,2,'{"days":1}'),
(3,3,'{"days":4}');

-- ------------------------------
-- NOTIFICATIONS
-- ------------------------------
INSERT INTO notifications (user_id, notif_type, payload, is_sent, scheduled_at)
VALUES
(1,'reminder','{"msg":"Check je gezondheid"}',0,NOW()),
(2,'warning','{"msg":"Te weinig slaap"}',1,NOW());

-- ------------------------------
-- WEEKLY ANALYSIS
-- ------------------------------
INSERT INTO weekly_analysis (user_id, week_start, week_end, summary)
VALUES
(1,'2025-11-01','2025-11-07','{"score":7}'),
(2,'2025-11-01','2025-11-07','{"score":5}');

-- ------------------------------
-- MONTHLY ANALYSIS
-- ------------------------------
INSERT INTO monthly_analysis (user_id, month_start, month_end, summary)
VALUES
(1,'2025-11-01','2025-11-30','{"trend":"positief"}'),
(2,'2025-11-01','2025-11-30','{"trend":"stabiel"}');

-- ------------------------------
-- ADMIN ACTIONS
-- ------------------------------
INSERT INTO admin_actions (admin_user_id, action_type, target_table, target_id, details)
VALUES
(20,'reset','users','1','{"reason":"test"}');

COMMIT;
