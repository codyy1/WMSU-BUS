<?php
include __DIR__ . '/db_connect.php'; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$action_type = '';

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update Route
    if ($action === 'update_route') {
        $route_id = intval($_POST['route_id']);
        $route_name = trim($_POST['route_name'] ?? '');
        $start_location = trim($_POST['start_location'] ?? '');
        $end_location = trim($_POST['end_location'] ?? '');
        
        if ($route_name && $start_location && $end_location) {
            $stmt = $conn->prepare("UPDATE Routes SET RouteName = ?, StartLocation = ?, EndLocation = ? WHERE RouteID = ?");
            $stmt->bind_param("sssi", $route_name, $start_location, $end_location, $route_id);
            if ($stmt->execute()) {
                $message = "Route updated successfully!";
                $action_type = 'success';
            } else {
                $message = "Error updating route: " . $conn->error;
                $action_type = 'error';
            }
            $stmt->close();
        }
    }
    
    // Update Stop
    elseif ($action === 'update_stop') {
        $stop_id = intval($_POST['stop_id']);
        $stop_name = trim($_POST['stop_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($stop_name && $description) {
            $stmt = $conn->prepare("UPDATE Stops SET StopName = ?, Description = ? WHERE StopID = ?");
            $stmt->bind_param("ssi", $stop_name, $description, $stop_id);
            if ($stmt->execute()) {
                $message = "Stop updated successfully!";
                $action_type = 'success';
            } else {
                $message = "Error updating stop: " . $conn->error;
                $action_type = 'error';
            }
            $stmt->close();
        }
    }
    
    // Update User Account
    elseif ($action === 'update_user') {
        $user_id = intval($_POST['user_id']);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        
        if ($first_name && $last_name) {
            $stmt = $conn->prepare("UPDATE Users SET FirstName = ?, LastName = ? WHERE UserID = ?");
            $stmt->bind_param("ssi", $first_name, $last_name, $user_id);
            if ($stmt->execute()) {
                $message = "User updated successfully!";
                $action_type = 'success';
            } else {
                $message = "Error updating user: " . $conn->error;
                $action_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Get report type
$report_type = $_GET['report'] ?? 'routes';
$sort_by = $_GET['sort'] ?? 'date'; // 'date' or 'name'
$sort_order = $_GET['order'] ?? 'desc'; // 'asc' or 'desc'

// Fetch data based on report type
$routes_data = [];
$stops_data = [];
$users_data = [];

if ($report_type === 'routes' || $report_type === 'all') {
    $order_by = ($sort_by === 'name') ? 'RouteName' : 'CreatedAt';
    $order_direction = ($sort_order === 'asc') ? 'ASC' : 'DESC';
    $result = $conn->query("SELECT RouteID, RouteName, StartLocation, EndLocation, IsActive, CreatedAt FROM Routes ORDER BY $order_by $order_direction");
    while ($row = $result->fetch_assoc()) {
        $routes_data[] = $row;
    }
}

if ($report_type === 'stops' || $report_type === 'all') {
    $order_by = ($sort_by === 'name') ? 'StopName' : 'CreatedAt';
    $order_direction = ($sort_order === 'asc') ? 'ASC' : 'DESC';
    $result = $conn->query("SELECT StopID, StopName, Description, Latitude, Longitude, CreatedAt FROM Stops ORDER BY $order_by $order_direction");
    while ($row = $result->fetch_assoc()) {
        $stops_data[] = $row;
    }
}

if ($report_type === 'users' || $report_type === 'all') {
    $order_direction = ($sort_order === 'asc') ? 'ASC' : 'DESC';
    $result = $conn->query("SELECT UserID, WMSUID, FirstName, LastName, UserType, CreatedAt FROM Users ORDER BY CreatedAt $order_direction");
    while ($row = $result->fetch_assoc()) {
        $users_data[] = $row;
    }
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // We'll use a simple approach to generate a printable format
    // For full PDF generation, consider using a library like TCPDF or mPDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="WMSU_Transport_Report_' . date('Y-m-d_H-i-s') . '.html"');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>WMSU Transport Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #2563eb; }
            h2 { color: #1e293b; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-top: 30px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            th { background: #2563eb; color: white; padding: 12px; text-align: left; }
            td { border: 1px solid #e5e7eb; padding: 10px; }
            tr:nth-child(even) { background: #f9fafb; }
            .report-date { text-align: center; color: #6b7280; margin-bottom: 20px; }
            @media print { body { margin: 0; } }
        </style>
    </head>
    <body>
        <h1>WMSU Transport System Report</h1>
        <div class="report-date">Generated on: ' . date('F j, Y g:i A') . '</div>';
    
    if (!empty($routes_data)) {
        echo '<h2>Routes</h2>
        <table>
            <thead>
                <tr>
                    <th>Route ID</th>
                    <th>Route Name</th>
                    <th>Start Location</th>
                    <th>End Location</th>
                    <th>Status</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($routes_data as $route) {
            echo '<tr>
                <td>' . $route['RouteID'] . '</td>
                <td>' . htmlspecialchars($route['RouteName']) . '</td>
                <td>' . htmlspecialchars($route['StartLocation']) . '</td>
                <td>' . htmlspecialchars($route['EndLocation']) . '</td>
                <td>' . ($route['IsActive'] ? 'Active' : 'Inactive') . '</td>
                <td>' . date('M d, Y', strtotime($route['CreatedAt'])) . '</td>
            </tr>';
        }
        echo '</tbody></table>';
    }
    
    if (!empty($stops_data)) {
        echo '<h2>Stops</h2>
        <table>
            <thead>
                <tr>
                    <th>Stop ID</th>
                    <th>Stop Name</th>
                    <th>Description</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($stops_data as $stop) {
            echo '<tr>
                <td>' . $stop['StopID'] . '</td>
                <td>' . htmlspecialchars($stop['StopName']) . '</td>
                <td>' . htmlspecialchars($stop['Description']) . '</td>
                <td>' . ($stop['Latitude'] ?? 'N/A') . '</td>
                <td>' . ($stop['Longitude'] ?? 'N/A') . '</td>
            </tr>';
        }
        echo '</tbody></table>';
    }
    
    if (!empty($users_data)) {
        echo '<h2>User Accounts</h2>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>WMSU ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>User Type</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($users_data as $user) {
            echo '<tr>
                <td>' . $user['UserID'] . '</td>
                <td>' . htmlspecialchars($user['WMSUID']) . '</td>
                <td>' . htmlspecialchars($user['FirstName']) . '</td>
                <td>' . htmlspecialchars($user['LastName']) . '</td>
                <td>' . htmlspecialchars($user['UserType']) . '</td>
                <td>' . date('M d, Y', strtotime($user['CreatedAt'])) . '</td>
            </tr>';
        }
        echo '</tbody></table>';
    }
    
    echo '</body></html>';
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - WMSU Transport</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .alert-success { background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:4px; margin-bottom:16px; border-left:4px solid #2e7d32; }
        .alert-error { background:#ffebee; color:#c62828; padding:12px; border-radius:4px; margin-bottom:16px; border-left:4px solid #c62828; }
        .report-tabs { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .tab-btn { background:#f3f4f6; border:2px solid #e5e7eb; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:600; transition:all .2s; }
        .tab-btn.active { background:#2563eb; color:#fff; border-color:#2563eb; }
        .tab-btn:hover { border-color:#2563eb; }
        table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
        thead th { background:#f3f4f6; text-align:left; padding:12px; font-weight:700; color:#111827; }
        tbody td { padding:12px; border-top:1px solid #e5e7eb; color:#111827; }
        tbody tr:hover { background:#f9fafb; }
        .edit-btn, .save-btn, .cancel-btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; margin-right:4px; }
        .edit-btn { background:#2563eb; color:#fff; }
        .edit-btn:hover { background:#1d4ed8; }
        .save-btn { background:#16a34a; color:#fff; }
        .save-btn:hover { background:#15803d; }
        .cancel-btn { background:#f3f4f6; color:#111827; }
        .cancel-btn:hover { background:#e5e7eb; }
        .print-btn { background:#6366f1; color:#fff; padding:10px 16px; border:none; border-radius:8px; cursor:pointer; font-weight:600; text-decoration:none; }
        .print-btn:hover { background:#4f46e5; }
        a { text-decoration:none; }
        input[type="text"], input[type="number"] { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; }
        .action-cell { min-width:150px; }
        .edit-form { display:none; }
        .edit-form.show { display:table-row; }
        .section-title { font-size:16px; font-weight:700; color:#111827; margin-bottom:12px; margin-top:8px; }
        @media (max-width: 900px) {
            .admin-layout { flex-direction:column; }
            .admin-sidebar { width:100%; height:auto; position:relative; }
            .report-tabs { flex-direction:column; }
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
                <h1>System Reports</h1>
                <p>View, edit, and export all system data including routes, stops, and user accounts.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert-<?php echo $action_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Report Tabs -->
            <div class="card">
                <div class="report-tabs">
                    <a href="?report=routes" class="tab-btn <?php echo ($report_type === 'routes' ? 'active' : ''); ?>">
                        <i class="fas fa-map"></i> Routes
                    </a>
                    <a href="?report=stops" class="tab-btn <?php echo ($report_type === 'stops' ? 'active' : ''); ?>">
                        <i class="fas fa-map-pin"></i> Stops
                    </a>
                    <a href="?report=users" class="tab-btn <?php echo ($report_type === 'users' ? 'active' : ''); ?>">
                        <i class="fas fa-users"></i> User Accounts
                    </a>
                    <a href="?report=all" class="tab-btn <?php echo ($report_type === 'all' ? 'active' : ''); ?>">
                        <i class="fas fa-file-alt"></i> All Data
                    </a>
                    <a href="?report=<?php echo $report_type; ?>&export=pdf" class="print-btn" style="margin-left:auto;">
                        <i class="fas fa-file-pdf"></i> Export as HTML
                    </a>
                    <button onclick="window.print()" class="print-btn">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>

                <!-- Sort Controls -->
                <div style="display:flex; gap:12px; align-items:center; margin-top:16px; padding-top:16px; border-top:1px solid #e5e7eb;">
                    <span style="font-weight:600; color:#111827;">Sort by:</span>
                    <a href="?report=<?php echo $report_type; ?>&sort=date&order=<?php echo ($sort_by === 'date' && $sort_order === 'desc') ? 'asc' : 'desc'; ?>" 
                       class="tab-btn <?php echo ($sort_by === 'date') ? 'active' : ''; ?>" style="padding:8px 12px;">
                        <i class="fas fa-calendar"></i> <?php echo ($sort_by === 'date' && $sort_order === 'asc') ? 'Oldest First' : 'Newest First'; ?>
                    </a>
                    <a href="?report=<?php echo $report_type; ?>&sort=name&order=<?php echo ($sort_by === 'name' && $sort_order === 'asc') ? 'desc' : 'asc'; ?>" 
                       class="tab-btn <?php echo ($sort_by === 'name') ? 'active' : ''; ?>" style="padding:8px 12px;">
                        <i class="fas fa-font"></i> <?php echo ($sort_by === 'name' && $sort_order === 'asc') ? 'A-Z Order' : 'Recently Added'; ?>
                    </a>
                </div>
            </div>

            <!-- Routes Report -->
            <?php if (($report_type === 'routes' || $report_type === 'all') && !empty($routes_data)): ?>
            <div class="card">
                <div class="section-title"><i class="fas fa-map"></i> Routes (<?php echo count($routes_data); ?>)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Route ID</th>
                            <th>Route Name</th>
                            <th>Start Location</th>
                            <th>End Location</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes_data as $route): ?>
                        <tr id="route-<?php echo $route['RouteID']; ?>">
                            <td><?php echo $route['RouteID']; ?></td>
                            <td><?php echo htmlspecialchars($route['RouteName']); ?></td>
                            <td><?php echo htmlspecialchars($route['StartLocation']); ?></td>
                            <td><?php echo htmlspecialchars($route['EndLocation']); ?></td>
                            <td><?php echo ($route['IsActive'] ? '<span style="background:#dcfce7;color:#166534;padding:4px 8px;border-radius:4px;">Active</span>' : '<span style="background:#fee2e2;color:#991b1b;padding:4px 8px;border-radius:4px;">Inactive</span>'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($route['CreatedAt'])); ?></td>
                            <td class="action-cell">
                                <button class="edit-btn" onclick="toggleEditRoute(<?php echo $route['RouteID']; ?>)">Edit</button>
                            </td>
                        </tr>
                        <tr class="edit-form" id="edit-route-<?php echo $route['RouteID']; ?>">
                            <td colspan="7">
                                <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
                                    <input type="hidden" name="action" value="update_route">
                                    <input type="hidden" name="route_id" value="<?php echo $route['RouteID']; ?>">
                                    <div style="flex:1;min-width:150px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Route Name</label>
                                        <input type="text" name="route_name" value="<?php echo htmlspecialchars($route['RouteName']); ?>" required>
                                    </div>
                                    <div style="flex:1;min-width:150px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Start Location</label>
                                        <input type="text" name="start_location" value="<?php echo htmlspecialchars($route['StartLocation']); ?>" required>
                                    </div>
                                    <div style="flex:1;min-width:150px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">End Location</label>
                                        <input type="text" name="end_location" value="<?php echo htmlspecialchars($route['EndLocation']); ?>" required>
                                    </div>
                                    <button type="submit" class="save-btn">Save</button>
                                    <button type="button" class="cancel-btn" onclick="toggleEditRoute(<?php echo $route['RouteID']; ?>)">Cancel</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Stops Report -->
            <?php if (($report_type === 'stops' || $report_type === 'all') && !empty($stops_data)): ?>
            <div class="card">
                <div class="section-title"><i class="fas fa-map-pin"></i> Stops (<?php echo count($stops_data); ?>)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Stop ID</th>
                            <th>Stop Name</th>
                            <th>Description</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stops_data as $stop): ?>
                        <tr id="stop-<?php echo $stop['StopID']; ?>">
                            <td><?php echo $stop['StopID']; ?></td>
                            <td><?php echo htmlspecialchars($stop['StopName']); ?></td>
                            <td><?php echo htmlspecialchars($stop['Description']); ?></td>
                            <td><?php echo $stop['Latitude'] ?? 'N/A'; ?></td>
                            <td><?php echo $stop['Longitude'] ?? 'N/A'; ?></td>
                            <td class="action-cell">
                                <button class="edit-btn" onclick="toggleEditStop(<?php echo $stop['StopID']; ?>)">Edit</button>
                            </td>
                        </tr>
                        <tr class="edit-form" id="edit-stop-<?php echo $stop['StopID']; ?>">
                            <td colspan="6">
                                <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
                                    <input type="hidden" name="action" value="update_stop">
                                    <input type="hidden" name="stop_id" value="<?php echo $stop['StopID']; ?>">
                                    <div style="flex:1;min-width:150px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Stop Name</label>
                                        <input type="text" name="stop_name" value="<?php echo htmlspecialchars($stop['StopName']); ?>" required>
                                    </div>
                                    <div style="flex:1;min-width:200px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Description</label>
                                        <input type="text" name="description" value="<?php echo htmlspecialchars($stop['Description']); ?>" required>
                                    </div>
                                    <button type="submit" class="save-btn">Save</button>
                                    <button type="button" class="cancel-btn" onclick="toggleEditStop(<?php echo $stop['StopID']; ?>)">Cancel</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Users Report -->
            <?php if (($report_type === 'users' || $report_type === 'all') && !empty($users_data)): ?>
            <div class="card">
                <div class="section-title"><i class="fas fa-users"></i> User Accounts (<?php echo count($users_data); ?>)</div>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>WMSU ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>User Type</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_data as $user): ?>
                        <tr id="user-<?php echo $user['UserID']; ?>">
                            <td><?php echo $user['UserID']; ?></td>
                            <td><?php echo htmlspecialchars($user['WMSUID']); ?></td>
                            <td><?php echo htmlspecialchars($user['FirstName']); ?></td>
                            <td><?php echo htmlspecialchars($user['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($user['UserType']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['CreatedAt'])); ?></td>
                            <td class="action-cell">
                                <button class="edit-btn" onclick="toggleEditUser(<?php echo $user['UserID']; ?>)">Edit</button>
                            </td>
                        </tr>
                        <tr class="edit-form" id="edit-user-<?php echo $user['UserID']; ?>">
                            <td colspan="7">
                                <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
                                    <div style="flex:1;min-width:150px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">First Name</label>
                                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['FirstName']); ?>" required>
                                    </div>
                                    <div style="flex:1;min-width:150px;">
                                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Last Name</label>
                                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['LastName']); ?>" required>
                                    </div>
                                    <button type="submit" class="save-btn">Save</button>
                                    <button type="button" class="cancel-btn" onclick="toggleEditUser(<?php echo $user['UserID']; ?>)">Cancel</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>

<script>
function toggleEditRoute(routeId) {
    const form = document.getElementById('edit-route-' + routeId);
    form.classList.toggle('show');
}

function toggleEditStop(stopId) {
    const form = document.getElementById('edit-stop-' + stopId);
    form.classList.toggle('show');
}

function toggleEditUser(userId) {
    const form = document.getElementById('edit-user-' + userId);
    form.classList.toggle('show');
}
</script>
</body>
</html>
