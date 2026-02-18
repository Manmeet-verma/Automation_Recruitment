<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $conn = getDB();
    
    $action = $data['action'] ?? '';
    
    // Admin Login
    if ($action === 'admin_login') {
        $admin_id = sanitize($conn, $data['admin_id']);
        $password = $data['password'];
        
        $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password_hash'])) {
                // Generate session token
                $token = bin2hex(random_bytes(32));
                
                jsonResponse([
                    'success' => true,
                    'token' => $token,
                    'admin' => [
                        'id' => $admin['id'],
                        'name' => $admin['name'],
                        'email' => $admin['email']
                    ]
                ]);
            }
        }
        
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    // Verify Token (for protected routes)
    if ($action === 'verify_token') {
        $token = sanitize($conn, $data['token']);
        // In production, verify against stored tokens
        jsonResponse(['valid' => true]);
    }
    
    $conn->close();
}

jsonResponse(['error' => 'Invalid request'], 400);
?>