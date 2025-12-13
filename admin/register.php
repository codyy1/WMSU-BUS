<?php

include __DIR__ . '/db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wmsuid = trim($_POST['wmsuid'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($wmsuid)) {
        $errors['wmsuid'] = "WMSU ID is required.";
    }
    if (empty($firstname)) {
        $errors['firstname'] = "First Name is required.";
    }
    if (empty($lastname)) {
        $errors['lastname'] = "Last Name is required.";
    }
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Confirm Password is required.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Check if WMSUID already exists in Users or UserRegistrations
        $sql_check = "SELECT WMSUID FROM Users WHERE WMSUID = ? UNION SELECT WMSUID FROM UserRegistrations WHERE WMSUID = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $wmsuid, $wmsuid);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['wmsuid'] = "WMSU ID already exists.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into Users as Admin
            $sql = "INSERT INTO Users (WMSUID, FirstName, LastName, Email, PasswordHash, UserType) VALUES (?, ?, ?, ?, ?, 'Admin')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $wmsuid, $firstname, $lastname, $email, $password_hash);

            if ($stmt->execute()) {
                // Redirect to setup_admin.php to show consistent admin setup page
                header('Location: setup_admin.php?created=1&wmsuid=' . urlencode($wmsuid));
                exit();
            } else {
                $errors['general'] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Registration - WMSU Transport</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
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
            background-color: rgba(255, 255, 255, 0.8);
            z-index: -1;
        }
        .container {
            position: relative;
            z-index: 5;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .password-container { position: relative; }
        .password-container input[type="password"],
        .password-container input[type="text"] { padding-right: 40px; width:100%; box-sizing:border-box; }
        .password-toggle { position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer; color:#666; font-size:16px; z-index:1; }
    </style>
</head>
<body>
<div class="container" style="max-width: 600px; margin: 50px auto; padding: 20px;">
    <h2 style="text-align:center; color: var(--wmsu-red);">WMSU Transport Admin Registration</h2>
    <?php if (isset($errors['general'])): ?>
        <p class="alert alert-error"><?php echo htmlspecialchars($errors['general']); ?></p>
    <?php endif; ?>

    <form method="POST" action="" class="login-form">
        <div class="form-row">
            <label for="wmsuid">WMSU ID</label>
            <input type="text" id="wmsuid" name="wmsuid" required value="<?php echo isset($_POST['wmsuid']) ? htmlspecialchars($_POST['wmsuid']) : ''; ?>">
            <?php if (isset($errors['wmsuid'])): ?><span style="color:red; font-size:12px;"><?php echo htmlspecialchars($errors['wmsuid']); ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" required value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
            <?php if (isset($errors['firstname'])): ?><span style="color:red; font-size:12px;"><?php echo htmlspecialchars($errors['firstname']); ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" required value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
            <?php if (isset($errors['lastname'])): ?><span style="color:red; font-size:12px;"><?php echo htmlspecialchars($errors['lastname']); ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <?php if (isset($errors['email'])): ?><span style="color:red; font-size:12px;"><?php echo htmlspecialchars($errors['email']); ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="password">Password</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
            </div>
            <?php if (isset($errors['password'])): ?><span style="color:red; font-size:12px;"><?php echo htmlspecialchars($errors['password']); ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
            </div>
            <?php if (isset($errors['confirm_password'])): ?><span style="color:red; font-size:12px;"><?php echo htmlspecialchars($errors['confirm_password']); ?></span><?php endif; ?>
        </div>

        <div class="form-row form-actions">
            <button type="submit" class="btn">Register Admin</button>
        </div>
    </form>

    <p style="text-align:center; margin-top:16px;">Back to <a href="index.php">Admin Login</a></p>
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
