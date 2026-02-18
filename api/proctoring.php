<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDB();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $candidateId = intval($data['candidate_id']);
    $violationType = sanitize($conn, $data['violation_type']);
    $screenshot = $data['screenshot'] ?? null; // Base64 image
    
    // Save screenshot if provided
    $screenshotPath = null;
    if ($screenshot) {
        $uploadDir = '../assets/uploads/proctoring/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = $candidateId . '_' . time() . '.png';
        $filepath = $uploadDir . $filename;
        
        // Remove base64 prefix and decode
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $screenshot));
        file_put_contents($filepath, $imageData);
        $screenshotPath = 'assets/uploads/proctoring/' . $filename;
    }
    
    // Log to database
    $stmt = $conn->prepare("INSERT INTO proctoring_logs (candidate_id, violation_type, screenshot_path) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $candidateId, $violationType, $screenshotPath);
    
    if ($stmt->execute()) {
        // Update candidate's proctoring logs JSON
        $stmt2 = $conn->prepare("SELECT proctoring_logs FROM candidates WHERE id = ?");
        $stmt2->bind_param("i", $candidateId);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $row = $result->fetch_assoc();
        
        $logs = json_decode($row['proctoring_logs'] ?? '[]', true);
        $logs[] = [
            'type' => $violationType,
            'time' => date('Y-m-d H:i:s'),
            'screenshot' => $screenshotPath
        ];
        
        $stmt3 = $conn->prepare("UPDATE candidates SET proctoring_logs = ? WHERE id = ?");
        $logsJson = json_encode($logs);
        $stmt3->bind_param("si", $logsJson, $candidateId);
        $stmt3->execute();
        
        jsonResponse([
            'success' => true,
            'violation_logged' => true,
            'warning_level' => count($logs) >= 2 ? 'critical' : 'warning'
        ]);
    } else {
        jsonResponse(['error' => 'Failed to log violation'], 500);
    }
}

if ($method === 'GET') {
    $candidateId = intval($_GET['candidate_id']);
    
    $stmt = $conn->prepare("SELECT * FROM proctoring_logs WHERE candidate_id = ? ORDER BY violation_time DESC");
    $stmt->bind_param("i", $candidateId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    jsonResponse(['logs' => $logs, 'count' => count($logs)]);
}

$conn->close();
?>