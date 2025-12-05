<?php
session_start();


define('USER_FILE', __DIR__ . '/data/users.json');


$data_folder = __DIR__ . '/data';
if (!is_dir($data_folder)) {
    mkdir($data_folder, 0777, true);
}


if (!file_exists(USER_FILE)) {
    $default_admin = [
        [
            "id" => 1,
            "email" => "admin@example.com",
            "name" => "Admin User",
            "role" => "admin",
            "password" => password_hash("admin123", PASSWORD_DEFAULT)
        ]
    ];
    file_put_contents(USER_FILE, json_encode($default_admin, JSON_PRETTY_PRINT));
}

function load_users() {
    return json_decode(file_get_contents(USER_FILE), true);
}

function save_users($users) {
    file_put_contents(USER_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function find_user_by_email($email) {
    $users = load_users();
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

function authenticate($email, $password) {
    $user = find_user_by_email($email);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['user']['role'] !== 'admin') {
        echo "Access denied. Admins only.";
        exit();
    }
}

function get_next_user_id() {
    $users = load_users();
    $max_id = 0;
    foreach ($users as $user) {
        if (isset($user['id']) && $user['id'] > $max_id) {
            $max_id = $user['id'];
        }
    }
    return $max_id + 1;
}

function email_exists($email) {
    $users = load_users();
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            return true;
        }
    }
    return false;
}

function student_id_exists($student_id) {
    $users = load_users();
    foreach ($users as $user) {
        if (isset($user['student_id']) && $user['student_id'] === $student_id) {
            return true;
        }
    }
    return false;
}