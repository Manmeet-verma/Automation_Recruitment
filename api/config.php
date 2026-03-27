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

// Email configuration (Update these with your SMTP details)
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USER', 'your-email@gmail.com'); // REPLACE with your Gmail address
define('EMAIL_PASS', 'your-app-password'); // REPLACE with Gmail App Password (16 chars)
define('EMAIL_FROM_NAME', 'Techon Recruitment');

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

// Send Email Function
function sendEmail($to, $subject, $body, $isHtml = true) {
    $headers = [
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_USER . '>',
        'Reply-To: ' . EMAIL_USER,
        'MIME-Version: 1.0',
        'Content-Type: text/' . ($isHtml ? 'html' : 'plain') . '; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.sendgrid.com/api/mail.send.json',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'api_user' => EMAIL_USER,
            'api_key' => EMAIL_PASS,
            'to' => $to,
            'subject' => $subject,
            'html' => $body,
            'from' => EMAIL_USER
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200 || $httpCode === 201;
}

// Simple mail fallback using PHP mail()
function sendStatusEmail($to, $name, $applicationId, $status) {
    $statusText = ucfirst($status);
    $isSelected = $status === 'selected';
    
    $subject = "Application Status Update - $applicationId";
    
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
                    <h3 style='margin:0;'>Application Status: $statusText</h3>
                    <p style='margin:10px 0 0 0;'>Application ID: <strong>$applicationId</strong></p>
                </div>
                " . ($isSelected ? 
                    "<p>Congratulations! We are pleased to inform you that your application has been <strong>selected</strong>. Our HR team will contact you shortly with further details regarding the next steps.</p>" :
                    "<p>We regret to inform you that your application has not been <strong>selected</strong> at this time. We encourage you to apply for future openings that match your profile.</p>
                    <p>We appreciate your interest in Techon LED and wish you all the best in your career.</p>"
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
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_USER . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    return mail($to, $subject, $body, $headers);
}
?>
