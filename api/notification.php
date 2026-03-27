<?php
require_once 'config.php';

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
        
        if (empty($candidateEmail) || empty($status)) {
            jsonResponse(['error' => 'Missing required fields (email or status)'], 400);
        }
        
        if (!in_array($status, ['selected', 'rejected'])) {
            jsonResponse(['error' => 'Invalid status. Only selected/rejected triggers email.'], 400);
        }
        
        // Send email
        $emailSent = sendStatusEmail($candidateEmail, $candidateName, $applicationId, $status);
        
        if ($emailSent) {
            jsonResponse([
                'success' => true,
                'message' => "Email sent to $candidateEmail"
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'error' => 'Failed to send email. Check PHP mail configuration.'
            ], 500);
        }
    }
}

jsonResponse(['error' => 'Invalid request'], 400);

