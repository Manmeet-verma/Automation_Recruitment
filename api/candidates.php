<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDB();

switch ($method) {
    case 'GET':
        // Get single candidate by ID
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM candidates WHERE id = ? OR application_id = ?");
            $stmt->bind_param("is", $id, $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $candidate = $result->fetch_assoc();
                $candidate = mapCandidateFields($candidate);
                jsonResponse(['candidate' => $candidate]);
            } else {
                jsonResponse(['error' => 'Candidate not found'], 404);
            }
            break;
        }
        
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
            $candidates[] = mapCandidateFields($row);
        }
        
        jsonResponse(['candidates' => $candidates, 'count' => count($candidates)]);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'create';
        
        if ($action === 'create') {
            $app_id = generateAppId();
            $documents = json_encode([
                'photo' => false, 'aadhar' => false, 'pan' => false, 'cv' => false,
                'disability' => false, 'service' => false
            ]);
            $specialCategories = json_encode($data['specialCategories'] ?? []);
            
            $stmt = $conn->prepare("INSERT INTO candidates (
                application_id, full_name, email, phone, dob, aadhaar, pan,
                address, city, state, department, position, location,
                entrance_score, iq_score, final_score, total_score, bonus_score,
                special_categories, documents, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $entrance = intval($data['score'] ?? 0);
            $iq = intval($data['iqScore'] ?? 0);
            $final = intval($data['score'] ?? 0);
            $total = intval($data['score'] ?? 0);
            $bonus = intval($data['bonus'] ?? 0);
            $status = $final >= 60 ? 'review' : 'rejected';
            
            $stmt->bind_param("ssssssssssssiiiiiisss",
                $app_id,
                sanitize($conn, $data['full_name']),
                sanitize($conn, $data['email']),
                sanitize($conn, $data['phone']),
                $data['dob'],
                sanitize($conn, $data['aadhaar']),
                sanitize($conn, $data['pan'] ?? ''),
                sanitize($conn, $data['address'] ?? ''),
                sanitize($conn, $data['city']),
                sanitize($conn, $data['state']),
                sanitize($conn, $data['department']),
                sanitize($conn, $data['position']),
                sanitize($conn, $data['location'] ?? ''),
                $entrance, $iq, $final, $total, $bonus,
                $specialCategories, $documents, $status
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
        
        if ($action === 'update_status') {
            $id = intval($data['id']);
            $status = sanitize($conn, $data['status']);
            
            $stmt = $conn->prepare("UPDATE candidates SET status = ? WHERE id = ? OR application_id = ?");
            $stmt->bind_param("sis", $status, $id, $id);
            
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
            
            $stmt = $conn->prepare("UPDATE candidates SET cv_filename = ?, certificates = ? WHERE id = ? OR application_id = ?");
            $stmt->bind_param("ssii", $cv, $certs, $id, $id);
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['error' => 'Update failed'], 500);
            }
        }
        break;
        
    case 'DELETE':
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ? OR application_id = ?");
        $stmt->bind_param("is", $id, $_GET['id']);
        
        if ($stmt->execute()) {
            jsonResponse(['success' => true, 'message' => 'Candidate deleted']);
        } else {
            jsonResponse(['error' => 'Delete failed'], 500);
        }
        break;
}

function mapCandidateFields($row) {
    return [
        'id' => $row['application_id'] ?? $row['id'],
        'name' => $row['full_name'] ?? '',
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'dob' => $row['dob'] ?? '',
        'aadhar' => $row['aadhaar'] ?? '',
        'pan' => $row['pan'] ?? '',
        'city' => $row['city'] ?? '',
        'state' => $row['state'] ?? '',
        'role' => $row['position'] ?? '',
        'roleType' => $row['department'] ?? '',
        'score' => intval($row['total_score'] ?? 0),
        'baseScore' => intval($row['entrance_score'] ?? 0),
        'iqScore' => intval($row['iq_score'] ?? 0),
        'bonus' => intval($row['bonus_score'] ?? 0),
        'special' => '',
        'specialCategories' => json_decode($row['special_categories'] ?? '[]', true),
        'status' => $row['status'] ?? 'pending',
        'documents' => json_decode($row['documents'] ?? '{}', true),
        'violations' => intval($row['violations'] ?? 0),
        'notes' => $row['notes'] ?? '',
        'appliedDate' => $row['applied_date'] ?? $row['created_at'] ?? date('Y-m-d H:i:s')
    ];
}

$conn->close();
?>