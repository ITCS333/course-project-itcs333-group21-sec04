<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "Database.php"; 

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);
$queryParams = $_GET;

function getStudents($db) {
    $search = $_GET['search'] ?? null;
    $sort   = $_GET['sort'] ?? 'name';
    $order  = $_GET['order'] ?? 'asc';

    $allowedSort = ['name', 'student_id', 'email'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSort)) $sort = 'name';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'asc';

    $sql = "SELECT id, student_id, name, email, created_at FROM students";
    if ($search) {
        $sql .= " WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search";
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    if ($search) {
        $stmt->bindValue(":search", "%$search%");
    }

    $stmt->execute();
    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getStudentById($db, $studentId) {
    $stmt = $db->prepare(
        "SELECT id, student_id, name, email, created_at 
         FROM students WHERE student_id = :student_id"
    );
    $stmt->bindParam(":student_id", $studentId);
    $stmt->execute();

    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        sendResponse(["success" => true, "data" => $student]);
    } else {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }
}

function createStudent($db, $data) {
    $required = ['student_id', 'name', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(["error" => "$field is required"], 400);
        }
    }

    $student_id = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];

    if (!validateEmail($email)) {
        sendResponse(["error" => "Invalid email format"], 400);
    }

    $check = $db->prepare(
        "SELECT id FROM students WHERE student_id = :sid OR email = :email"
    );
    $check->execute([':sid' => $student_id, ':email' => $email]);

    if ($check->rowCount() > 0) {
        sendResponse(["error" => "Student ID or Email already exists"], 409);
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        "INSERT INTO students (student_id, name, email, password)
         VALUES (:sid, :name, :email, :password)"
    );

    if ($stmt->execute([
        ':sid' => $student_id,
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashed
    ])) {
        sendResponse(["success" => true, "message" => "Student created"], 201);
    }

    sendResponse(["error" => "Failed to create student"], 500);
}

function updateStudent($db, $data) {
    if (empty($data['student_id'])) {
        sendResponse(["error" => "student_id is required"], 400);
    }

    $student_id = $data['student_id'];

    $stmt = $db->prepare("SELECT id FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $student_id]);
    if ($stmt->rowCount() === 0) {
        sendResponse(["error" => "Student not found"], 404);
    }

    $fields = [];
    $params = [':sid' => $student_id];

    if (!empty($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = sanitizeInput($data['name']);
    }

    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) {
            sendResponse(["error" => "Invalid email"], 400);
        }

        $check = $db->prepare(
            "SELECT id FROM students WHERE email = :email AND student_id != :sid"
        );
        $check->execute([':email' => $data['email'], ':sid' => $student_id]);
        if ($check->rowCount() > 0) {
            sendResponse(["error" => "Email already exists"], 409);
        }

        $fields[] = "email = :email";
        $params[':email'] = sanitizeInput($data['email']);
    }

    if (empty($fields)) {
        sendResponse(["error" => "No data to update"], 400);
    }

    $sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE student_id = :sid";
    $stmt = $db->prepare($sql);

    if ($stmt->execute($params)) {
        sendResponse(["success" => true, "message" => "Student updated"]);
    }

    sendResponse(["error" => "Update failed"], 500);
}

function deleteStudent($db, $studentId) {
    if (!$studentId) {
        sendResponse(["error" => "student_id required"], 400);
    }

    $stmt = $db->prepare("SELECT id FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $studentId]);

    if ($stmt->rowCount() === 0) {
        sendResponse(["error" => "Student not found"], 404);
    }

    $del = $db->prepare("DELETE FROM students WHERE student_id = :sid");
    if ($del->execute([':sid' => $studentId])) {
        sendResponse(["success" => true, "message" => "Student deleted"]);
    }

    sendResponse(["error" => "Delete failed"], 500);
}

function changePassword($db, $data) {
    foreach (['student_id','current_password','new_password'] as $f) {
        if (empty($data[$f])) {
            sendResponse(["error" => "$f required"], 400);
        }
    }

    if (strlen($data['new_password']) < 8) {
        sendResponse(["error" => "Password too short"], 400);
    }

    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $data['student_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($data['current_password'], $row['password'])) {
        sendResponse(["error" => "Invalid credentials"], 401);
    }

    $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $update = $db->prepare(
        "UPDATE students SET password = :pwd WHERE student_id = :sid"
    );

    if ($update->execute([':pwd' => $newHash, ':sid' => $data['student_id']])) {
        sendResponse(["success" => true, "message" => "Password changed"]);
    }

    sendResponse(["error" => "Password update failed"], 500);
}

try {
    if ($method === 'GET') {
        if (!empty($_GET['student_id'])) {
            getStudentById($db, $_GET['student_id']);
        } else {
            getStudents($db);
        }

    } elseif ($method === 'POST') {
        if (($_GET['action'] ?? '') === 'change_password') {
            changePassword($db, $input);
        } else {
            createStudent($db, $input);
        }

    } elseif ($method === 'PUT') {
        updateStudent($db, $input);

    } elseif ($method === 'DELETE') {
        $sid = $_GET['student_id'] ?? ($input['student_id'] ?? null);
        deleteStudent($db, $sid);

    } else {
        sendResponse(["error" => "Method not allowed"], 405);
    }

} catch (Exception $e) {
    sendResponse(["error" => "Server error"], 500);
}


function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
