<?php
session_start();
define('USER_FILE', __DIR__ . '/data/users.json');

$name = trim($_POST['name'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $student_id === '' || $email === '' || $password === '') {
    header('Location: manage_users.php'); 
    exit;
}

$users = load_users();


if (email_exists($email)) { 
    header('Location: manage_users.php'); 
    exit; 
}


if (student_id_exists($student_id)) { 
    header('Location: manage_users.php'); 
    exit; 
}

$new = [
    'id' => get_next_user_id(),
    'name' => $name,
    'student_id' => $student_id,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'student',
    'created_at' => date('Y-m-d H:i:s')
];

$users[] = $new;
save_users($users);
header('Location: manage_users.php');
exit;