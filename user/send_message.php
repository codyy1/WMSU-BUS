<?php
include __DIR__ . '/../admin/db_connect.php';

// Allow both AJAX (JSON) and form POST
function respond_json($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'Admin') {
    respond_json(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$from_id = $_SESSION['user_id'];

// Basic validation
$recipient_wmsuid = trim($_POST['recipient_wmsuid'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');

if ($recipient_wmsuid === '' || $body === '') {
    respond_json(['success' => false, 'error' => 'Recipient WMSU ID and message body are required']);
    exit();
}

// Find recipient user
try {
    $stmt = $conn->prepare("SELECT UserID, Email, FirstName, LastName FROM Users WHERE WMSUID = ? LIMIT 1");
    $stmt->bind_param("s", $recipient_wmsuid);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        respond_json(['success' => false, 'error' => 'Recipient not found']);
        exit();
    }
    $row = $res->fetch_assoc();
    $to_id = (int)$row['UserID'];
    $stmt->close();
} catch (Throwable $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        respond_json(['success' => false, 'error' => 'Database error']);
    }
    $_SESSION['message_error'] = 'Database error';
    header('Location: home.php');
    exit();
}

// Insert message into Messages table
try {
    $ins = $conn->prepare("INSERT INTO Messages (FromUserID, ToUserID, Subject, Body) VALUES (?, ?, ?, ?)");
    $ins->bind_param("iiss", $from_id, $to_id, $subject, $body);
    if ($ins->execute()) {
        $message_id = $ins->insert_id;
        $ins->close();
        // Create a notification for the recipient (if table exists)
        try {
            $tres = $conn->query("SHOW TABLES LIKE 'Notifications'");
            if ($tres && $tres->num_rows > 0) {
                $nstmt = $conn->prepare("INSERT INTO Notifications (Type, RelatedID, Title, Message) VALUES ('Message', ?, ?, ?)");
                if ($nstmt) {
                    $title = $subject ? 'Message: ' . $subject : 'New message';
                    $msg = 'You have received a new message from user ID ' . $from_id;
                    $nstmt->bind_param("iss", $message_id, $title, $msg);
                    $nstmt->execute();
                    $nstmt->close();
                }
            }
        } catch (Throwable $e) {
            // ignore notification errors
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            respond_json(['success' => true, 'message' => 'Message sent']);
        }
        $_SESSION['message_success'] = 'Message sent successfully';
        header('Location: home.php');
        exit();
    } else {
        $ins->close();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            respond_json(['success' => false, 'error' => 'Failed to send message']);
        }
        $_SESSION['message_error'] = 'Failed to send message';
        header('Location: home.php');
        exit();
    }
} catch (Throwable $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        respond_json(['success' => false, 'error' => 'Database insert error']);
    }
    $_SESSION['message_error'] = 'Database insert error';
    header('Location: home.php');
    exit();
}
