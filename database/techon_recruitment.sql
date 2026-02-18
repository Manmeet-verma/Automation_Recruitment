-- Create Database
CREATE DATABASE techon_recruitment;
USE techon_recruitment;

-- Users/Admins Table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates Table
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE,
    aadhaar VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    department VARCHAR(50),
    position VARCHAR(100),
    location VARCHAR(100),
    cv_filename VARCHAR(255),
    certificates JSON,
    entrance_score INT DEFAULT 0,
    iq_score INT DEFAULT 0,
    final_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    status ENUM('pending', 'selected', 'rejected') DEFAULT 'pending',
    exam_answers JSON,
    proctoring_logs JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Proctoring Logs Table
CREATE TABLE proctoring_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT,
    violation_type VARCHAR(100),
    violation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    screenshot_path VARCHAR(255),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id)
);

-- Exam Questions Table
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(50),
    question_type ENUM('entrance', 'iq', 'final'),
    question_text TEXT,
    options JSON,
    correct_answer INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO admins (admin_id, password_hash, name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin@techon.in');
-- Password: password (change in production)

-- Insert Sample Questions for Technical Department
INSERT INTO questions (department, question_type, question_text, options, correct_answer) VALUES
('Technical', 'entrance', 'What does LED stand for?', '["Light Emitting Diode", "Light Energy Device", "Liquid Emitting Display", "Laser Emitting Diode"]', 0),
('Technical', 'entrance', 'Which protocol is commonly used for LED display control?', '["HTTP", "FTP", "DMX512", "SMTP"]', 2),
('Technical', 'entrance', 'What is the typical voltage for a single LED?', '["1.8-3.3V", "5V", "12V", "220V"]', 0),
('Technical', 'entrance', 'Which component controls brightness in LED displays?', '["Resistor", "PWM Controller", "Capacitor", "Transformer"]', 1),
('Technical', 'entrance', 'What does RGB stand for?', '["Red Green Blue", "Red Gold Black", "Random Generated Brightness", "None"]', 0);

-- Insert IQ Questions
INSERT INTO questions (department, question_type, question_text, options, correct_answer) VALUES
('All', 'iq', 'Complete the series: 2, 6, 12, 20, 30, ?', '["42", "40", "36", "44"]', 0),
('All', 'iq', 'If A=1, B=2, C=3, what is the value of WORD?', '["60", "58", "62", "64"]', 0),
('All', 'iq', 'Which shape has the most sides?', '["Hexagon", "Pentagon", "Octagon", "Decagon"]', 3),
('All', 'iq', 'Find the odd one out:', '["Mars", "Venus", "Sun", "Jupiter"]', 2),
('All', 'iq', 'Complete: 1, 1, 2, 3, 5, 8, ?', '["13", "12", "11", "14"]', 0);

-- Insert Final Assessment Questions
INSERT INTO questions (department, question_type, question_text, options, correct_answer) VALUES
('All', 'final', 'Describe how you handle tight deadlines and work pressure.', '[]', -1),
('All', 'final', 'Why do you want to join TECH ON specifically?', '[]', -1),
('All', 'final', 'Where do you see yourself in 5 years?', '[]', -1),
('All', 'final', 'How do you handle conflict with team members?', '[]', -1);