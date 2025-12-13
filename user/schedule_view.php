<?php
include __DIR__ . '/../admin/db_connect.php';


$routes_result = $conn->query("SELECT RouteID, RouteName FROM Routes WHERE IsActive = TRUE");


$selected_route_id = isset($_GET['route_id']) ? $_GET['route_id'] : 
    ($routes_result->num_rows > 0 ? $routes_result->fetch_assoc()['RouteID'] : null);
$routes_result->data_seek(0); 

$schedule_rows = [];
$assignment = null;
$selected_route_name = null;
if ($selected_route_id) {
    // Load stops for the route (avoid get_result dependency)
    $sql = "SELECT rs.StopOrder, s.StopName, rs.ScheduledTime
            FROM RouteStops rs
            JOIN Stops s ON rs.StopID = s.StopID
            WHERE rs.RouteID = ?
            ORDER BY rs.StopOrder";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_route_id);
    $stmt->execute();
    $stmt->bind_result($stopOrder, $stopName, $scheduledTime);
    while ($stmt->fetch()) {
        $schedule_rows[] = ['StopOrder' => $stopOrder, 'StopName' => $stopName, 'ScheduledTime' => $scheduledTime];
    }
    $stmt->close();

    // Get today's assignment (avoid get_result dependency)
    $today = date("Y-m-d");
    $assignment_sql = "SELECT s.DriverName, v.PlateNumber, s.Status
                       FROM Schedules s
                       JOIN Vehicles v ON s.VehicleID = v.VehicleID
                       WHERE s.RouteID = ? AND s.DateOfService = ? LIMIT 1";
    $assign_stmt = $conn->prepare($assignment_sql);
    $assign_stmt->bind_param("is", $selected_route_id, $today);
    $assign_stmt->execute();
    $assign_stmt->bind_result($driverName, $plateNumber, $status);
    if ($assign_stmt->fetch()) {
        $assignment = ['DriverName' => $driverName, 'PlateNumber' => $plateNumber, 'Status' => $status];
    }
    $assign_stmt->close();

    // Get route name for display
    $route_q = $conn->prepare("SELECT RouteName FROM Routes WHERE RouteID = ? LIMIT 1");
    $route_q->bind_param("i", $selected_route_id);
    $route_q->execute();
    $route_q->bind_result($rname);
    if ($route_q->fetch()) $selected_route_name = $rname;
    $route_q->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WMSU Bus Schedule</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
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
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,0.04); }
    .stack { display:grid; gap:16px; }
    table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    thead th { background:#f3f4f6; text-align:left; padding:12px; font-weight:700; color:#111827; }
    tbody td { padding:12px; border-top:1px solid #e5e7eb; color:#111827; }
    @media (max-width: 900px) { .admin-layout { flex-direction:column; } .admin-sidebar { width:100%; height:auto; position:relative; } }
</style>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
    <div class="admin-main">
    <div class="stack">
    <div class="card">
    <h1>WMSU Bus Schedule - Daily View</h1>

    <form method="GET" style="margin-bottom: 20px;">
        <label for="route_id">Select Route:</label>
        <select id="route_id" name="route_id" onchange="this.form.submit()">
            <?php while ($row = $routes_result->fetch_assoc()): ?>
                <option value="<?php echo $row['RouteID']; ?>" 
                    <?php if ($row['RouteID'] == $selected_route_id) echo 'selected'; ?>>
                    <?php echo $row['RouteName']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
    
    <?php if ($selected_route_id && count($schedule_rows) > 0): ?>
        <h2>Route Details: 
            <?php echo $selected_route_name ? htmlspecialchars($selected_route_name) : 'Selected Route'; ?>
        </h2>
        
        <?php if ($assignment): ?>
            <p><strong>Today's Assignment (<?php echo date("F j, Y"); ?>):</strong></p>
            <ul>
                <li>Vehicle Plate: **<?php echo $assignment['PlateNumber']; ?>**</li>
                <li>Driver: **<?php echo $assignment['DriverName']; ?>**</li>
                <li>Current Status: **<?php echo $assignment['Status']; ?>**</li>
            </ul>
        <?php else: ?>
            <p style="color:red;">**No trip assigned to this route for today.**</p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Stop Name</th>
                    <th>Scheduled Time (Static)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedule_rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['StopOrder']); ?></td>
                    <td><?php echo htmlspecialchars($row['StopName']); ?></td>
                    <td>**<?php echo date("h:i A", strtotime($row['ScheduledTime'])); ?>**</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($routes_result->num_rows === 0): ?>
        <p>No routes are currently defined in the system.</p>
    <?php endif; ?>
    </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>