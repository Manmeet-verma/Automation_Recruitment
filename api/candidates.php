<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDB();

switch ($method) {
    case 'GET':
        // Get candidates with filters
        $dept = $_GET['department'] ?? 'all';
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT * FROM candidates WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($dept !== 'all') {
            $sql .= " AND department = ?";
            $params[] = $dept;
            $types .= "s";
        }
        
        if ($status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        if ($search) {
            $sql .= " AND (full_name LIKE ? OR application_id LIKE ? OR email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }
        
        $sql .= " ORDER BY total_score DESC, created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $candidates = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['certificates'] = json_decode($row['certificates'], true);
            $row['exam_answers'] = json_decode($row['exam_answers'], true);
            $row['proctoring_logs'] = json_decode($row['proctoring_logs'], true);
            $candidates[] = $row;
        }
        
        jsonResponse(['candidates' => $candidates, 'count' => count($candidates)]);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'create';
        
        if ($action === 'create') {
            // Create new candidate (KYC step)
            $app_id = generateAppId();
            
            $stmt = $conn->prepare("INSERT INTO candidates (
                application_id, full_name, email, phone, dob, aadhaar, 
                address, city, state, department, position, location
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssssss",
                $app_id,
                sanitize($conn, $data['full_name']),
                sanitize($conn, $data['email']),
                sanitize($conn, $data['phone']),
                $data['dob'],
                sanitize($conn, $data['aadhaar']),
                sanitize($conn, $data['address']),
                sanitize($conn, $data['city']),
                sanitize($conn, $data['state']),
                sanitize($conn, $data['department']),
                sanitize($conn, $data['position']),
                sanitize($conn, $data['location'])
            );
            
            if ($stmt->execute()) {
                $id = $conn->insert_id;
                jsonResponse([
                    'success' => true,
                    'id' => $id,
                    'application_id' => $app_id,
                    'message' => 'Candidate registered successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to create candidate: ' . $stmt->error], 500);
            }
        }
        
        if ($action === 'update_scores') {
            $id = intval($data['id']);
            $entrance = intval($data['entrance_score']);
            $iq = intval($data['iq_score']);
            $final = intval($data['final_score']);
            $total = intval($data['total_score']);
            $answers = json_encode($data['exam_answers'] ?? []);
            
            $stmt = $conn->prepare("UPDATE candidates SET 
                entrance_score = ?, iq_score = ?, final_score = ?, 
                total_score = ?, exam_answers = ? WHERE id = ?");
            
            $stmt->bind_param("iiiisi", $entrance, $iq, $final, $total, $answers, $id);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'Scores updated']);
            } else {
                jsonResponse(['error' => 'Update failed'], 500);
            }
        }
        
        if ($action === 'update_status') {
            $id = intval($data['id']);
            $status = sanitize($conn, $data['status']);
            
            $stmt = $conn->prepare("UPDATE candidates SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'message' => 'Status updated to ' . $status]);
            } else {
                jsonResponse(['error' => 'Update failed'], 500);
            }
        }
        
        if ($action === 'update_documents') {
            $id = intval($data['id']);
            $cv = sanitize($conn, $data['cv_filename']);
            $certs = json_encode($data['certificates'] ?? []);
            
            $stmt = $conn->prepare("UPDATE candidates SET cv_filename = ?, certificates = ? WHERE id = ?");
            $stmt->bind_param("ssi", $cv, $certs, $id);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['error' => 'Update failed'], 500);
            }
        }
        break;
        
    case 'GET':
        // Get single candidate
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM candidates WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $candidate = $result->fetch_assoc();
                $candidate['certificates'] = json_decode($candidate['certificates'], true);
                $candidate['exam_answers'] = json_decode($candidate['exam_answers'], true);
                jsonResponse(['candidate' => $candidate]);
            } else {
                jsonResponse(['error' => 'Candidate not found'], 404);
            }
        }
        break;
        
    case 'DELETE':
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            jsonResponse(['success' => true, 'message' => 'Candidate deleted']);
        } else {
            jsonResponse(['error' => 'Delete failed'], 500);
        }
        break;
}

$conn->close();
?>