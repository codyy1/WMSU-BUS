<?php
include __DIR__ . '/db_connect.php'; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: index.php");
    exit();
}
$admin_id = $_SESSION['user_id'];

$message = '';


if (isset($_POST['publish_announcement'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $sql = "INSERT INTO Announcements (Title, Content, CreatedBy) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $content, $admin_id);
    if ($stmt->execute()) {
        $message = "Announcement published successfully! ✅";
        // Create a notification for this announcement
        $announcement_id = $stmt->insert_id;
        try {
            $nstmt = $conn->prepare("INSERT INTO Notifications (Type, RelatedID, Title, Message) VALUES ('Announcement', ?, ?, ?)");
            if ($nstmt) {
                $nstmt->bind_param("iss", $announcement_id, $title, $content);
                $nstmt->execute();
                $nstmt->close();
            }
        } catch (Throwable $e) {
            // Non-fatal: notification creation failure should not block announcement publishing
        }
    } else {
        $message = "Error publishing announcement: " . $conn->error;
    }
}


$announcements_query = "
    SELECT a.*, u.FirstName 
    FROM Announcements a 
    JOIN Users u ON a.CreatedBy = u.UserID 
    ORDER BY PublishDate DESC
";
$announcements_result = $conn->query($announcements_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Announcements - WMSU Admin</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <style>
        .alert-success { background:#e8f5e9; padding:10px; border-radius:4px; margin-bottom:10px; }
        /* Sidebar layout */
        .admin-layout { display:flex; min-height:100vh; background:#f7f9fb; }
        .admin-sidebar { width:260px; background:#0f172a; color:#e2e8f0; }
        .admin-main { flex:1; padding:24px; }
        /* Sidebar content */
        .sidebar-header { padding:20px; font-weight:700; font-size:18px; letter-spacing:.5px; color:#fff; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-nav { list-style:none; margin:0; padding:8px 0; }
        .sidebar-nav li { margin:4px 8px; }
        .sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:#cbd5e1; text-decoration:none; transition:background .2s,color .2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background:#1e293b; color:#fff; }
        /* Cardish containers */
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.04); }
        .stack { display:grid; gap:16px; }
        /* Form */
        form label { display:block; font-weight:600; margin:10px 0 6px; color:#111827; }
        form input[type="text"], textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; background:#fff; outline:none; transition:border .15s, box-shadow .15s; }
        form input[type="text"]:focus, textarea:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
        .btn { background:#2563eb; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn:hover { background:#1d4ed8; }
        /* Table */
        table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
        thead th { background:#f3f4f6; text-align:left; padding:12px; font-weight:700; color:#111827; }
        tbody td { padding:12px; border-top:1px solid #e5e7eb; color:#111827; }
        /* Responsive */
        @media (max-width: 900px) {
            .admin-layout { flex-direction:column; }
            .admin-sidebar { width:100%; height:auto; position:relative; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <div class="admin-main">
    <div class="stack">
    <div class="card">
    <h1>Announcements Management</h1>
    <?php if ($message): ?>
        <div class="alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <h2>Publish New Announcement</h2>
    <form method="POST">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>

        <label for="content">Content</label>
        <textarea id="content" name="content" rows="4" required></textarea>
        
        <button type="submit" name="publish_announcement" class="btn">Publish</button>
    </form>
    </div>
    <div class="card">

    <h2>Existing Announcements</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Content Snippet</th>
                <th>Published By</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($announcements_result && $announcements_result->num_rows > 0): ?>
                <?php while ($row = $announcements_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['Content'], 0, 50)) . '...'; ?></td>
                    <td><?php echo htmlspecialchars($row['FirstName']); ?></td>
                    <td><?php echo date("Y-m-d h:i A", strtotime($row['PublishDate'])); ?></td>
                    <td>
                        <a href="edit_announcement.php?id=<?php echo $row['AnnouncementID']; ?>" class="btn" style="background-color: orange;">Edit</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No announcements have been published yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
    </div>
</body>
</html>
