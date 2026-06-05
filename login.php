<?php
session_start();

// If already logged in, go to home
if(isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

include 'db.php';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT customer_id, name, password 
         FROM customers WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if($user && password_verify($password, $user['password'])) {
        $_SESSION['customer_id'] = $user['customer_id'];
        $_SESSION['name']        = $user['name'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - MediCare</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-box {
            max-width: 420px;
            margin: 80px auto;
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
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #e0e7ff;
            font-size: 15px;
        }
        .form-group input:focus {
            border-color: var(--primary);
        }
        .auth-btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border-radius: 10px;
            margin-top: 10px;
        }
        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
        }
        .auth-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>💊 MediCare</h1>
</div>

<div class="auth-box">
    <h2>🔐 Customer Login</h2>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" 
                   placeholder="you@email.com" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" 
                   placeholder="Enter your password" required>
        </div>
        <button class="auth-btn" type="submit">Login</button>
    </form>

    <div class="auth-link">
        Don't have an account? 
        <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>