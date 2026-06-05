<?php
session_start();
include 'db.php';
$error   = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if($password !== $confirm) {
        $error = 'Passwords do not match!';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare(
            "SELECT customer_id FROM customers WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows > 0) {
            $error = 'Email already registered!';
            $stmt->close();
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // close SELECT statement before preparing INSERT
            $stmt->close();

            $ins = $conn->prepare(
                "INSERT INTO customers (name, email, password) 
                 VALUES (?, ?, ?)"
            );
            if($ins) {
                $ins->bind_param("sss", $name, $email, $hashed);
                $ins->execute();
                $ins->close();
                $success = 'Account created! Please login.';
            } else {
                $error = 'DB error: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - MediCare</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-box {
            max-width: 420px;
            margin: 60px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        .auth-box h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #e0e7ff;
            font-size: 15px;
        }
        .auth-btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border-radius: 10px;
            margin-top: 10px;
        }
        .error-msg {
            background: #fee2e2; color: #dc2626;
            padding: 10px; border-radius: 8px;
            margin-bottom: 16px; text-align: center;
            font-weight: 600;
        }
        .success-msg {
            background: #d1fae5; color: #047857;
            padding: 10px; border-radius: 8px;
            margin-bottom: 16px; text-align: center;
            font-weight: 600;
        }
        .auth-link {
            text-align: center; margin-top: 20px;
            color: var(--text-light);
        }
        .auth-link a {
            color: var(--primary);
            font-weight: 600; text-decoration: none;
        }
    </style>
</head>
<body>

<div class="navbar"><h1>💊 MediCare</h1></div>

<div class="auth-box">
    <h2>📝 Create Account</h2>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="success-msg">
            <?php echo $success; ?>
            <br><a href="login.php">Click here to login</a>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" 
                   placeholder="Your full name" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" 
                   placeholder="you@email.com" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" 
                   placeholder="Min 6 characters" required>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" 
                   placeholder="Repeat password" required>
        </div>
        <button class="auth-btn" type="submit">
            Create Account
        </button>
    </form>

    <div class="auth-link">
        Already have account? 
        <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>