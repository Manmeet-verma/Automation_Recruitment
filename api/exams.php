<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDB();

switch ($method) {
    case 'GET':
        $dept = $_GET['department'] ?? 'Technical';
        $type = $_GET['type'] ?? 'entrance';
        
        $stmt = $conn->prepare("SELECT * FROM questions 
            WHERE (department = ? OR department = 'All') 
            AND question_type = ? 
            ORDER BY RAND() 
            LIMIT ?");
        
        $limit = ($type === 'entrance') ? 20 : (($type === 'iq') ? 15 : 10);
        $stmt->bind_param("ssi", $dept, $type, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $row['options'] = json_decode($row['options'], true);
            unset($row['correct_answer']); // Don't send correct answers to frontend
            $questions[] = $row;
        }
        
        jsonResponse(['questions' => $questions, 'count' => count($questions)]);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if ($action === 'validate_answers') {
            // Validate exam answers and calculate score
            $answers = $data['answers'];
            $dept = sanitize($conn, $data['department']);
            $type = sanitize($conn, $data['type']);
            
            // Get correct answers
            $placeholders = implode(',', array_fill(0, count($answers), '?'));
            $stmt = $conn->prepare("SELECT id, correct_answer FROM questions 
                WHERE id IN ($placeholders) AND question_type = ?");
            
            $types = str_repeat('i', count($answers)) . 's';
            $params = array_merge(array_keys($answers), [$type]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $correct = 0;
            $total = 0;
            
            while ($row = $result->fetch_assoc()) {
                $total++;
                if (isset($answers[$row['id']]) && $answers[$row['id']] == $row['correct_answer']) {
                    $correct++;
                }
            }
            
            $percentage = $total > 0 ? round(($correct / $total) * 100) : 0;
            
            jsonResponse([
                'correct' => $correct,
                'total' => $total,
                'percentage' => $percentage,
                'passed' => $percentage >= 60
            ]);
        }
        
        if ($action === 'add_question') {
            // Admin: Add new question
            $stmt = $conn->prepare("INSERT INTO questions 
                (department, question_type, question_text, options, correct_answer) 
                VALUES (?, ?, ?, ?, ?)");
            
            $options = json_encode($data['options']);
            
            $stmt->bind_param("ssssi",
                sanitize($conn, $data['department']),
                sanitize($conn, $data['type']),
                sanitize($conn, $data['question']),
                $options,
                intval($data['correct'])
            );
            
            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'id' => $conn->insert_id]);
            } else {
                jsonResponse(['error' => 'Failed to add question'], 500);
            }
        }
        break;
}

$conn->close();
?>