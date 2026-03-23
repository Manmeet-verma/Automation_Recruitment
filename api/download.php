<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain');
    exit('Method not allowed');
}

$candidateId = $_GET['candidate_id'] ?? '';
$docType = $_GET['type'] ?? '';

if (empty($candidateId) || empty($docType)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    exit('Missing parameters');
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'techon_recruitment');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: text/plain');
    exit('Database connection failed');
}
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("SELECT id, full_name, cv_filename FROM candidates WHERE id = ? OR application_id = ?");
$stmt->bind_param("is", $candidateId, $candidateId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(404);
    header('Content-Type: text/plain');
    $conn->close();
    exit('Candidate not found');
}

$candidate = $result->fetch_assoc();
$conn->close();

$uploadDir = __DIR__ . '/../assets/uploads/';

if ($docType === 'cv' && !empty($candidate['cv_filename'])) {
    $filePath = $uploadDir . $candidate['cv_filename'];
    
    if (file_exists($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        $downloadName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $candidate['full_name']) . '_CV.' . $ext;
        
        header_remove('Content-Security-Policy');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }
}

http_response_code(404);
header('Content-Type: text/plain');
exit('File not found');
?>
