<?php

include __DIR__ . '/../admin/db_connect.php';


$announcements_query = "SELECT a.*, u.FirstName, u.LastName FROM Announcements a JOIN Users u ON a.CreatedBy = u.UserID ORDER BY PublishDate DESC";
$announcements_result = $conn->query($announcements_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WMSU Bus Announcements</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <style>
        .announcement-card { border-radius:8px; padding:16px; margin-bottom:16px; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.06); }
        .announcement-meta { font-size:0.9rem; color:#666; margin-top:10px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<style>
    .admin-layout { display:flex; min-height:100vh; background:#f7f9fb; }
    .admin-sidebar { width:260px; background:#0f172a; color:#e2e8f0; position:sticky; top:0; align-self:flex-start; height:100vh; }
    .admin-main { flex:1; padding:24px; }
    .sidebar-header { padding:20px; font-weight:700; font-size:18px; letter-spacing:.5px; color:#fff; border-bottom:1px solid rgba(255,255,255,.08); }
    .sidebar-nav { list-style:none; margin:0; padding:8px 0; }
    .sidebar-nav li { margin:4px 8px; }
    .sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:#cbd5e1; text-decoration:none; transition:background .2s,color .2s; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background:#1e293b; color:#fff; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.06); }
    .stack { display:grid; gap:16px; }
    @media (max-width: 900px) { .admin-layout { flex-direction:column; } .admin-sidebar { width:100%; height:auto; position:relative; } }
</style>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
    <div class="admin-main">
    <div class="stack">
    <div class="card">
    <h1>Official WMSU Bus Announcements</h1>

    <?php if ($announcements_result && $announcements_result->num_rows > 0): ?>
        <?php while ($row = $announcements_result->fetch_assoc()): ?>
            <div class="announcement-card">
                <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($row['Content'])); ?></p>
                <div class="announcement-meta">
                    Published: <?php echo date("F j, Y, g:i A", strtotime($row['PublishDate'])); ?>
                    by <?php echo htmlspecialchars(trim($row['FirstName'] . ' ' . $row['LastName'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>There are no current announcements from the WMSU Transport Office. Please check the schedules page for trip information.</p>
    <?php endif; ?>

    </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
