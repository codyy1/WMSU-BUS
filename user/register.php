<?php

include __DIR__ . '/../admin/db_connect.php';

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wmsuid = trim($_POST['wmsuid'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $usertype = $_POST['usertype'] ?? 'Student';

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

            // Insert into UserRegistrations
            $sql = "INSERT INTO UserRegistrations (WMSUID, FirstName, LastName, Email, PasswordHash, UserType) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $wmsuid, $firstname, $lastname, $email, $password_hash, $usertype);

            if ($stmt->execute()) {
                $success = "Registration successful! Your account is pending approval.";
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
    <title>User Registration - WMSU Transport</title>
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
<div class="container" style="max-width: 400px; margin-top: 50px;">
    <h2>WMSU Transport User Registration</h2>
    <?php if (!empty($success)): ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (isset($errors['general'])): ?>
        <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($errors['general']); ?></p>
    <?php endif; ?>

    <form method="POST" action="" class="login-form">
        <div class="form-row">
            <label for="wmsuid">WMSU ID</label>
            <input type="text" id="wmsuid" name="wmsuid" required
                   value="<?php echo isset($_POST['wmsuid']) ? htmlspecialchars($_POST['wmsuid']) : ''; ?>">
            <?php if (isset($errors['wmsuid'])): ?>
                <span style="color: red; font-size: 12px;"><?php echo htmlspecialchars($errors['wmsuid']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" required
                   value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
            <?php if (isset($errors['firstname'])): ?>
                <span style="color: red; font-size: 12px;"><?php echo htmlspecialchars($errors['firstname']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" required
                   value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
            <?php if (isset($errors['lastname'])): ?>
                <span style="color: red; font-size: 12px;"><?php echo htmlspecialchars($errors['lastname']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <?php if (isset($errors['email'])): ?>
                <span style="color: red; font-size: 12px;"><?php echo htmlspecialchars($errors['email']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="usertype">User Type</label>
            <select id="usertype" name="usertype" required>
                <option value="Student" <?php echo (isset($_POST['usertype']) && $_POST['usertype'] == 'Student') ? 'selected' : ''; ?>>Student</option>
                <option value="Staff" <?php echo (isset($_POST['usertype']) && $_POST['usertype'] == 'Staff') ? 'selected' : ''; ?>>Staff</option>
            </select>
        </div>

        <div class="form-row">
            <label for="password">Password</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
            </div>
            <?php if (isset($errors['password'])): ?>
                <span style="color: red; font-size: 12px;"><?php echo htmlspecialchars($errors['password']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
            </div>
            <?php if (isset($errors['confirm_password'])): ?>
                <span style="color: red; font-size: 12px;"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <button type="submit" class="btn">Register</button>
        </div>
    </form>

    <p style="text-align: center; margin-top: 20px;">
        Already have an account? <a href="index_user.php">Login here</a>
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
