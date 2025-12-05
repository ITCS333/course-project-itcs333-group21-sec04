<?php
require_once 'auth.php';
require_admin();

if (!isset($_GET['id'])) {
    header('Location: manage_users.php');
    exit;
}

$user_id = (int)$_GET['id'];
$users = load_users();


foreach ($users as $key => $user) {
    if ($user['id'] === $user_id && $user['role'] !== 'admin') {
        unset($users[$key]);
        break;
    }
}


$users = array_values($users);
save_users($users);

header('Location: manage_users.php');
exit;