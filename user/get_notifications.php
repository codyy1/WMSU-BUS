<?php
include __DIR__ . '/../admin/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'Admin') {
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$uid = $_SESSION['user_id'];

// Check if Notifications table exists
$table_exists = false;
try {
    $tres = $conn->query("SHOW TABLES LIKE 'Notifications'");
    if ($tres && $tres->num_rows > 0) $table_exists = true;
} catch (Throwable $e) {
    $table_exists = false;
}

if (!$table_exists) {
    // Fallback: derive from Announcements
    $items = [];
    try {
        $ann_q = $conn->query("SELECT AnnouncementID AS ItemID, 'Announcement' AS Type, Title, Content AS Message, PublishDate AS CreatedAt FROM Announcements ORDER BY PublishDate DESC LIMIT 10");
        if ($ann_q && $ann_q->num_rows > 0) {
            while ($r = $ann_q->fetch_assoc()) {
                $items[] = [
                    'NotificationID' => 'ann-' . $r['ItemID'],
                    'Type' => $r['Type'],
                    'Title' => $r['Title'],
                    'Message' => $r['Message'],
                    'CreatedAt' => $r['CreatedAt'],
                    'IsRead' => 0
                ];
            }
        }
    } catch (Throwable $e) {}

    echo json_encode(['unread_count' => count($items), 'notifications' => $items]);
    exit();
}

// Use Notifications table and NotificationReads to compute per-user read status
try {
    $count_sql = "SELECT COUNT(*) AS cnt FROM Notifications n WHERE NOT EXISTS (SELECT 1 FROM NotificationReads nr WHERE nr.NotificationID = n.NotificationID AND nr.UserID = ?)
                  ";
    $cstmt = $conn->prepare($count_sql);
    $cstmt->bind_param("i", $uid);
    $cstmt->execute();
    $cres = $cstmt->get_result();
    $unread = 0;
    if ($cres && $r = $cres->fetch_assoc()) $unread = (int)$r['cnt'];
    $cstmt->close();

    $list_sql = "SELECT n.NotificationID, n.Type, n.Title, n.Message, n.CreatedAt,
                 (SELECT COUNT(*) FROM NotificationReads nr WHERE nr.NotificationID = n.NotificationID AND nr.UserID = ?) AS IsRead
                 FROM Notifications n ORDER BY n.CreatedAt DESC LIMIT 10";
    $lstmt = $conn->prepare($list_sql);
    $lstmt->bind_param("i", $uid);
    $lstmt->execute();
    $lres = $lstmt->get_result();
    $items = [];
    if ($lres) {
        while ($row = $lres->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $lstmt->close();

    echo json_encode(['unread_count' => $unread, 'notifications' => $items]);
    exit();
} catch (Throwable $e) {
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
    exit();
}
