<?php
session_start();

$_SESSION['initialized'] = true;

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents("php://input"), true) ?: [];

$action      = $_GET['action'] ?? null;
$id          = $_GET['id'] ?? null;
$resourceId  = $_GET['resource_id'] ?? null;
$commentId   = $_GET['comment_id'] ?? null;

function getAllResources($db) {
    $search = $_GET['search'] ?? null;
    $sort   = $_GET['sort'] ?? 'created_at';
    $order  = strtolower($_GET['order'] ?? 'desc');

    $allowedSort = ['title', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array($order, $allowedOrder)) $order = 'desc';

    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    if ($search) {
        $sql .= " WHERE title LIKE :s OR description LIKE :s";
        $params[':s'] = "%$search%";
    }

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["success" => true, "data" => $data]);
}

function getResourceById($db, $resourceId) {
    if (!is_numeric($resourceId)) sendResponse(["success" => false, "message" => "Invalid ID"], 400);

    $stmt = $db->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) sendResponse(["success" => false, "message" => "Resource not found"], 404);

    sendResponse(["success" => true, "data" => $row]);
}

function createResource($db, $data) {
    $req = validateRequiredFields($data, ['title', 'link']);
    if (!$req['valid']) sendResponse(["success" => false, "message" => "Missing fields", "missing" => $req['missing']], 400);

    $title = sanitizeInput($data['title']);
    $desc  = sanitizeInput($data['description'] ?? "");
    $link  = sanitizeInput($data['link']);

    if (!validateUrl($link)) sendResponse(["success" => false, "message" => "Invalid URL"], 400);

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    $ok = $stmt->execute([$title, $desc, $link]);

    if ($ok) {
        sendResponse([
            "success" => true,
            "message" => "Resource created",
            "id" => $db->lastInsertId()
        ], 201);
    }

    sendResponse(["success" => false, "message" => "DB insert failed"], 500);
}

function updateResource($db, $data) {
    if (empty($data['id'])) sendResponse(["success" => false, "message" => "ID required"], 400);
    $id = $data['id'];
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) sendResponse(["success" => false, "message" => "Resource not found"], 404);
    $fields = [];
    $vals   = [];
    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $vals[] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $vals[] = sanitizeInput($data['description']);
    }
    if (isset($data['link'])) {
        if (!validateUrl($data['link'])) sendResponse(["success" => false, "message" => "Invalid URL"], 400);
        $fields[] = "link = ?";
        $vals[] = sanitizeInput($data['link']);
    }
    if (!$fields) sendResponse(["success" => false, "message" => "Nothing to update"], 400);
    $vals[] = $id;
    $sql = "UPDATE resources SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($vals);
    sendResponse(["success" => true, "message" => "Resource updated"]);
}
function deleteResource($db, $resourceId) {
    if (!is_numeric($resourceId)) sendResponse(["success" => false, "message" => "Invalid ID"], 400);

    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$resourceId]);
    if (!$check->fetch()) sendResponse(["success" => false, "message" => "Resource not found"], 404);

    try {
        $db->beginTransaction();

        $delComments = $db->prepare("DELETE FROM comments WHERE resource_id = ?");
        $delComments->execute([$resourceId]);

        $delResource = $db->prepare("DELETE FROM resources WHERE id = ?");
        $delResource->execute([$resourceId]);

        $db->commit();

        sendResponse(["success" => true, "message" => "Resource & comments deleted"]);

    } catch (PDOException $e) {
    $db->rollBack();
    sendResponse([
        "success" => false,
        "message" => "Delete failed"
    ], 500);
}

}
function getCommentsByResourceId($db, $resourceId) {
    if (!is_numeric($resourceId)) sendResponse(["success" => false, "message" => "Invalid resource_id"], 400);

    $stmt = $db->prepare("SELECT * FROM comments WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["success" => true, "data" => $rows]);
}
function createComment($db, $data) {
    $req = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$req['valid']) sendResponse(["success" => false, "missing" => $req['missing']], 400);

    if (!is_numeric($data['resource_id'])) sendResponse(["success" => false, "message" => "Invalid resource_id"], 400);
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['resource_id']]);
    if (!$check->fetch()) sendResponse(["success" => false, "message" => "Resource not found"], 404);
    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);
    $stmt = $db->prepare("INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)");
    $ok = $stmt->execute([$data['resource_id'], $author, $text]);
    if ($ok) {
        sendResponse(["success" => true, "message" => "Comment added", "id" => $db->lastInsertId()], 201);
    }

    sendResponse(["success" => false, "message" => "DB insert failed"], 500);
}
function deleteComment($db, $commentId) {
    if (!is_numeric($commentId)) sendResponse(["success" => false, "message" => "Invalid ID"], 400);

    $check = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $check->execute([$commentId]);
    if (!$check->fetch()) sendResponse(["success" => false, "message" => "Not found"], 404);

    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);

    sendResponse(["success" => true, "message" => "Comment deleted"]);
}
if ($method === 'GET') {

    if ($action === 'comments' && $resourceId) {
        getCommentsByResourceId($db, $resourceId);
    }

    if ($id) {
        getResourceById($db, $id);
    }

    getAllResources($db);

} elseif ($method === 'POST') {

    if ($action === 'comment') {
        createComment($db, $body);
    }

    createResource($db, $body);

} elseif ($method === 'PUT') {

    updateResource($db, $body);

} elseif ($method === 'DELETE') {

    if ($action === 'delete_comment') {
        deleteComment($db, $commentId ?? ($body['comment_id'] ?? null));
    }

    deleteResource($db, $id ?? ($body['id'] ?? null));

} else {
    sendResponse(["success" => false, "message" => "Method Not Allowed"], 405);
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    return ['valid' => empty($missing), 'missing' => $missing];
}

?>
