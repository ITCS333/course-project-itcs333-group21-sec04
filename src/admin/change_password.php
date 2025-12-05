<?php
require_once 'auth.php';
require_admin();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
   
    $users = load_users();
    $current_user_email = $_SESSION['user']['email'];
    
    foreach ($users as &$user) {
        if ($user['email'] === $current_user_email) {
          
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
                break;
            }
            
           
            if (strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters long.';
                break;
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match.';
                break;
            }
            
          
            $user['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            $success = true;
            break;
        }
    }
    
    if ($success) {
        save_users($users);
        $successMessage = 'Password changed successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - ITCS333</title>
    <link rel="stylesheet" href="task1.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .password-container h2 {
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
            <h1>ITCS333 — Change Admin Password</h1>
            <nav>
                <a href="admin_portal.php">Back to Portal</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    <main>
        <div class="password-container">
            <h2>Change Admin Password</h2>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success" style="margin-bottom: 25px;">
                    <?= htmlspecialchars($successMessage) ?>
                    <p style="margin-top: 10px;">
                        <a href="admin_portal.php" class="btn" style="display: inline-block; width: auto; padding: 10px 20px;">Back to Admin Portal</a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="Enter current password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter new password (min 6 characters)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" required>
                    </div>
                    
                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                            Change Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="admin_portal.php">← Back to Admin Portal</a>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 ITCS333 - Course Management System</p>
    </footer>
</body>
</html>