<?php
include __DIR__ . '/../admin/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'Admin') {
    header('Location: index_user.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Detect whether Notifications table exists (admin-side insert approach)
$notifications_table = false;
try {
    $tres = $conn->query("SHOW TABLES LIKE 'Notifications'");
    if ($tres && $tres->num_rows > 0) $notifications_table = true;
} catch (Throwable $e) {
    $notifications_table = false;
}

// Handle mark as read only if Notifications table exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $notifications_table) {
    if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
        $nid = intval($_POST['notification_id']);
        // insert into NotificationReads if not exists
        $stmt = $conn->prepare("SELECT NotificationReadID FROM NotificationReads WHERE NotificationID = ? AND UserID = ?");
        $stmt->bind_param("ii", $nid, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO NotificationReads (NotificationID, UserID) VALUES (?, ?)");
            $ins->bind_param("ii", $nid, $user_id);
            $ins->execute();
            $ins->close();
        }
        $stmt->close();
    }
    if (isset($_POST['mark_all_read'])) {
        // mark all as read for user
        $all = $conn->query("SELECT NotificationID FROM Notifications");
        while ($row = $all->fetch_assoc()) {
            $nid = $row['NotificationID'];
            $stmt = $conn->prepare("SELECT NotificationReadID FROM NotificationReads WHERE NotificationID = ? AND UserID = ?");
            $stmt->bind_param("ii", $nid, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO NotificationReads (NotificationID, UserID) VALUES (?, ?)");
                $ins->bind_param("ii", $nid, $user_id);
                $ins->execute();
                $ins->close();
            }
            $stmt->close();
        }
    }
    header('Location: notifications.php');
    exit();
}

// Fetch notifications. If Notifications table exists, use it (and read-status). Otherwise derive from Announcements and Schedules.
$notifications = [];
if ($notifications_table) {
    $sql = "SELECT n.NotificationID, n.Type, n.Title, n.Message, n.CreatedAt,
            (SELECT COUNT(*) FROM NotificationReads nr WHERE nr.NotificationID = n.NotificationID AND nr.UserID = ?) AS IsRead
            FROM Notifications n
            ORDER BY n.CreatedAt DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Derive notifications from Announcements and Schedules (user-side approach)
    $ann_q = $conn->query("SELECT AnnouncementID AS ItemID, 'Announcement' AS Type, Title, Content AS Message, PublishDate AS CreatedAt FROM Announcements");
    $sched_q = $conn->query("SELECT s.ScheduleID AS ItemID, 'Schedule' AS Type, CONCAT('New schedule: ', r.RouteName) AS Title, CONCAT('Route ', r.RouteName, ' on ', s.DateOfService, ' (Driver: ', s.DriverName, ')') AS Message, s.DateOfService AS CreatedAt FROM Schedules s JOIN Routes r ON s.RouteID = r.RouteID");

    $combined = [];
    if ($ann_q && $ann_q->num_rows > 0) {
        while ($row = $ann_q->fetch_assoc()) {
            $combined[] = [
                'NotificationID' => 'ann-' . $row['ItemID'],
                'Type' => $row['Type'],
                'Title' => $row['Title'],
                'Message' => $row['Message'],
                'CreatedAt' => $row['CreatedAt'],
                'IsRead' => 0
            ];
        }
    }
    if ($sched_q && $sched_q->num_rows > 0) {
        while ($row = $sched_q->fetch_assoc()) {
            $combined[] = [
                'NotificationID' => 'sch-' . $row['ItemID'],
                'Type' => $row['Type'],
                'Title' => $row['Title'],
                'Message' => $row['Message'],
                'CreatedAt' => $row['CreatedAt'],
                'IsRead' => 0
            ];
        }
    }
    // Sort combined by CreatedAt desc
    usort($combined, function($a, $b){
        return strtotime($b['CreatedAt']) <=> strtotime($a['CreatedAt']);
    });
    $notifications = $combined;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - WMSU Transport</title>
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .container { max-width:900px; margin:24px auto; }
        .notif-card { background:#fff; border:1px solid #e5e7eb; padding:12px 16px; border-radius:8px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:flex-start; }
        .notif-unread { background:#eef2ff; }
        .notif-meta { color:#6b7280; font-size:12px; }
        .btn { padding:8px 12px; border-radius:8px; background:#2563eb; color:#fff; text-decoration:none; border:none; cursor:pointer; }
        .btn-ghost { background:#f3f4f6; color:#111827; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h1>Notifications</h1>
    <p>All announcements, route/stop additions, and schedule updates from the admin.</p>
    <?php if (!empty($_SESSION['email'])): ?>
        <p style="color:#6b7280; font-size:14px;">Logged in as: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
    <?php endif; ?>

    <form method="POST" style="margin-bottom:12px;">
        <button type="submit" name="mark_all_read" class="btn">Mark All Read</button>
    </form>

    <?php if (empty($notifications)): ?>
        <div class="notif-card">No notifications at this time.</div>
    <?php else: ?>
        <?php foreach ($notifications as $n): ?>
            <div class="notif-card <?php echo $n['IsRead'] ? '' : 'notif-unread'; ?>">
                <div style="flex:1;">
                    <div style="font-weight:700;"><?php echo htmlspecialchars($n['Title'] ?? ucfirst($n['Type'])); ?></div>
                    <div style="margin-top:6px; color:#374151;">
                        <?php echo nl2br(htmlspecialchars($n['Message'])); ?>
                    </div>
                    <div class="notif-meta" style="margin-top:8px;"><?php echo date('M d, Y g:i A', strtotime($n['CreatedAt'])); ?></div>
                </div>
                <div style="margin-left:12px; text-align:right;">
                    <?php if (!$n['IsRead']): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="notification_id" value="<?php echo $n['NotificationID']; ?>">
                            <button type="submit" name="mark_read" class="btn btn-ghost">Mark Read</button>
                        </form>
                    <?php else: ?>
                        <div style="color:#6b7280; font-size:12px;">Read</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
