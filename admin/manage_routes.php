<?php
include __DIR__ . '/db_connect.php'; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: index.php');
    exit();
}


$route_message = '';
if (isset($_POST['add_route'])) {
    $route_name = trim($_POST['route_name'] ?? '');
    $start_location = trim($_POST['start_location'] ?? '');
    $end_location = trim($_POST['end_location'] ?? '');
    if ($route_name && $start_location && $end_location) {
        $stmt = $conn->prepare("INSERT INTO Routes (RouteName, StartLocation, EndLocation) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $route_name, $start_location, $end_location);
        if ($stmt->execute()) {
            $route_message = "Route added successfully!";
            // Create a notification for the new route
            $new_route_id = $stmt->insert_id;
            try {
                $nstmt = $conn->prepare("INSERT INTO Notifications (Type, RelatedID, Title, Message) VALUES ('Route', ?, ?, ?)");
                if ($nstmt) {
                    $title = 'New route: ' . $route_name;
                    $msg = 'Route "' . $route_name . '" added (from ' . $start_location . ' to ' . $end_location . ').';
                    $nstmt->bind_param("iss", $new_route_id, $title, $msg);
                    $nstmt->execute();
                    $nstmt->close();
                }
            } catch (Throwable $e) {
                // ignore notification errors
            }
        } else {
            $route_message = "Error adding route: " . $conn->error;
        }
        $stmt->close();
    } else {
        $route_message = "Please fill in all route fields.";
    }
}


$stop_message = '';
if (isset($_POST['add_stop'])) {
    $stop_name = trim($_POST['stop_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    if ($stop_name && $description) {
        $stmt = $conn->prepare("INSERT INTO Stops (StopName, Description, Latitude, Longitude) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdd", $stop_name, $description, $latitude, $longitude);
        if ($stmt->execute()) {
                $stop_message = "Stop added successfully!";
                // Create a notification for the new stop
                $new_stop_id = $stmt->insert_id;
                try {
                    $nstmt = $conn->prepare("INSERT INTO Notifications (Type, RelatedID, Title, Message) VALUES ('Stop', ?, ?, ?)");
                    if ($nstmt) {
                        $title = 'New stop: ' . $stop_name;
                        $msg = 'Stop "' . $stop_name . '" has been added.';
                        $nstmt->bind_param("iss", $new_stop_id, $title, $msg);
                        $nstmt->execute();
                        $nstmt->close();
                    }
                } catch (Throwable $e) {
                    // ignore notification errors
                }
        } else {
            $stop_message = "Error adding stop: " . $conn->error;
        }
        $stmt->close();
    } else {
        $stop_message = "Please fill in all stop fields.";
    }
}


$all_routes = $conn->query("SELECT * FROM Routes ORDER BY RouteName");
$all_stops = $conn->query("SELECT * FROM Stops ORDER BY StopName");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Routes - WMSU Transport</title>
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
        .alert-success { background:#e8f5e9; padding:10px; border-radius:4px; margin-bottom:10px; }
        form label { display:block; font-weight:600; margin:10px 0 6px; color:#111827; }
        form input[type="text"], input[type="number"] { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; background:#fff; outline:none; }
        .btn { background:#2563eb; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn:hover { background:#1d4ed8; }
        table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
        thead th { background:#f3f4f6; text-align:left; padding:12px; font-weight:700; color:#111827; }
        tbody td { padding:12px; border-top:1px solid #e5e7eb; color:#111827; }
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
    <div class="card" style="margin-bottom:2rem;">
    <h1>Route & Stop Management</h1>
    <div class="card" style="margin-bottom:2rem;">
        <h2>Add New Route</h2>
        <?php if ($route_message): ?><div class="alert-success"><?php echo htmlspecialchars($route_message); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <label for="route_name">Route Name</label>
                <input type="text" id="route_name" name="route_name" required placeholder="e.g. WMSU Main Gate to City Hall">
            </div>
            <div class="form-row">
                <label for="start_location">Start Location</label>
                <input type="text" id="start_location" name="start_location" required placeholder="e.g. WMSU Main Gate">
            </div>
            <div class="form-row">
                <label for="end_location">End Location</label>
                <input type="text" id="end_location" name="end_location" required placeholder="e.g. City Hall Bus Terminal">
            </div>
            <button type="submit" name="add_route" class="btn">Save Route</button>
        </form>
    </div>

    <div class="card" style="margin-bottom:2rem;">
        <h2>Add New Stop Location</h2>
        <?php if ($stop_message): ?><div class="alert-success"><?php echo htmlspecialchars($stop_message); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <label for="stop_name">Stop Name</label>
                <input type="text" id="stop_name" name="stop_name" required placeholder="e.g. Grandstop Drop Off">
            </div>
            <div class="form-row">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" required placeholder="Short description">
            </div>
            <div class="form-row">
                <label for="latitude">Latitude</label>
                <input type="number" step="any" id="latitude" name="latitude" placeholder="Optional">
            </div>
            <div class="form-row">
                <label for="longitude">Longitude</label>
                <input type="number" step="any" id="longitude" name="longitude" placeholder="Optional">
            </div>
            <button type="submit" name="add_stop" class="btn">Save Stop</button>
        </form>
    </div>

    <div class="card" style="margin-bottom:2rem;">
        <h2>Existing Routes</h2>
        <table>
            <thead>
                <tr>
                    <th>Route Name</th>
                    <th>Start Location</th>
                    <th>End Location</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $all_routes->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['RouteName']); ?></td>
                    <td><?php echo htmlspecialchars($row['StartLocation']); ?></td>
                    <td><?php echo htmlspecialchars($row['EndLocation']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Existing Stops</h2>
        <table>
            <thead>
                <tr>
                    <th>Stop Name</th>
                    <th>Description</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $all_stops->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['StopName']); ?></td>
                    <td><?php echo htmlspecialchars($row['Description']); ?></td>
                    <td><?php echo htmlspecialchars($row['Latitude']); ?></td>
                    <td><?php echo htmlspecialchars($row['Longitude']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>