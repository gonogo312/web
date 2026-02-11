





USE `seb_exam`;







INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `full_name`) VALUES
('teacher1', 'teacher1@school.local', '$2y$10$YF1JkDq5eRkV0QGnCHrOa.9G3RzFGeKlAn3yVTMFbX0gAqDfS4jGi', 'teacher', 'Prof. Ivanov'),
('student1', 'student1@school.local', '$2y$10$jR4rXLhZ3wOqJGF.l4v5aeRn8N0bZiFb5k2cVdqH3IuXEpzTFfaVi', 'student', 'Maria Petrova'),
('student2', 'student2@school.local', '$2y$10$jR4rXLhZ3wOqJGF.l4v5aeRn8N0bZiFb5k2cVdqH3IuXEpzTFfaVi', 'student', 'Georgi Dimitrov');




INSERT INTO `exams` (`teacher_id`, `title`, `description`, `time_limit_min`, `passing_score`, `access_code`, `is_published`) VALUES
(1, 'Web Technologies Basics', 'A quiz covering HTML, CSS, JavaScript and HTTP fundamentals.', 20, 60.00, 'WEB2024', 1);

INSERT INTO `questions` (`exam_id`, `type`, `question_text`, `options_json`, `correct_answer`, `points`, `sort_order`) VALUES
(1, 'mcq', 'Which HTML tag is used to define an unordered list?', '["<ol>","<ul>","<li>","<dl>"]', '<ul>', 2.00, 1),
(1, 'mcq', 'What does CSS stand for?', '["Computer Style Sheets","Creative Style Sheets","Cascading Style Sheets","Colorful Style Sheets"]', 'Cascading Style Sheets', 2.00, 2),
(1, 'tf',  'JavaScript is a compiled language.', NULL, 'false', 2.00, 3),
(1, 'short', 'What HTTP status code indicates "Not Found"?', NULL, '404', 2.00, 4),
(1, 'mcq', 'Which property is used to change the background color in CSS?', '["color","bgcolor","background-color","background"]', 'background-color', 2.00, 5);




INSERT INTO `attempts` (`student_id`, `exam_id`, `started_at`, `finished_at`, `score`, `max_score`, `is_submitted`) VALUES
(2, 1, '2025-12-01 09:00:00', '2025-12-01 09:15:00', 6.00, 10.00, 1),
(2, 1, '2025-12-05 10:00:00', '2025-12-05 10:12:00', 8.00, 10.00, 1),
(3, 1, '2025-12-02 14:00:00', '2025-12-02 14:18:00', 4.00, 10.00, 1);

INSERT INTO `attempt_answers` (`attempt_id`, `question_id`, `student_answer`, `is_correct`, `points_awarded`) VALUES

(1, 1, '<ul>',  1, 2.00),
(1, 2, 'Cascading Style Sheets', 1, 2.00),
(1, 3, 'true',  0, 0.00),
(1, 4, '404',   1, 2.00),
(1, 5, 'color', 0, 0.00),

(2, 1, '<ul>',  1, 2.00),
(2, 2, 'Cascading Style Sheets', 1, 2.00),
(2, 3, 'false', 1, 2.00),
(2, 4, '404',   1, 2.00),
(2, 5, 'color', 0, 0.00),

(3, 1, '<ol>',  0, 0.00),
(3, 2, 'Cascading Style Sheets', 1, 2.00),
(3, 3, 'true',  0, 0.00),
(3, 4, '200',   0, 0.00),
(3, 5, 'background-color', 1, 2.00);




INSERT INTO `games` (`teacher_id`, `title`, `description`, `access_code`, `is_published`) VALUES
(1, 'The Lost Server Room', 'Find the server room key and restore the network before time runs out!', 'ESCAPE1', 1);

INSERT INTO `game_nodes` (`game_id`, `node_key`, `title`, `description`, `is_end_node`, `sort_order`) VALUES
(1, 'start',     'Reception Desk',     'You arrive at the IT building reception. The lights are flickering. There is a desk with a computer and a locked drawer.', 0, 1),
(1, 'computer',  'Check the Computer',  'The screen shows a password hint: "The year the company was founded + 42". A sticky note on the monitor says "Founded: 1980".', 0, 2),
(1, 'drawer',    'Open the Drawer',     'Inside the drawer you find a keycard and a network diagram. The diagram shows the server room is on floor B2.', 0, 3),
(1, 'server',    'Server Room',         'You use the keycard to enter the server room. You restart the main switch and the network comes back online. Congratulations!', 1, 4);


UPDATE `games` SET `start_node_id` = 1 WHERE `id` = 1;

INSERT INTO `game_choices` (`node_id`, `choice_text`, `target_node_id`, `sort_order`) VALUES
(1, 'Look at the computer screen', 2, 1),
(1, 'Try to open the drawer', 3, 2),
(2, 'Enter password 2022 (1980+42)', 3, 1),
(2, 'Go back to the desk', 1, 2),
(3, 'Take the keycard and go to B2', 4, 1),
(3, 'Go back to the desk', 1, 2);




