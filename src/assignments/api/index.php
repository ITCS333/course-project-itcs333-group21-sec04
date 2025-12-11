<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'Database.php';

$database = new Database();
$db = $database->getConnection();

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method = $_SERVER['REQUEST_METHOD'];

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = [];
}

$queryParams = $_GET;

function getAllAssignments($db) {
    $sql = "SELECT * FROM assignments WHERE 1=1";
    $params = [];
    
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    
    $sort = 'created_at';
    $order = 'asc';

    if (isset($_GET['sort']) && validateAllowedValue($_GET['sort'], ['title', 'due_date', 'created_at'])) {
        $sort = $_GET['sort'];
    }

    if (isset($_GET['order']) && validateAllowedValue(strtolower($_GET['order']), ['asc', 'desc'])) {
        $order = strtolower($_GET['order']);
    }

    $sql .= " ORDER BY {$sort} {$order}";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($assignments as $i => $row) {
        $assignments[$i]['files'] = json_decode($row['files'], true);
    }
    
    sendResponse($assignments, 200);
}


function getAssignmentById($db, $assignmentId) {
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
    }
    $sql = "SELECT * FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$assignment) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }
   $assignment['files'] = json_decode($assignment['files'], true);
    sendResponse($assignment, 200);
}


function createAssignment($db, $data) {
    if (!isset($data['title']) || !isset($data['description']) || !isset($data['due_date'])) {
        sendResponse(['error' => 'title, description, and due_date are required'], 400);
    }
    $title       = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $due_date     = sanitizeInput($data['due_date']);
    if (!validateDate($due_date)) {
        sendResponse(['error' => 'Invalid due_date format (expected YYYY-MM-DD)'], 400);
    }
    $filesJson = null;
    if (isset($data['files'])) {
        $filesJson = json_encode($data['files']);
    }
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':due_date', $due_date, PDO::PARAM_STR);
    $stmt->bindValue(':files', $filesJson, PDO::PARAM_STR);
    $ok = $stmt->execute();
    $newId = $db->lastInsertId();
    if (!$ok) {
        sendResponse(['error' => 'Failed to create assignment'], 500);
    }
    $createdAssignment = [
        'id'          => $newId,
        'title'       => $title,
        'description' => $description,
        'due_date'    => $due_date,
        'files'       => $filesJson ? json_decode($filesJson, true) : null
    ];

    sendResponse($createdAssignment, 201);
    
}


function updateAssignment($db, $data) {
    if (!isset($data['id'])) {
        sendResponse(['error' => 'Assignment ID (id) is required'], 400);
    }
    $id = $data['id'];
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $id);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }
    $setParts = [];
    $params = [':id' => $id];
    if (array_key_exists('title', $data)) {
        $setParts[] = "title = :title";
        $params[':title'] = $data['title'];
    }
    if (array_key_exists('description', $data)) {
        $setParts[] = "description = :description";
        $params[':description'] = $data['description'];
    }
    if (array_key_exists('due_date', $data)) {
        $setParts[] = "due_date = :due_date";
        $params[':due_date'] = $data['due_date'];
    }
    if (array_key_exists('files', $data)) {
        $setParts[] = "files = :files";
        $params[':files'] = json_encode($data['files']);
    }
    if (empty($setParts)) {
        sendResponse(['error' => 'No fields provided to update'], 400);
    }
    $setClause = implode(', ', $setParts) . ', updated_at = NOW()';
    $sql = "UPDATE assignments SET {$setClause} WHERE id = :id";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $rows = $stmt->rowCount();
    if ($rows === 0) {
        sendResponse(['message' => 'No changes made'], 200);
    }

    sendResponse(['message' => 'Assignment updated successfully'], 200);
}


function deleteAssignment($db, $assignmentId) {
    if (!$assignmentId) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
    }
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignmentId);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }
    $delComments = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $delComments->bindValue(':id', $assignmentId);
    $delComments->execute();
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
     sendResponse(['error' => 'Failed to delete assignment'], 500);
    }

    sendResponse(['message' => 'Assignment deleted successfully'], 200);
}


function getCommentsByAssignment($db, $assignmentId) {
    if (!$assignmentId) {
        sendResponse(['error' => 'assignment_id is required'], 400);
    }
    $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at ASC");
    $stmt->bindValue(':assignment_id', $assignmentId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse($comments, 200);
}


function createComment($db, $data) {
    if (!isset($data['assignment_id']) || !isset($data['author']) || !isset($data['text'])) {
        sendResponse(['error' => 'assignment_id, author, and text are required'], 400);
    }
    $assignmentId = sanitizeInput($data['assignment_id']);
    $author       = sanitizeInput($data['author']);
    $text         = sanitizeInput($data['text']);
    if (trim($text) === '') {
        sendResponse(['error' => 'Comment text cannot be empty'], 400);
    }
    $checkStmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $checkStmt->bindValue(':id', $assignmentId);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Assignment not found'], 404);
    }
    $stmt = $db->prepare(
        "INSERT INTO comments (assignment_id, author, text, created_at)
         VALUES (:assignment_id, :author, :text, NOW())"
    );
    $stmt->bindValue(':assignment_id', $assignmentId);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);
    $stmt->execute();
    $newId = $db->lastInsertId();
    $createdComment = [
        'id'            => $newId,
        'assignment_id' => $assignmentId,
        'author'        => $author,
        'text'          => $text
    ];

    sendResponse($createdComment, 201);
}

function deleteComment($db, $commentId) {
    if (!$commentId) {
        sendResponse(['error' => 'Comment ID is required'], 400);
    }
    $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $checkStmt->bindValue(':id', $commentId);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Comment not found'], 404);
    }
     $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->bindValue(':id', $commentId);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        sendResponse(['error' => 'Failed to delete comment'], 500);
    }
    sendResponse(['message' => 'Comment deleted successfully'], 200);

}


try {
    $resource = isset($_GET['resource']) ? $_GET['resource'] : null;
    if ($method === 'GET') {
        if ($resource === 'assignments') {
            if (isset($_GET['id'])) {
                getAssignmentById($db, $_GET['id']);
            } else {
                getAllAssignments($db);
            }
        } elseif ($resource === 'comments') {
            if (isset($_GET['assignment_id'])) {
                getCommentsByAssignment($db, $_GET['assignment_id']);
            } else {
                sendResponse(['error' => 'assignment_id is required'], 400);
            }
        } else {
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } elseif ($method === 'POST') {
        if ($resource === 'assignments') {
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            createComment($db, $data);
        } else {
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } elseif ($method === 'PUT') {
        if ($resource === 'assignments') {
            updateAssignment($db, $data);
        } else {
            sendResponse(['error' => 'PUT not supported for this resource'], 405);
        }
        
    } elseif ($method === 'DELETE') {
        if ($resource === 'assignments') {
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($data['id']) ? $data['id'] : null);
            deleteAssignment($db, $id);
        } elseif ($resource === 'comments') {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            deleteComment($db, $id);
        } else {
            sendResponse(['error' => 'Invalid resource'], 400);
        }
        
    } else {
        sendResponse(['error' => 'Method not supported'], 405);
    }
    
} catch (PDOException $e) {
    sendResponse(['error' => 'Database error', 500]);
} catch (Exception $e) {
    sendResponse(['error' => 'Server error', 500]);
    
}


function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (!is_array($data)) {
        $data = (array) $data;
    }
    echo json_encode($data);
    exit;
}



function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}



function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


function validateAllowedValue($value, $allowedValues) {
    $result = in_array($value, $allowedValues, true);
    return $result;
}
?>
