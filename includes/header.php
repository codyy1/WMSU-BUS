<?php
// Shared header/navigation
// Expects session to be started by the including file
$current = basename($_SERVER['PHP_SELF']);
$isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin';
?>
<header>
    <?php
    // Prepare notification count and latest items for non-admin users
    $unread_count = 0;
    $latest_notifications = [];
    if (isset($_SESSION['user_id']) && !$isAdmin && isset($conn)) {
        $uid = $_SESSION['user_id'];
        $table_exists = false;
        try {
            $tres = $conn->query("SHOW TABLES LIKE 'Notifications'");
            if ($tres && $tres->num_rows > 0) {
                $table_exists = true;
            }
        } catch (Throwable $e) {
            $table_exists = false;
        }

        if ($table_exists) {
            try {
                $count_sql = "SELECT COUNT(*) AS cnt FROM Notifications n WHERE NOT EXISTS (SELECT 1 FROM NotificationReads nr WHERE nr.NotificationID = n.NotificationID AND nr.UserID = ?)";
                $cstmt = $conn->prepare($count_sql);
                if ($cstmt) {
                    $cstmt->bind_param("i", $uid);
                    $cstmt->execute();
                    $cres = $cstmt->get_result();
                    if ($cres && $row = $cres->fetch_assoc()) $unread_count = $row['cnt'];
                    $cstmt->close();
                }

                $list_sql = "SELECT n.NotificationID, n.Type, n.Title, n.Message, n.CreatedAt, (SELECT COUNT(*) FROM NotificationReads nr WHERE nr.NotificationID = n.NotificationID AND nr.UserID = ?) AS IsRead FROM Notifications n ORDER BY n.CreatedAt DESC LIMIT 5";
                $lstmt = $conn->prepare($list_sql);
                if ($lstmt) {
                    $lstmt->bind_param("i", $uid);
                    $lstmt->execute();
                    $lres = $lstmt->get_result();
                    if ($lres) $latest_notifications = $lres->fetch_all(MYSQLI_ASSOC);
                    $lstmt->close();
                }
            } catch (Throwable $e) {
                // If anything goes wrong querying notifications, fall back to empty results
                $unread_count = 0;
                $latest_notifications = [];
            }
        } else {
            // Derive notifications from Announcements and Schedules for preview when Notifications table is not present
            try {
                $combined = [];
                $ann_q = $conn->query("SELECT AnnouncementID AS ItemID, 'Announcement' AS Type, Title, Content AS Message, PublishDate AS CreatedAt FROM Announcements ORDER BY PublishDate DESC LIMIT 5");
                if ($ann_q && $ann_q->num_rows > 0) {
                    while ($row = $ann_q->fetch_assoc()) {
                        $combined[] = [
                            'NotificationID' => 'ann-' . $row['ItemID'],
                            'Type' => $row['Type'],
                            'Title' => $row['Title'],
                            'Message' => $row['Message'],
                            'CreatedAt' => $row['CreatedAt'],
                            'IsRead' => 0
                        ];
                    }
                }

                $sched_q = $conn->query("SELECT s.ScheduleID AS ItemID, 'Schedule' AS Type, CONCAT('New schedule: ', r.RouteName) AS Title, CONCAT('Route ', r.RouteName, ' on ', s.DateOfService, ' (Driver: ', s.DriverName, ')') AS Message, s.DateOfService AS CreatedAt FROM Schedules s JOIN Routes r ON s.RouteID = r.RouteID ORDER BY s.DateOfService DESC LIMIT 5");
                if ($sched_q && $sched_q->num_rows > 0) {
                    while ($row = $sched_q->fetch_assoc()) {
                        $combined[] = [
                            'NotificationID' => 'sch-' . $row['ItemID'],
                            'Type' => $row['Type'],
                            'Title' => $row['Title'],
                            'Message' => $row['Message'],
                            'CreatedAt' => $row['CreatedAt'],
                            'IsRead' => 0
                        ];
                    }
                }

                // sort by CreatedAt desc
                usort($combined, function($a,$b){
                    return strtotime($b['CreatedAt']) <=> strtotime($a['CreatedAt']);
                });

                $unread_count = count($combined);
                $latest_notifications = array_slice($combined, 0, 5);
            } catch (Throwable $e) {
                $unread_count = 0;
                $latest_notifications = [];
            }
        }
    }
    ?>
    <nav style="display:flex; align-items:center; justify-content:space-between; padding:12px 24px; box-shadow:0 1px 3px rgba(0,0,0,0.08); gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <img src="/WMSUBUS/images/wmsu_logo.png" alt="WMSU Logo" style="width:50px; height:50px; object-fit:contain; flex-shrink:0;">
            <div style="flex:1; display:flex; align-items:center;">
                <?php if ($isAdmin): ?>
                    <div style="font-weight:700; font-size:18px; color:#fff;">WMSU BUS ADMIN</div>
                <?php else: ?>
                    <div style="font-weight:700; font-size:18px; color:#fff;">WMSU BUS SCHEDULING</div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:12px;">
            <?php if (!$isAdmin): ?>
                <div style="position:relative; display:flex; gap:8px; align-items:center;">
                    <?php if ($current === 'home.php'): ?>
                        <!-- Email / Compose button (only shown on home.php) -->
                        <button id="composeToggle" style="background:transparent;border:none;color:#fff;cursor:pointer;font-size:20px;position:relative;">
                            <i class="fas fa-envelope"></i>
                        </button>
                    <?php endif; ?>
                    <div style="position:relative;">
                        <button id="notifToggle" style="background:transparent;border:none;color:#fff;cursor:pointer;font-size:20px;position:relative;">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span style="position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; padding:2px 6px; border-radius:12px; font-size:12px; font-weight:700;"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notifDropdown" style="display:none; position:absolute; right:0; top:36px; width:320px; background:#fff; color:#111827; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 6px 24px rgba(0,0,0,0.12); z-index:1000;">
                            <div style="padding:12px; border-bottom:1px solid #f3f4f6; font-weight:700;">Notifications</div>
                            <div style="max-height:320px; overflow:auto;">
                                <?php if (empty($latest_notifications)): ?>
                                    <div style="padding:12px; color:#6b7280;">No notifications</div>
                                <?php else: ?>
                                    <?php foreach ($latest_notifications as $n): ?>
                                        <div style="padding:10px 12px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between;">
                                            <div style="flex:1;">
                                                <div style="font-weight:700; font-size:14px; color:#111827;"><?php echo htmlspecialchars($n['Title'] ?? ucfirst($n['Type'])); ?></div>
                                                <div style="font-size:12px; color:#6b7280; margin-top:6px;"><?php echo htmlspecialchars(substr($n['Message'],0,120)); ?><?php echo strlen($n['Message'])>120 ? '...' : ''; ?></div>
                                                <div style="font-size:11px; color:#9ca3af; margin-top:8px;"><?php echo date('M d, Y g:i A', strtotime($n['CreatedAt'])); ?></div>
                                            </div>
                                            <div style="margin-left:8px;">
                                                <?php if (!$n['IsRead']): ?>
                                                    <form method="POST" action="/WMSUBUS/user/notifications.php" style="margin:0;">
                                                        <input type="hidden" name="notification_id" value="<?php echo $n['NotificationID']; ?>">
                                                        <button type="submit" name="mark_read" style="background:#2563eb;border:none;color:#fff;padding:6px 8px;border-radius:6px;cursor:pointer;">Mark</button>
                                                    </form>
                                                <?php else: ?>
                                                    <div style="font-size:12px;color:#6b7280;">Read</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div style="padding:10px; text-align:center;">
                                <a href="/WMSUBUS/user/notifications.php" style="text-decoration:none;color:#2563eb;font-weight:700;">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- Compose Modal (only used on home.php) -->
        <?php if (!$isAdmin && $current === 'home.php'): ?>
            <div id="composeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
                <div style="width:520px; max-width:96%; background:#fff; border-radius:8px; padding:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <div style="font-weight:700;">Compose Message</div>
                        <button id="composeClose" style="background:transparent;border:none;font-size:18px;cursor:pointer;">&times;</button>
                    </div>
                    <form id="composeForm">
                        <div style="margin-bottom:8px;"><label style="font-weight:600;">Recipient WMSU ID</label><input type="text" name="recipient_wmsuid" required style="width:100%; padding:8px; border:1px solid #e5e7eb; border-radius:6px;"></div>
                        <div style="margin-bottom:8px;"><label style="font-weight:600;">Subject</label><input type="text" name="subject" style="width:100%; padding:8px; border:1px solid #e5e7eb; border-radius:6px;"></div>
                        <div style="margin-bottom:8px;"><label style="font-weight:600;">Message</label><textarea name="body" required rows="6" style="width:100%; padding:8px; border:1px solid #e5e7eb; border-radius:6px;"></textarea></div>
                        <div style="display:flex; gap:8px; justify-content:flex-end;">
                            <button type="button" id="composeCancel" style="background:#f3f4f6;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;">Cancel</button>
                            <button type="submit" id="composeSend" style="background:#2563eb;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;">Send</button>
                        </div>
                        <div id="composeMsg" style="margin-top:8px;color:#111827;display:none;"></div>
                    </form>
                </div>
            </div>
            <script>
                (function(){
                    const toggle = document.getElementById('composeToggle');
                    const modal = document.getElementById('composeModal');
                    const close = document.getElementById('composeClose');
                    const cancel = document.getElementById('composeCancel');
                    const form = document.getElementById('composeForm');
                    const msgBox = document.getElementById('composeMsg');

                    if (toggle) toggle.addEventListener('click', ()=>{ modal.style.display = 'flex'; });
                    if (close) close.addEventListener('click', ()=>{ modal.style.display='none'; });
                    if (cancel) cancel.addEventListener('click', ()=>{ modal.style.display='none'; });

                    form.addEventListener('submit', async function(e){
                        e.preventDefault();
                        msgBox.style.display = 'none';
                        const fd = new FormData(form);
                        try {
                            const res = await fetch('/WMSUBUS/user/send_message.php', {
                                method: 'POST',
                                body: fd,
                                credentials: 'same-origin'
                            });
                            const j = await res.json();
                            if (j.success) {
                                msgBox.style.color = '#065f46'; msgBox.textContent = 'Message sent.'; msgBox.style.display='block';
                                form.reset();
                                setTimeout(()=>{ modal.style.display='none'; msgBox.style.display='none'; }, 900);
                            } else {
                                msgBox.style.color = '#991b1b'; msgBox.textContent = j.error || 'Failed to send'; msgBox.style.display='block';
                            }
                        } catch (err) {
                            msgBox.style.color = '#991b1b'; msgBox.textContent = 'Network error'; msgBox.style.display='block';
                        }
                    });
                })();
            </script>
        <?php endif; ?>
    </nav>
    <script>
        document.addEventListener('click', function(e){
            const toggle = document.getElementById('notifToggle');
            const dropdown = document.getElementById('notifDropdown');
            if (!toggle) return;
            if (toggle.contains(e.target)) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                return;
            }
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
    <script>
        // Poll notifications endpoint every 20 seconds to update badge and dropdown
        (function(){
            const badgeSelector = function(){
                const btn = document.getElementById('notifToggle');
                if (!btn) return null;
                return btn.querySelector('span');
            };

            async function fetchAndUpdate() {
                try {
                    const res = await fetch('/WMSUBUS/user/get_notifications.php', { credentials: 'same-origin' });
                    if (!res.ok) return;
                    const data = await res.json();
                    const badge = badgeSelector();
                    // update badge
                    if (data.unread_count && data.unread_count > 0) {
                        if (badge) {
                            badge.textContent = data.unread_count;
                        } else {
                            const btn = document.getElementById('notifToggle');
                            if (btn) {
                                const span = document.createElement('span');
                                span.style.cssText = 'position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; padding:2px 6px; border-radius:12px; font-size:12px; font-weight:700;';
                                span.textContent = data.unread_count;
                                btn.appendChild(span);
                            }
                        }
                    } else {
                        if (badge && badge.parentNode) badge.parentNode.removeChild(badge);
                    }

                    // update dropdown list if open
                    const dropdown = document.getElementById('notifDropdown');
                    if (dropdown) {
                        const list = dropdown.querySelector('div[style*="max-height:320px"]');
                        if (list) {
                            list.innerHTML = '';
                            if (!data.notifications || data.notifications.length === 0) {
                                list.innerHTML = '<div style="padding:12px; color:#6b7280;">No notifications</div>';
                            } else {
                                for (const n of data.notifications) {
                                    const item = document.createElement('div');
                                    item.style.cssText = 'padding:10px 12px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between;';
                                    const left = document.createElement('div'); left.style.flex = '1';
                                    const title = document.createElement('div'); title.style.cssText = 'font-weight:700; font-size:14px; color:#111827;';
                                    title.textContent = n.Title || (n.Type || 'Notification');
                                    const msg = document.createElement('div'); msg.style.cssText = 'font-size:12px; color:#6b7280; margin-top:6px;';
                                    msg.textContent = (n.Message || '').substring(0,120) + ((n.Message||'').length>120? '...' : '');
                                    const date = document.createElement('div'); date.style.cssText = 'font-size:11px; color:#9ca3af; margin-top:8px;';
                                    date.textContent = new Date(n.CreatedAt).toLocaleString();
                                    left.appendChild(title); left.appendChild(msg); left.appendChild(date);

                                    const right = document.createElement('div'); right.style.marginLeft = '8px';
                                    if (!n.IsRead || n.IsRead == 0) {
                                        const form = document.createElement('form'); form.method='POST'; form.action='/WMSUBUS/user/notifications.php'; form.style.margin='0';
                                        const hid = document.createElement('input'); hid.type='hidden'; hid.name='notification_id'; hid.value = n.NotificationID;
                                        const btn = document.createElement('button'); btn.type='submit'; btn.name='mark_read'; btn.style.cssText = 'background:#2563eb;border:none;color:#fff;padding:6px 8px;border-radius:6px;cursor:pointer;'; btn.textContent='Mark';
                                        form.appendChild(hid); form.appendChild(btn); right.appendChild(form);
                                    } else {
                                        const read = document.createElement('div'); read.style.cssText='font-size:12px;color:#6b7280;'; read.textContent='Read'; right.appendChild(read);
                                    }

                                    item.appendChild(left); item.appendChild(right);
                                    list.appendChild(item);
                                }
                            }
                        }
                    }
                } catch (e) {
                    // ignore fetch errors silently
                }
            }

            // initial fetch and then poll
            fetchAndUpdate();
            setInterval(fetchAndUpdate, 20000);
        })();
    </script>
</header>
