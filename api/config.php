<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Your MySQL password (empty for default XAMPP)
define('DB_NAME', 'techon_recruitment');

// Email configuration (Using SendGrid API - Free tier: 100 emails/day)
// Sign up at https://sendgrid.com and get a free API key
define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY'); // Replace with your SendGrid API key
define('EMAIL_FROM_NAME', 'Techon Recruitment');
define('EMAIL_FROM', 'your-email@example.com'); // Replace with your verified sender email

// Create connection
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Input sanitization
function sanitize($conn, $input) {
    if (is_array($input)) {
        return array_map(function($item) use ($conn) {
            return sanitize($conn, $item);
        }, $input);
    }
    return htmlspecialchars(strip_tags($conn->real_escape_string($input)));
}

// Generate Application ID
function generateAppId() {
    return 'TECH-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function sendStatusEmail($to, $name, $applicationId, $status) {
    $isSelected = $status === 'selected';
    
    $subject = $isSelected ? "Congratulations! You are Selected - $applicationId" : "Application Status Update - $applicationId";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0b2a5b 0%, #1a4a8a 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
            .status-box { padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0; }
            .selected { background: #d4edda; border: 2px solid #28a745; color: #155724; }
            .rejected { background: #f8d7da; border: 2px solid #dc3545; color: #721c24; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0;'>NexusRecruit</h1>
                <p style='margin:5px 0 0 0;'>Techon LED Recruitment Portal</p>
            </div>
            <div class='content'>
                <h2>Dear $name,</h2>
                <p>Thank you for applying through our recruitment portal. We have reviewed your application.</p>
                <div class='status-box " . ($isSelected ? 'selected' : 'rejected') . "'>
                    <h3 style='margin:0;'>" . ($isSelected ? 'YOU ARE SELECTED!' : 'APPLICATION NOT SELECTED') . "</h3>
                    <p style='margin:10px 0 0 0;'>Application ID: <strong>$applicationId</strong></p>
                </div>
                " . ($isSelected ? 
                    "<p><strong>Why You Are Selected:</strong></p>
                    <p>Your application score is good and you have qualified for the position. We are pleased to inform you that you are <strong>SELECTED</strong>.</p>
                    <div style='background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                        <p style='margin:0;'><strong>Joining Details:</strong></p>
                        <p style='margin:5px 0 0 0;'>Your joining date is <strong>tomorrow</strong>.</p>
                        <p style='margin:5px 0 0 0;'>Before joining, please note that your <strong>final interview will be conducted by HR Sir</strong>.</p>
                    </div>
                    <p>Our HR team will contact you shortly with more details regarding documentation and other formalities.</p>
                    <p>Please be available for the HR interview at the scheduled time.</p>" :
                    "<p><strong>Why You Are Not Selected:</strong></p>
                    <p>After careful review of your application, we regret to inform you that your profile did not meet the current requirements for this position.</p>
                    <p>Your score was not sufficient to qualify for this round. We encourage you to:</p>
                    <ul>
                        <li>Apply for future openings that match your profile</li>
                        <li>Work on improving your skills and qualifications</li>
                        <li>Keep checking our career portal for new opportunities</li>
                    </ul>
                    <p>We appreciate your interest in Techon LED and wish you all the best in your career journey.</p>"
                ) . "
                <p>If you have any questions, please don't hesitate to contact our HR department.</p>
                <p>Best regards,<br><strong>Techon LED Recruitment Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; " . date('Y') . " Techon LED. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return sendSendGridEmail($to, $subject, $body);
}

function sendSendGridEmail($to, $subject, $body) {
    $apiKey = SENDGRID_API_KEY;
    $fromEmail = EMAIL_FROM;
    $fromName = EMAIL_FROM_NAME;
    
    if ($apiKey === 'YOUR_SENDGRID_API_KEY' || empty($apiKey)) {
        return 'SendGrid API key not configured. Please update api/config.php with your SendGrid API key.';
    }
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]],
                'subject' => $subject
            ]
        ],
        'from' => ['email' => $fromEmail, 'name' => $fromName],
        'content' => [
            [
                'type' => 'text/html',
                'value' => $body
            ]
        ]
    ];
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    }
    
    return "SendGrid error: HTTP $httpCode - $response";
}
?>
