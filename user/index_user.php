<?php

include __DIR__ . '/../admin/db_connect.php';

$message = '';


if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'Admin') {
    
    header("Location: home.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wmsuid = $_POST['wmsuid'] ?? '';
    $password = $_POST['password'] ?? '';

    
    $sql = "SELECT UserID, UserType, PasswordHash, Email FROM Users WHERE WMSUID = ? AND UserType != 'Admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $wmsuid);
    $stmt->execute();
    $stmt->bind_result($userId, $userType, $passwordHash, $email);

    if ($stmt->fetch()) {
       
        if (password_verify($password, $passwordHash)) { 
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = $userType;
            $_SESSION['email'] = $email;
            $stmt->close();
            
            
            header("Location: home.php");
            exit();
        } else {
            $message = "Invalid WMSU ID or Password";
        }
    } else {
        $message = "Invalid WMSU ID or Password";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login - WMSU Transport</title>
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-image: url('../images/wmsu_bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: -1;
        }
        header {
            position: relative;
            z-index: 10;
        }
        .container {
            position: relative;
            z-index: 5;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .password-container {
            position: relative;
        }
        .password-container input[type="password"],
        .password-container input[type="text"] {
            padding-right: 40px;
            width: 100%;
            box-sizing: border-box;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 16px;
            z-index: 1;
        }
        .password-toggle:hover {
            color: #333;
        }
    </style>
</head>
<body>
<header>
</header>
<div class="container" style="max-width: 400px; margin-top: 100px;">
    <h2>WMSU Transport User Portal</h2>
    <?php if ($message): ?>
        <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
    <form method="POST" action="" class="login-form">
        <div class="form-row">
            <label for="wmsuid">WMSU ID</label>
            <input type="text" id="wmsuid" name="wmsuid" required 
                   value="<?php echo isset($_POST['wmsuid']) ? htmlspecialchars($_POST['wmsuid']) : ''; ?>">
        </div>
        
        <div class="form-row">
            <label for="password">Password</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
            </div>
        </div>
        
        <div class="form-row">
            <button type="submit" class="btn">Login</button>
        </div>
    </form>

    <p style="text-align: center; margin-top: 20px;">
        Don't have an account? <a href="register.php">Register here</a>
    </p>
    
    <p style="text-align: center; margin-top: 10px;">
        <a href="forgot_password.php" style="color: #A30000; text-decoration: none; font-size: 14px;">Forgot Password?</a>
    </p>
</div>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = passwordField.nextElementSibling;

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>
