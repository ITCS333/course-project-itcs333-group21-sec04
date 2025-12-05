<?php
require_once 'auth.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$successMessage = '';
$formData = [
    'name' => '',
    'student_id' => '',
    'email' => '',
    'role' => 'student'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $formData['name'] = htmlspecialchars($name);
    $formData['student_id'] = htmlspecialchars($student_id);
    $formData['email'] = htmlspecialchars($email);
    
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    }
    
    if (empty($student_id)) {
        $errors[] = 'Student ID is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\-]+$/', $student_id)) {
        $errors[] = 'Student ID can only contain letters, numbers, and hyphens.';
    } elseif (student_id_exists($student_id)) {
        $errors[] = 'This Student ID is already registered.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (email_exists($email)) {
        $errors[] = 'This email is already registered.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $users = load_users();
        
        $new_user = [
            'id' => get_next_user_id(),
            'name' => $name,
            'student_id' => $student_id,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'student',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $users[] = $new_user;
        save_users($users);
        
        $successMessage = 'Registration successful! You can now login.';
        $formData = [
            'name' => '',
            'student_id' => '',
            'email' => '',
            'role' => 'student'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ITCS333</title>
    <link rel="stylesheet" href="task1.css">
    <style>
        .signup-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .signup-container h2 {
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
        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
        }
        .password-strength.weak { color: #c0392b; }
        .password-strength.medium { color: #f39c12; }
        .password-strength.strong { color: #27ae60; }
        .requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.9rem;
            color: #666;
        }
        .requirements ul {
            margin: 10px 0 0 20px;
        }
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }
        .login-link a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .student-id-note {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>ITCS333 — Course Registration</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
            </nav>
        </div>
    </header>
    <main>
        <div class="signup-container">
            <h2>Create Student Account</h2>
            <?php if (!empty($successMessage)): ?>
                <div class="success" style="margin-bottom: 25px;">
                    <?= htmlspecialchars($successMessage) ?>
                    <p style="margin-top: 10px;">
                        <a href="login.php" class="btn" style="display: inline-block; width: auto; padding: 10px 20px;">Go to Login</a>
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
            <?php if (empty($successMessage)): ?>
                <form method="POST" action="" id="signupForm">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?= $formData['name'] ?>" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="student_id">Student ID *</label>
                        <input type="text" id="student_id" name="student_id" value="<?= $formData['student_id'] ?>" placeholder="e.g., 202412345" required>
                        <div class="student-id-note">Format: Letters, numbers, and hyphens only</div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= $formData['email'] ?>" placeholder="student@example.com" required>
                    </div>
                    <div class="requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 6 characters long</li>
                            <li>Use a mix of letters and numbers for security</li>
                            <li>Should not be too common or personal</li>
                        </ul>
                    </div>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" placeholder="Create a secure password" required>
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                        <div id="passwordMatch" style="margin-top: 5px; font-size: 0.9rem;"></div>
                    </div>
                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                            Create Account
                        </button>
                    </div>
                    <div class="login-link">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 ITCS333 - Course Management System</p>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordMatch = document.getElementById('passwordMatch');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 'Weak';
                let className = 'weak';
                
                if (password.length >= 8) {
                    if (/[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                        strength = 'Strong';
                        className = 'strong';
                    } else if ((/[A-Z]/.test(password) || /[0-9]/.test(password)) && password.length >= 8) {
                        strength = 'Medium';
                        className = 'medium';
                    }
                } else if (password.length >= 6) {
                    strength = 'Fair';
                    className = 'medium';
                }
                
                if (password.length === 0) {
                    passwordStrength.textContent = '';
                    passwordStrength.className = 'password-strength';
                } else {
                    passwordStrength.textContent = `Strength: ${strength}`;
                    passwordStrength.className = `password-strength ${className}`;
                }
            });
            
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword.length === 0) {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.style.color = '#27ae60';
                } else {
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.style.color = '#c0392b';
                }
            });
        });
    </script>
</body>
</html>