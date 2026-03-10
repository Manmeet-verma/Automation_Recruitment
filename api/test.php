<?php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'techon_recruitment');

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database not found. Please create it first in phpMyAdmin.']);
} else {
    $result = $conn->query("SELECT COUNT(*) as total FROM candidates");
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'ok', 'candidates_count' => $row['total']]);
}

$conn->close();
?>
