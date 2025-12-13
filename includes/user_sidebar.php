<?php 
$current = basename($_SERVER['PHP_SELF']);

// Get the logged-in user's first name and email
$user_first_name = 'User';
$user_email = '';
if (isset($_SESSION['user_id'])) {
	// Prefer session-stored email when available
	if (isset($_SESSION['email'])) {
		$user_email = $_SESSION['email'];
	}
	// This line requires db_connect.php to be included in the calling file
	if (isset($conn)) {
		$stmt = $conn->prepare("SELECT FirstName, Email FROM Users WHERE UserID = ?");
		$stmt->bind_param("i", $_SESSION['user_id']);
		$stmt->execute();
		$stmt->bind_result($firstName, $dbEmail);
		if ($stmt->fetch()) {
			$user_first_name = $firstName;
			if (empty($user_email) && !empty($dbEmail)) $user_email = $dbEmail;
		}
		$stmt->close();
	}
}
?>
<aside class="admin-sidebar">
	<div class="sidebar-header"><?php echo htmlspecialchars($user_first_name); ?></div>
	<?php if (!empty($user_email)): ?>
		<div style="padding:6px 16px; color:#cbd5e1; font-size:13px;"><?php echo htmlspecialchars($user_email); ?></div>
	<?php endif; ?>
	<ul class="sidebar-nav">
		<li><a href="/WMSUBUS/user/home.php" <?php if ($current === 'home.php') echo 'class="active"'; ?>>Home</a></li>
		<li><a href="/WMSUBUS/user/schedule_view.php" <?php if ($current === 'schedule_view.php') echo 'class="active"'; ?>>Schedules</a></li>
		<li><a href="/WMSUBUS/user/announcements_user.php" <?php if ($current === 'announcements_user.php') echo 'class="active"'; ?>>Announcements</a></li>
		<li><a href="/WMSUBUS/admin/dashboard.php">Admin Side</a></li>
		<li><a href="/WMSUBUS/user/logout_user.php" <?php if ($current === 'logout_user.php') echo 'class="active"'; ?>>Logout</a></li>
	</ul>
</aside>


