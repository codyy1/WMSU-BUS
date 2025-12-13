<?php
include __DIR__ . '/db_connect.php';

$message = '';
$reset_token = '';
$show_token = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (!$email) {
        $message = "Please enter your email or WMSU ID.";
    } else {
        // Find user by email or WMSUID
        $stmt = $conn->prepare("SELECT UserID, Email, WMSUID FROM Users WHERE (Email = ? OR WMSUID = ?) AND UserType = 'Admin' LIMIT 1");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $user_id = $row['UserID'];
            $user_email = $row['Email'];
            
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            // Insert reset token
            $ins = $conn->prepare("INSERT INTO PasswordResets (UserID, Email, Token, ExpiresAt) VALUES (?, ?, ?, ?)");
            $ins->bind_param("isss", $user_id, $user_email, $token, $expires_at);
            
            if ($ins->execute()) {
                $ins->close();
                // In a real app, you'd send this via email. For XAMPP, we display it.
                $reset_link = "http://localhost/WMSUBUS/admin/reset_password.php?token=" . urlencode($token);
                $reset_token = $token;
                $show_token = true;
                $message = "✓ Password reset link generated. Click the link below or enter the token on the reset page.";
            } else {
                $message = "Error generating reset token. Please try again.";
            }
        } else {
            $message = "No admin account found with that email or WMSU ID.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - WMSU Admin</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <style>
        body {
            background-image: url('../images/wmsu_bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
        .container { width: 100%; max-width: 400px; padding: 20px; position: relative; z-index: 5; }
        .card { background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        h1 { margin: 0 0 8px 0; font-size: 24px; color: #111827; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 600; color: #111827; margin-bottom: 6px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        input[type="text"]:focus, input[type="email"]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .button { width: 100%; padding: 10px; background: #667eea; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
        .button:hover { background: #5568d3; }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .token-box { background: #f3f4f6; padding: 12px; border-radius: 8px; margin: 12px 0; border-left: 4px solid #667eea; }
        .token-box label { margin-bottom: 4px; }
        .token-value { font-family: monospace; font-size: 12px; word-break: break-all; color: #111827; background: #fff; padding: 8px; border-radius: 4px; }
        .link-box { background: #eff6ff; padding: 12px; border-radius: 8px; margin: 12px 0; border-left: 4px solid #667eea; }
        .link-box a { color: #2563eb; text-decoration: none; word-break: break-all; font-size: 12px; }
        .link-box a:hover { text-decoration: underline; }
        .back-link { text-align: center; margin-top: 16px; }
        .back-link a { color: #667eea; text-decoration: none; font-size: 14px; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<header>
</header>
<div class="container">
    <div class="card">
        <h1>Forgot Password?</h1>
        <p class="subtitle">Enter your email or WMSU ID to reset your password</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $show_token ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_token): ?>
            <div class="token-box">
                <label>Reset Link (click to visit):</label>
                <div class="link-box">
                    <a href="<?php echo htmlspecialchars($reset_link); ?>" target="_blank">
                        <?php echo htmlspecialchars($reset_link); ?>
                    </a>
                </div>
            </div>
            
            <div class="token-box">
                <label>Or enter this token on reset page:</label>
                <div class="token-value"><?php echo htmlspecialchars($reset_token); ?></div>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="reset_password.php" class="button" style="display: inline-block; width: auto; padding: 10px 20px; text-decoration: none;">Go to Reset Password</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email or WMSU ID</label>
                    <input type="text" id="email" name="email" placeholder="your@email.com or WMSU-ID" required>
                </div>
                <button type="submit" name="request_reset" class="button">Request Reset Link</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>
