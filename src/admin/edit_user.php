<?php
require_once 'auth.php';
require_admin();

if (!isset($_GET['id'])) {
    header('Location: manage_users.php');
    exit;
}

$user_id = (int)$_GET['id'];
$users = load_users();
$user_to_edit = null;

foreach ($users as $user) {
    if ($user['id'] === $user_id) {
        $user_to_edit = $user;
        break;
    }
}

if (!$user_to_edit) {
    header('Location: manage_users.php');
    exit;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($student_id)) {
        $errors[] = 'Student ID is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($errors)) {
        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                $user['name'] = $name;
                $user['student_id'] = $student_id;
                $user['email'] = $email;
                break;
            }
        }
        
        save_users($users);
        $successMessage = 'User updated successfully!';
     
        $user_to_edit = ['name' => $name, 'student_id' => $student_id, 'email' => $email, 'id' => $user_id, 'role' => $user_to_edit['role']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - ITCS333</title>
    <link rel="stylesheet" href="task1.css">
    <style>
        .edit-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .edit-container h2 {
            text-align: center;
            color: #0056b3;
            margin-bottom: 30px;
            font-size: 2.2rem;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        .back-link a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>ITCS333 — Edit User</h1>
            <nav>
                <a href="manage_users.php">Back to Manage Users</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    <main>
        <div class="edit-container">
            <h2>Edit User</h2>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success" style="margin-bottom: 25px;">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?= htmlspecialchars($user_to_edit['name']) ?>" 
                           placeholder="Enter full name" required>
                </div>
                
                <div class="form-group">
                    <label for="student_id">Student ID *</label>
                    <input type="text" id="student_id" name="student_id" 
                           value="<?= htmlspecialchars($user_to_edit['student_id'] ?? '') ?>" 
                           placeholder="Enter student ID" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($user_to_edit['email']) ?>" 
                           placeholder="Enter email" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <input type="text" id="role" name="role" 
                           value="<?= htmlspecialchars($user_to_edit['role']) ?>" 
                           disabled style="background: #f5f5f5;">
                    <small>Note: Role cannot be changed</small>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                        Update User
                    </button>
                </div>
            </form>
            
            <div class="back-link">
                <a href="manage_users.php">← Back to Manage Users</a>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 ITCS333 - Course Management System</p>
    </footer>
</body>
</html>