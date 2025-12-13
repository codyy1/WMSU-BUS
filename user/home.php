<?php
include __DIR__ . '/../admin/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'Admin') {
    header("Location: index_user.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT UserID, WMSUID, FirstName, LastName, Email, UserType, CreatedAt FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userId, $wmsuid, $firstName, $lastName, $email, $userType, $createdAt);
$stmt->fetch();
$stmt->close();

// Get statistics
$total_routes = $conn->query("SELECT COUNT(*) AS total FROM Routes WHERE IsActive = TRUE")->fetch_assoc()['total'];
$total_announcements = $conn->query("SELECT COUNT(*) AS total FROM Announcements")->fetch_assoc()['total'];

// Get today's routes with schedule information
$today = date("Y-m-d");
$today_routes_sql = "SELECT DISTINCT r.RouteID, r.RouteName, r.StartLocation, r.EndLocation, 
                     s.ScheduleID, s.DriverName, v.PlateNumber, s.Status, 
                     MIN(rs.ScheduledTime) AS FirstDeparture
                     FROM Routes r
                     LEFT JOIN Schedules s ON r.RouteID = s.RouteID AND s.DateOfService = ?
                     LEFT JOIN Vehicles v ON s.VehicleID = v.VehicleID
                     LEFT JOIN RouteStops rs ON r.RouteID = rs.RouteID
                     WHERE r.IsActive = TRUE
                     GROUP BY r.RouteID, r.RouteName, r.StartLocation, r.EndLocation, 
                              s.ScheduleID, s.DriverName, v.PlateNumber, s.Status
                     ORDER BY FirstDeparture ASC";
$today_routes_stmt = $conn->prepare($today_routes_sql);
$today_routes_stmt->bind_param("s", $today);
$today_routes_stmt->execute();
$today_routes_result = $today_routes_stmt->get_result();

// Get recent announcements (limit 3)
$recent_announcements_sql = "SELECT a.AnnouncementID, a.Title, a.Content, a.PublishDate, u.FirstName, u.LastName 
                             FROM Announcements a 
                             JOIN Users u ON a.CreatedBy = u.UserID 
                             ORDER BY a.PublishDate DESC LIMIT 3";
$recent_announcements = $conn->query($recent_announcements_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - WMSU Transport</title>
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-layout { display:flex; min-height:100vh; background:#f7f9fb; }
        .admin-sidebar { width:260px; background:#0f172a; color:#e2e8f0; position:sticky; top:0; align-self:flex-start; height:100vh; }
        .admin-main { flex:1; padding:24px; }
        .sidebar-header { padding:20px; font-weight:700; font-size:18px; letter-spacing:.5px; color:#fff; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-nav { list-style:none; margin:0; padding:8px 0; }
        .sidebar-nav li { margin:4px 8px; }
        .sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:#cbd5e1; text-decoration:none; transition:background .2s,color .2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background:#1e293b; color:#fff; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.04); }
        .stack { display:grid; gap:16px; }
        
        .welcome-section { background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#fff; padding:24px; border-radius:12px; margin-bottom:16px; }
        .welcome-section h1 { margin:0 0 8px 0; font-size:28px; }
        .welcome-section p { margin:0; opacity:0.9; }
        
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:16px; margin-bottom:16px; }
        .stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; text-align:center; }
        .stat-number { font-size:28px; font-weight:700; color:#2563eb; }
        .stat-label { font-size:14px; color:#6b7280; margin-top:8px; }
        
        .quick-access-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:16px; }
        .quick-access-card { background:#fff; border:2px solid #e5e7eb; border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:all .2s; text-decoration:none; color:inherit; }
        .quick-access-card:hover { border-color:#2563eb; box-shadow:0 4px 12px rgba(37, 99, 235, 0.1); transform:translateY(-2px); }
        .quick-access-icon { font-size:32px; margin-bottom:12px; color:#2563eb; }
        .quick-access-title { font-weight:600; font-size:16px; margin-bottom:4px; }
        .quick-access-desc { font-size:13px; color:#6b7280; }
        
        .section-title { font-size:18px; font-weight:700; color:#111827; margin-bottom:12px; margin-top:8px; }
        
        .route-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; }
        .route-header { display:flex; justify-content:space-between; align-items:start; margin-bottom:12px; }
        .route-name { font-weight:700; font-size:16px; color:#111827; }
        .route-status { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .route-status.on-time { background:#dcfce7; color:#166534; }
        .route-status.delayed { background:#fef3c7; color:#92400e; }
        .route-status.no-trip { background:#f3f4f6; color:#374151; }
        .route-info { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; font-size:14px; }
        .route-info-item { }
        .route-info-label { color:#6b7280; font-size:12px; }
        .route-info-value { color:#111827; font-weight:500; }
        
        .announcement-item { background:#fff; border-left:4px solid #2563eb; border-radius:4px; padding:12px; margin-bottom:12px; }
        .announcement-title { font-weight:600; color:#111827; margin-bottom:6px; }
        .announcement-preview { color:#6b7280; font-size:13px; margin-bottom:6px; line-height:1.4; }
        .announcement-meta { font-size:12px; color:#9ca3af; }
        
        .profile-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; }
        .profile-item { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .profile-item:last-child { border-bottom:none; }
        .profile-label { color:#6b7280; font-weight:500; }
        .profile-value { color:#111827; font-weight:600; }
        
        .btn-primary { background:#2563eb; color:#fff; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-block; }
        .btn-primary:hover { background:#1d4ed8; }
        
        .no-data { text-align:center; color:#6b7280; padding:20px; background:#f9fafb; border-radius:8px; }
        
        @media (max-width: 900px) { 
            .admin-layout { flex-direction:column; } 
            .admin-sidebar { width:100%; height:auto; position:relative; }
            .stats-grid { grid-template-columns:repeat(2, 1fr); }
            .quick-access-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
    <div class="admin-main">
        <div class="stack">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($firstName . ' ' . $lastName); ?>! 👋</h1>
                <p>Stay updated with WMSU transport schedules and announcements</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_routes; ?></div>
                    <div class="stat-label">Active Routes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_announcements; ?></div>
                    <div class="stat-label">Announcements</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo date("l"); ?></div>
                    <div class="stat-label">Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo strtoupper($userType); ?></div>
                    <div class="stat-label">Account Type</div>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="quick-access-grid">
                <a href="schedule_view.php" class="quick-access-card">
                    <div class="quick-access-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="quick-access-title">View Schedules</div>
                    <div class="quick-access-desc">Check all bus routes and timings</div>
                </a>
                <a href="announcements_user.php" class="quick-access-card">
                    <div class="quick-access-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="quick-access-title">Announcements</div>
                    <div class="quick-access-desc">Latest updates from WMSU</div>
                </a>
            </div>

            <!-- Today's Routes Section -->
            <div class="card">
                <div class="section-title"><i class="fas fa-bus"></i> Today's Active Routes (<?php echo date("F j, Y"); ?>)</div>
                
                <?php if ($today_routes_result && $today_routes_result->num_rows > 0): ?>
                    <?php while ($route = $today_routes_result->fetch_assoc()): ?>
                        <div class="route-card">
                            <div class="route-header">
                                <div class="route-name"><?php echo htmlspecialchars($route['RouteName']); ?></div>
                                <div class="route-status <?php 
                                    if ($route['ScheduleID']) {
                                        echo strtolower(str_replace(' ', '-', $route['Status']));
                                    } else {
                                        echo 'no-trip';
                                    }
                                ?>">
                                    <?php echo $route['ScheduleID'] ? htmlspecialchars($route['Status']) : 'No Trip'; ?>
                                </div>
                            </div>
                            <div class="route-info">
                                <div class="route-info-item">
                                    <div class="route-info-label">Route</div>
                                    <div class="route-info-value"><?php echo htmlspecialchars($route['StartLocation']) . ' → ' . htmlspecialchars($route['EndLocation']); ?></div>
                                </div>
                                <?php if ($route['ScheduleID']): ?>
                                    <div class="route-info-item">
                                        <div class="route-info-label">Driver</div>
                                        <div class="route-info-value"><?php echo htmlspecialchars($route['DriverName']); ?></div>
                                    </div>
                                    <div class="route-info-item">
                                        <div class="route-info-label">Vehicle</div>
                                        <div class="route-info-value"><?php echo htmlspecialchars($route['PlateNumber']); ?></div>
                                    </div>
                                    <div class="route-info-item">
                                        <div class="route-info-label">First Stop Time</div>
                                        <div class="route-info-value"><?php echo $route['FirstDeparture'] ? date("g:i A", strtotime($route['FirstDeparture'])) : 'N/A'; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i> No routes scheduled for today. Check back later!
                    </div>
                <?php endif; ?>
                
                <a href="schedule_view.php" class="btn-primary" style="margin-top:12px;">View Detailed Schedules</a>
            </div>

            <!-- Recent Announcements -->
            <div class="card">
                <div class="section-title"><i class="fas fa-megaphone"></i> Recent Announcements</div>
                
                <?php if ($recent_announcements && $recent_announcements->num_rows > 0): ?>
                    <?php while ($announcement = $recent_announcements->fetch_assoc()): ?>
                        <div class="announcement-item">
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['Title']); ?></div>
                            <div class="announcement-preview">
                                <?php 
                                    $preview = substr($announcement['Content'], 0, 100);
                                    echo htmlspecialchars($preview) . (strlen($announcement['Content']) > 100 ? '...' : '');
                                ?>
                            </div>
                            <div class="announcement-meta">
                                <i class="fas fa-calendar-clock"></i> <?php echo date("M d, Y g:i A", strtotime($announcement['PublishDate'])); ?> 
                                by <?php echo htmlspecialchars($announcement['FirstName'] . ' ' . $announcement['LastName']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <a href="announcements_user.php" class="btn-primary" style="margin-top:12px;">View All Announcements</a>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i> No announcements at this time.
                    </div>
                <?php endif; ?>
            </div>

            <!-- User Profile Card -->
            <div class="card">
                <div class="section-title"><i class="fas fa-user"></i> Your Profile</div>
                <div class="profile-card">
                    <div class="profile-item">
                        <span class="profile-label">WMSU ID:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($wmsuid); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Full Name:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Account Type:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($userType); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Email:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Account Created:</span>
                        <span class="profile-value"><?php echo date("F j, Y", strtotime($createdAt)); ?></span>
                    </div>
                </div>
            </div>

        </div>
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
