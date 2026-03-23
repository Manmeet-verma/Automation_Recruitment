<?php
require_once 'config.php';

// Create upload directory if not exists
$uploadDir = '../assets/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'cv';
    $candidateId = intval($_POST['candidate_id']);
    
    if (!isset($_FILES['file'])) {
        jsonResponse(['error' => 'No file uploaded'], 400);
    }
    
    $file = $_FILES['file'];
    $allowedTypes = [
        'cv' => ['pdf', 'doc', 'docx'],
        'certificate' => ['pdf', 'jpg', 'jpeg', 'png'],
        'screenshot' => ['png', 'jpg']
    ];
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedTypes[$type] ?? [])) {
        jsonResponse(['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes[$type])], 400);
    }
    
    if ($file['size'] > $maxSize) {
        jsonResponse(['error' => 'File too large. Max 5MB'], 400);
    }
    
    // Generate unique filename
    $newName = $candidateId . '_' . $type . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $conn = getDB();
        
        if ($type === 'cv') {
            $stmt = $conn->prepare("UPDATE candidates SET cv_filename = ? WHERE id = ?");
            $stmt->bind_param("si", $newName, $candidateId);
            $stmt->execute();
        }
        
        $stmt = $conn->prepare("SELECT documents FROM candidates WHERE id = ?");
        $stmt->bind_param("i", $candidateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $documents = json_decode($row['documents'] ?? '{}', true);
        $docMap = ['cv' => 'cv'];
        if (isset($docMap[$type])) {
            $documents[$docMap[$type]] = true;
            $stmt = $conn->prepare("UPDATE candidates SET documents = ? WHERE id = ?");
            $stmt->bind_param("si", json_encode($documents), $candidateId);
            $stmt->execute();
        }
        
        $conn->close();
        
        jsonResponse([
            'success' => true,
            'filename' => $newName,
            'path' => 'assets/uploads/' . $newName
        ]);
    } else {
        jsonResponse(['error' => 'Failed to save file'], 500);
    }
}

jsonResponse(['error' => 'Invalid request'], 400);
?>