<?php
include __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Gather additional stats for enhanced dashboard
$totalAdmins = 0;
$pendingRegs = 0;
$totalRoutes = 0;
$totalVehicles = 0;
$upcomingSchedules = 0;
$totalSchedules = 0;
$canceledSchedules = 0;
$totalUsers = 0;
$recentRegs = [];
$recentAnnouncements = [];

// Analytics data
$userGrowthData = [];
$scheduleTrendsData = [];
$routePopularityData = [];

// Wrap queries in try/catch to avoid breaking the page on DB errors
try {
    $res = $conn->query("SELECT COUNT(*) AS total FROM Users WHERE UserType = 'Admin'");
    $totalAdmins = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM UserRegistrations WHERE Status = 'Pending'");
    $pendingRegs = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM Routes");
    $totalRoutes = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM Vehicles");
    $totalVehicles = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM Users WHERE UserType IN ('Student','Staff')");
    $totalUsers = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM Schedules WHERE DateOfService BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $upcomingSchedules = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM Schedules");
    $totalSchedules = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $res = $conn->query("SELECT COUNT(*) AS total FROM Schedules WHERE Status = 'Canceled'");
    $canceledSchedules = $res ? (int)$res->fetch_assoc()['total'] : 0;

    $stmt = $conn->prepare("SELECT WMSUID, FirstName, LastName, UserType, CreatedAt FROM UserRegistrations ORDER BY CreatedAt DESC LIMIT 5");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentRegs[] = $row;
        }
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT Title, PublishDate FROM Announcements ORDER BY PublishDate DESC LIMIT 5");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentAnnouncements[] = $row;
        }
        $stmt->close();
    }

    // User growth data (last 12 months)
    $stmt = $conn->prepare("SELECT DATE_FORMAT(CreatedAt, '%Y-%m') AS month, COUNT(*) AS count FROM Users WHERE CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $userGrowthData[] = $row;
        }
        $stmt->close();
    }

    // Schedule trends data (status distribution)
    $stmt = $conn->prepare("SELECT Status, COUNT(*) AS count FROM Schedules GROUP BY Status");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $scheduleTrendsData[] = $row;
        }
        $stmt->close();
    }

    // Route popularity data (schedules per route)
    $stmt = $conn->prepare("SELECT r.RouteName, COUNT(s.ScheduleID) AS count FROM Routes r LEFT JOIN Schedules s ON r.RouteID = s.RouteID GROUP BY r.RouteID, r.RouteName ORDER BY count DESC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $routePopularityData[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // silently ignore here; page will show zeroed stats
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - WMSU Transport</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <style>
        .admin-layout { display:flex; min-height:100vh; background:#f7f9fb; }
        .admin-sidebar { width:260px; background:#0f172a; color:#e2e8f0; }
        .admin-main { flex:1; padding:24px; }
        .sidebar-header { padding:20px; font-weight:700; font-size:18px; letter-spacing:.5px; color:#fff; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-nav { list-style:none; margin:0; padding:8px 0; }
        .sidebar-nav li { margin:4px 8px; }
        .sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:#cbd5e1; text-decoration:none; transition:background .2s,color .2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background:#1e293b; color:#fff; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.04); }
        .stack { display:grid; gap:16px; }
        .btn { background:#2563eb; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn:hover { background:#1d4ed8; }
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
        <div class="card" style="background: var(--white, #ffffff);">
            <h1 style="color: #000000; margin:0 0 8px 0;">Welcome, WMSU Transport Admin!</h1>
            <p style="color: #000000; margin:0;">Manage core data, schedules, and announcements.</p>
            <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
                <a href="manage_routes.php" class="btn">Manage Routes & Stops</a>
                <a href="manage_schedules.php" class="btn">Create/Manage Schedules</a>
                <a href="manage_accounts.php" class="btn">Manage Accounts</a>
                <a href="announcements.php" class="btn">Announcements</a>
            </div>
        </div>

        <div class="card">
            <h2>Analytics & Statistics</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px,1fr)); gap:16px; margin-top:12px;">
                <div class="card" style="padding:16px;">
                    <h3>User Growth (Last 12 Months)</h3>
                    <canvas id="userGrowthChart" width="300" height="200"></canvas>
                </div>
                <div class="card" style="padding:16px;">
                    <h3>Schedule Status Distribution</h3>
                    <canvas id="scheduleTrendsChart" width="300" height="200"></canvas>
                </div>
                <div class="card" style="padding:16px;">
                    <h3>Route Popularity</h3>
                    <canvas id="routePopularityChart" width="300" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Overview</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:12px; margin-top:12px;">
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Total Admins</h3>
                    <div class="number"><?php echo $totalAdmins; ?></div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <a href="manage_accounts.php" style="display:block; text-decoration:none; color:inherit;">
                        <h3>Total Users</h3>
                        <div class="number"><?php echo $totalUsers; ?></div>
                    </a>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Pending Registrations</h3>
                    <div class="number"><?php echo $pendingRegs; ?></div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Routes</h3>
                    <div class="number"><?php echo $totalRoutes; ?></div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Vehicles</h3>
                    <div class="number"><?php echo $totalVehicles; ?></div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Upcoming Schedules (7d)</h3>
                    <div class="number"><?php echo $upcomingSchedules; ?></div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Total Schedules</h3>
                    <div class="number"><?php echo $totalSchedules; ?></div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                    <h3>Canceled Schedules</h3>
                    <div class="number"><?php echo $canceledSchedules; ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Recent Registrations</h2>
            <?php if (empty($recentRegs)): ?>
                <p>No recent registrations.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>WMSU ID</th><th>Name</th><th>Type</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentRegs as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['WMSUID']); ?></td>
                            <td><?php echo htmlspecialchars($r['FirstName'] . ' ' . $r['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($r['UserType']); ?></td>
                            <td><?php echo htmlspecialchars($r['CreatedAt']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Recent Announcements</h2>
            <?php if (empty($recentAnnouncements)): ?>
                <p>No announcements yet.</p>
            <?php else: ?>
                <ul>
                <?php foreach ($recentAnnouncements as $a): ?>
                    <li><?php echo htmlspecialchars($a['Title']); ?> <small style="color:#666;">(<?php echo htmlspecialchars($a['PublishDate']); ?>)</small></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Reports</h2>
            <p>Quick report links and summaries.</p>
            <div style="margin-top:12px;">
                <a href="reports.php" class="btn">View Reports</a>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
    </div>
</body>
</html>