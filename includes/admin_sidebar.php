<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="admin-sidebar">
	<div class="sidebar-header">WMSU Admin</div>
	<ul class="sidebar-nav">
		<li><a href="/WMSUBUS/admin/dashboard.php" <?php if ($current === 'dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>
		<li><a href="/WMSUBUS/admin/manage_routes.php" <?php if ($current === 'manage_routes.php') echo 'class="active"'; ?>>Routes &amp; Stops</a></li>
		<li><a href="/WMSUBUS/admin/manage_schedules.php" <?php if ($current === 'manage_schedules.php') echo 'class="active"'; ?>>Schedules</a></li>
		<li><a href="/WMSUBUS/admin/manage_accounts.php" <?php if ($current === 'manage_accounts.php') echo 'class="active"'; ?>>User Accounts</a></li>
		<li><a href="/WMSUBUS/admin/announcements.php" <?php if ($current === 'announcements.php') echo 'class="active"'; ?>>Announcements</a></li>
		<li><a href="/WMSUBUS/admin/reports.php" <?php if ($current === 'reports.php') echo 'class="active"'; ?>>Reports</a></li>
		<li><a href="/WMSUBUS/user/index_user.php">User Side</a></li>
		<li><a href="/WMSUBUS/admin/logout.php" <?php if ($current === 'logout.php') echo 'class="active"'; ?>>Logout</a></li>
	</ul>
</aside>


