<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
check_role(1);

$admin = get_employee_info($conn, $_SESSION['employee_id']);

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
$total_employees = get_total_employees($conn);
$pending_leaves = count_pending_leaves($conn);
$today_present = $conn->query("SELECT COUNT(DISTINCT employee_id) as count FROM attendance WHERE attendance_date = CURDATE() AND status = 'present'")->fetch_assoc()['count'];
$today_absent = $conn->query("SELECT COUNT(DISTINCT e.employee_id) as count FROM employees e WHERE e.status = 'active' AND e.employee_id NOT IN (SELECT DISTINCT employee_id FROM attendance WHERE attendance_date = CURDATE())")->fetch_assoc()['count'];
$on_leave_today = $conn->query("SELECT COUNT(DISTINCT e.employee_id) as count FROM employees e JOIN leave_requests lr ON e.employee_id = lr.employee_id WHERE lr.status = 'approved' AND CURDATE() BETWEEN lr.start_date AND lr.end_date")->fetch_assoc()['count'];

// Get users for table
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$where_clause = "WHERE 1=1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where_clause .= " AND (u.username LIKE '%$search%' OR e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.employee_code LIKE '%$search%')";
}
if (!empty($role_filter)) {
    $role_filter = (int)$role_filter;
    $where_clause .= " AND u.role_id = $role_filter";
}

$users = $conn->query("SELECT u.*, e.first_name, e.last_name, e.employee_code, r.role_name FROM users u JOIN employees e ON u.employee_id = e.employee_id JOIN roles r ON u.role_id = r.role_id $where_clause ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get system logs (last 20 actions)
$logs = $conn->query("
    SELECT 
        'user_created' as action_type,
        CONCAT('👤 Created user: ', username) as action_detail,
        u.created_at as timestamp
    FROM users u
    UNION ALL
    SELECT 
        'holiday_added' as action_type,
        CONCAT('📅 Added holiday: ', holiday_name) as action_detail,
        h.created_at as timestamp
    FROM holidays h
    UNION ALL
    SELECT 
        'leave_type_added' as action_type,
        CONCAT('📋 Added leave type: ', leave_type_name) as action_detail,
        lt.created_at as timestamp
    FROM leave_types lt
    UNION ALL
    SELECT 
        'employee_added' as action_type,
        CONCAT('👥 Added employee: ', first_name, ' ', last_name) as action_detail,
        created_at as timestamp
    FROM employees
    ORDER BY timestamp DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Admin Dashboard - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .search-filter-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .search-filter-box .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .search-filter-box input,
        .search-filter-box select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .logs-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .log-item {
            padding: 12px;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-item .action {
            flex: 1;
        }
        
        .log-item .time {
            color: #999;
            font-size: 12px;
            white-space: nowrap;
            margin-left: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card h4 {
            color: #666;
            font-size: 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>HR Administrator Dashboard</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($admin['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Quick Statistics -->
        <div class="dashboard">
            <div class="stat-box">
                <h4>👥 Total Users</h4>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <p>System accounts</p>
            </div>
            <div class="stat-box">
                <h4>✅ Active Users</h4>
                <div class="stat-number"><?php echo $active_users; ?></div>
                <p>Currently active</p>
            </div>
            <div class="stat-box">
                <h4>👨‍💼 Employees</h4>
                <div class="stat-number"><?php echo $total_employees; ?></div>
                <p>On system</p>
            </div>
            <div class="stat-box">
                <h4>⏳ Pending Leaves</h4>
                <div class="stat-number"><?php echo $pending_leaves; ?></div>
                <p>Awaiting approval</p>
            </div>
        </div>

        <!-- Today's Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div class="stat-card">
                <h4>✅ Present Today</h4>
                <div class="number"><?php echo $today_present; ?></div>
            </div>
            <div class="stat-card">
                <h4>❌ Absent Today</h4>
                <div class="number"><?php echo $today_absent; ?></div>
            </div>
            <div class="stat-card">
                <h4>📋 On Leave Today</h4>
                <div class="number"><?php echo $on_leave_today; ?></div>
            </div>
        </div>

        <!-- System Management -->
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 30px;">
            <h3>⚙️ System Management</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <a href="manage_employees.php" class="btn btn-primary" style="text-align: center; background: rgba(255,255,255,0.2);">👥 Manage Employees</a>
                <a href="manage_users.php" class="btn btn-primary" style="text-align: center; background: rgba(255,255,255,0.2);">👤 Manage Users</a>
                <a href="manage_holidays.php" class="btn btn-primary" style="text-align: center; background: rgba(255,255,255,0.2);">📅 Holidays</a>
                <a href="manage_leaves.php" class="btn btn-primary" style="text-align: center; background: rgba(255,255,255,0.2);">📋 Leave Types</a>
            </div>
        </div>

        <!-- System Activity Log -->
        <div class="logs-container">
            <h3>📜 System Activity Log (Recent Actions)</h3>
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                <div class="log-item">
                    <div class="action">
                        <strong><?php echo htmlspecialchars($log['action_detail']); ?></strong>
                    </div>
                    <div class="time"><?php echo format_datetime($log['timestamp']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">No activity yet</p>
            <?php endif; ?>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-box">
            <h3 style="margin-bottom: 15px;">🔍 Search & Filter Users</h3>
            <form method="GET" action="">
                <div class="grid-3">
                    <input type="text" name="search" placeholder="Search by name, username, or employee ID..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="role">
                        <option value="">-- All Roles --</option>
                        <option value="2" <?php echo ($role_filter == 2 ? 'selected' : ''); ?>>HR Staff</option>
                        <option value="3" <?php echo ($role_filter == 3 ? 'selected' : ''); ?>>Employee</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">🔍 Search</button>
                </div>
            </form>
            <?php if (!empty($search) || !empty($role_filter)): ?>
                <a href="dashboard.php" style="margin-top: 10px; display: inline-block;" class="btn btn-secondary">Clear Filters</a>
            <?php endif; ?>
        </div>

        <!-- System Users Table -->
        <div class="table-container">
            <h3>👤 System Users (<?php echo count($users); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Employee ID</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo $user['employee_code']; ?></td>
                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['last_login'] ? format_datetime($user['last_login']) : '-'; ?></td>
                            <td>
                                <a href="user_status.php?user_id=<?php echo $user['user_id']; ?>&status=<?php echo ($user['status'] === 'active' ? 'inactive' : 'active'); ?>" class="btn <?php echo ($user['status'] === 'active' ? 'btn-danger' : 'btn-success'); ?>" style="padding: 5px 10px; font-size: 12px;">
                                    <?php echo ($user['status'] === 'active' ? '🔒 Deactivate' : '🔓 Activate'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No users found matching your criteria</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>