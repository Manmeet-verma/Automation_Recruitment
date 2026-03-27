<?php
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'send_status_email') {
        $candidateId = $data['candidateId'] ?? '';
        $candidateName = $data['candidateName'] ?? '';
        $candidateEmail = $data['candidateEmail'] ?? '';
        $status = $data['status'] ?? '';
        $applicationId = $data['applicationId'] ?? $candidateId;
        
        if (empty($candidateEmail)) {
            jsonResponse(['error' => 'No email address provided'], 400);
        }
        
        if (empty($status)) {
            jsonResponse(['error' => 'No status selected'], 400);
        }
        
        if (!in_array($status, ['selected', 'rejected'])) {
            jsonResponse(['error' => 'Invalid status. Must be selected or rejected'], 400);
        }
        
        $result = sendStatusEmail($candidateEmail, $candidateName, $applicationId, $status);
        
        if ($result === true) {
            jsonResponse([
                'success' => true,
                'message' => "Email sent to $candidateEmail"
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'error' => $result
            ], 500);
        }
    }
    
    if ($action === 'test_email') {
        $result = sendStatusEmail('test@example.com', 'Test User', 'TEST-001', 'selected');
        jsonResponse([
            'success' => $result,
            'message' => $result ? 'Test email sent!' : 'Test email failed'
        ]);
    }
}

jsonResponse(['error' => 'Invalid request'], 400);

