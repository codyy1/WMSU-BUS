<?php
include __DIR__ . '/db_connect.php';

$message = '';
$token_valid = false;
$user_email = '';

// Check if token is provided in URL
$token = $_GET['token'] ?? '';

if ($token) {
    // Validate token
    $stmt = $conn->prepare("SELECT UserID, Email FROM PasswordResets WHERE Token = ? AND Used = FALSE AND ExpiresAt > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $user_id = $row['UserID'];
        $user_email = $row['Email'];
        $token_valid = true;
    } else {
        $message = "Invalid or expired reset token.";
    }
    $stmt->close();
}

// Handle password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $reset_token = trim($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate token again from POST
    $stmt = $conn->prepare("SELECT UserID, Email FROM PasswordResets WHERE Token = ? AND Used = FALSE AND ExpiresAt > NOW() LIMIT 1");
    $stmt->bind_param("s", $reset_token);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if (!$res || $res->num_rows === 0) {
        $message = "Invalid or expired token.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        $row = $res->fetch_assoc();
        $reset_user_id = $row['UserID'];
        
        // Update password
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
        $upd->bind_param("si", $hashed, $reset_user_id);
        
        if ($upd->execute()) {
            // Mark token as used
            $mark = $conn->prepare("UPDATE PasswordResets SET Used = TRUE WHERE Token = ?");
            $mark->bind_param("s", $reset_token);
            $mark->execute();
            $mark->close();
            $upd->close();
            
            $message = "✓ Password updated successfully! You can now log in.";
            $token_valid = false;
            echo '<div style="text-align:center; margin-top:20px;"><a href="index.php" class="button" style="display:inline-block; padding:10px 20px; background:#667eea; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">Go to Login</a></div>';
        } else {
            $message = "Error updating password. Please try again.";
        }
        $upd->close();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - WMSU Admin</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .container { width: 100%; max-width: 400px; padding: 20px; }
        .card { background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        h1 { margin: 0 0 8px 0; font-size: 24px; color: #111827; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 600; color: #111827; margin-bottom: 6px; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        input[type="text"]:focus, input[type="password"]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .button { width: 100%; padding: 10px; background: #667eea; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .button:hover { background: #5568d3; }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .back-link { text-align: center; margin-top: 16px; }
        .back-link a { color: #667eea; text-decoration: none; font-size: 14px; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Reset Password</h1>
        <p class="subtitle">Enter your new password</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✓') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($token_valid): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background:#f3f4f6; cursor:not-allowed;">
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                
                <button type="submit" name="reset_password" class="button">Reset Password</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="token">Reset Token</label>
                    <input type="text" id="token" name="token" placeholder="Paste your reset token here" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                
                <button type="submit" name="reset_password" class="button">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>
