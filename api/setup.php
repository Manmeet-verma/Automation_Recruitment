<?php
require_once 'config.php';

$conn = getDB();

$sql = "CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    dob DATE,
    aadhaar VARCHAR(20),
    pan VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    department VARCHAR(50),
    position VARCHAR(100),
    location VARCHAR(100),
    entrance_score INT DEFAULT 0,
    iq_score INT DEFAULT 0,
    final_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    bonus_score INT DEFAULT 0,
    special_categories JSON,
    cv_filename VARCHAR(255),
    certificates JSON,
    exam_answers JSON,
    proctoring_logs JSON,
    documents JSON,
    violations INT DEFAULT 0,
    notes TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Table created successfully']);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>
