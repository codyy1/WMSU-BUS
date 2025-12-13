<?php
include __DIR__ . '/db_connect.php'; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$action_type = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_id = $_POST['registration_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if ($registration_id && ($action === 'approve' || $action === 'reject')) {
        $registration_id = intval($registration_id);
        
        // Get registration details
        $stmt = $conn->prepare("SELECT WMSUID, FirstName, LastName, PasswordHash, UserType, Email FROM UserRegistrations WHERE RegistrationID = ?");
        $stmt->bind_param("i", $registration_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $registration = $result->fetch_assoc();
        $stmt->close();
        
        if ($registration) {
            if ($action === 'approve') {
                // Check if WMSUID already exists in Users table
                $check_stmt = $conn->prepare("SELECT UserID FROM Users WHERE WMSUID = ?");
                $check_stmt->bind_param("s", $registration['WMSUID']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    // Insert into Users table (include Email)
                    $insert_stmt = $conn->prepare("INSERT INTO Users (WMSUID, FirstName, LastName, Email, PasswordHash, UserType) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssssss", $registration['WMSUID'], $registration['FirstName'], $registration['LastName'], $registration['Email'], $registration['PasswordHash'], $registration['UserType']);
                    
                    if ($insert_stmt->execute()) {
                        // Update registration status to Approved
                        $update_stmt = $conn->prepare("UPDATE UserRegistrations SET Status = 'Approved' WHERE RegistrationID = ?");
                        $update_stmt->bind_param("i", $registration_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        $message = "Account approved successfully! User can now login.";
                        $action_type = 'success';
                    } else {
                        $message = "Error approving account: " . $conn->error;
                        $action_type = 'error';
                    }
                    $insert_stmt->close();
                } else {
                    $message = "User already exists in the system.";
                    $action_type = 'error';
                }
                $check_stmt->close();
                
            } elseif ($action === 'reject') {
                // Update registration status to Rejected
                $update_stmt = $conn->prepare("UPDATE UserRegistrations SET Status = 'Rejected' WHERE RegistrationID = ?");
                $update_stmt->bind_param("i", $registration_id);
                
                if ($update_stmt->execute()) {
                    $message = "Account rejected successfully!";
                    $action_type = 'success';
                } else {
                    $message = "Error rejecting account: " . $conn->error;
                    $action_type = 'error';
                }
                $update_stmt->close();
            }
        } else {
            $message = "Registration not found.";
            $action_type = 'error';
        }
    }
}

// Fetch pending registrations
$pending_registrations = $conn->query("SELECT RegistrationID, WMSUID, FirstName, LastName, Email, UserType, CreatedAt FROM UserRegistrations WHERE Status = 'Pending' ORDER BY CreatedAt DESC");

// Fetch approved registrations
$approved_registrations = $conn->query("SELECT RegistrationID, WMSUID, FirstName, LastName, Email, UserType, CreatedAt FROM UserRegistrations WHERE Status = 'Approved' ORDER BY CreatedAt DESC");

// Fetch rejected registrations
$rejected_registrations = $conn->query("SELECT RegistrationID, WMSUID, FirstName, LastName, Email, UserType, CreatedAt FROM UserRegistrations WHERE Status = 'Rejected' ORDER BY CreatedAt DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User Accounts - WMSU Transport</title>
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
        .alert-success { background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:4px; margin-bottom:16px; border-left:4px solid #2e7d32; }
        .alert-error { background:#ffebee; color:#c62828; padding:12px; border-radius:4px; margin-bottom:16px; border-left:4px solid #c62828; }
        table { width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
        thead th { background:#f3f4f6; text-align:left; padding:12px; font-weight:700; color:#111827; }
        tbody td { padding:12px; border-top:1px solid #e5e7eb; color:#111827; }
        .btn { background:#2563eb; color:#fff; border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; margin-right:6px; }
        .btn:hover { background:#1d4ed8; }
        .btn-approve { background:#16a34a; }
        .btn-approve:hover { background:#15803d; }
        .btn-reject { background:#dc2626; }
        .btn-reject:hover { background:#b91c1c; }
        .btn-small { padding:6px 10px; font-size:12px; }
        .badge { display:inline-block; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; }
        .badge-pending { background:#fef3c7; color:#92400e; }
        .badge-approved { background:#dcfce7; color:#166534; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }
        .section-title { margin-top:24px; margin-bottom:16px; font-size:18px; font-weight:700; color:#111827; }
        .form-row { margin-bottom:12px; }
        form label { display:block; font-weight:600; margin-bottom:6px; color:#111827; }
        form input, form select { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; background:#fff; outline:none; }
        form input:focus, form select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37, 99, 235, 0.1); }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4); }
        .modal.show { display:block; }
        .modal-content { background-color:#fefefe; margin:10% auto; padding:20px; border:1px solid #888; border-radius:12px; width:90%; max-width:400px; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
        .modal-close { color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer; }
        .modal-close:hover { color:#000; }
        .modal-buttons { display:flex; gap:10px; margin-top:20px; justify-content:flex-end; }
        .no-data { text-align:center; color:#6b7280; padding:20px; }
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
                <h1>User Account Management</h1>
                <p>Review and approve or reject user registration requests.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert-<?php echo $action_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Pending Accounts Section -->
            <div class="card">
                <div class="section-title">
                    Pending Approvals (<?php echo $pending_registrations->num_rows; ?>)
                </div>
                
                <?php if ($pending_registrations->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>WMSU ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['WMSUID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['UserType']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($row['CreatedAt'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="registration_id" value="<?php echo $row['RegistrationID']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve btn-small">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="registration_id" value="<?php echo $row['RegistrationID']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-reject btn-small">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No pending registrations at this time.</div>
                <?php endif; ?>
            </div>

            <!-- Approved Accounts Section -->
            <div class="card">
                <div class="section-title">
                    Approved Accounts (<?php echo $approved_registrations->num_rows; ?>)
                </div>
                
                <?php if ($approved_registrations->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>WMSU ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Approved On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $approved_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['WMSUID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['UserType']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($row['CreatedAt'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No approved registrations yet.</div>
                <?php endif; ?>
            </div>

            <!-- Rejected Accounts Section -->
            <div class="card">
                <div class="section-title">
                    Rejected Accounts (<?php echo $rejected_registrations->num_rows; ?>)
                </div>
                
                <?php if ($rejected_registrations->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>WMSU ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Rejected On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $rejected_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['WMSUID']); ?></td>
                                    <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['UserType']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($row['CreatedAt'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No rejected registrations.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
