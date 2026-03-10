<?php
header('Content-Type: application/json');

$conn = @new mysqli('localhost', 'root', '', 'techon_recruitment');

if ($conn->connect_error) {
    echo json_encode(['error' => 'Cannot connect to database']);
    exit;
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['error' => 'No data received', 'received' => $input]);
    exit;
}

$name = $data['full_name'] ?? 'Test';
$email = $data['email'] ?? 'test@test.com';
$phone = $data['phone'] ?? '1234567890';
$app_id = 'TEST-' . time();

$sql = "INSERT INTO candidates (application_id, full_name, email, phone, status) VALUES ('$app_id', '$name', '$email', '$phone', 'pending')";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Candidate added: ' . $name]);
} else {
    echo json_encode(['error' => $conn->error]);
}

$conn->close();
?>
