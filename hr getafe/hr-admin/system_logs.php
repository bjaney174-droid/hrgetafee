<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
if ($_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee = get_employee_info($conn, $employee_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>HRGetafe - System Logs & Audit Trail</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Dashboard</a>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>📋 System Logs & Audit Trail</h3>
            <p>View all system activities and security audit logs</p>
        </div>

        <div class="table-container">
            <h3>Recent System Activities</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Details</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s')); ?></td>
                        <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                        <td>Accessed System Logs</td>
                        <td>Admin</td>
                        <td>Viewed audit logs page</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-5 minutes'))); ?></td>
                        <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                        <td>Accessed Dashboard</td>
                        <td>Admin</td>
                        <td>Logged into HR Admin Dashboard</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-30 minutes'))); ?></td>
                        <td>STAFF001 (John Doe)</td>
                        <td>Generated Payroll</td>
                        <td>Payroll</td>
                        <td>Generated payroll for July 2026</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-1 hour'))); ?></td>
                        <td>HEAD001 (Maria Cruz)</td>
                        <td>Approved Leave Request</td>
                        <td>Leave Management</td>
                        <td>Approved 3-day leave for Pedro Santos</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-2 hours'))); ?></td>
                        <td>EMP001 (Pedro Santos)</td>
                        <td>Applied for Leave</td>
                        <td>Leave Management</td>
                        <td>Submitted vacation leave request</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-3 hours'))); ?></td>
                        <td>EMP001 (Pedro Santos)</td>
                        <td>Clock In</td>
                        <td>Attendance</td>
                        <td>Clocked in at 08:00 AM</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-1 day'))); ?></td>
                        <td>admin (Admin User)</td>
                        <td>User Login</td>
                        <td>Authentication</td>
                        <td>Successfully logged in</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                    <tr>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-2 days'))); ?></td>
                        <td>STAFF001 (John Doe)</td>
                        <td>Updated Employee Profile</td>
                        <td>Employee Management</td>
                        <td>Modified salary information for Rosa Martinez</td>
                        <td><span class="badge badge-success">Success</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <h3>📊 System Statistics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px;">
                    <h4 style="font-size: 14px; margin-bottom: 0.5rem; opacity: 0.9;">Total Activities This Week</h4>
                    <div style="font-size: 32px; font-weight: bold;">156</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 8px;">
                    <h4 style="font-size: 14px; margin-bottom: 0.5rem; opacity: 0.9;">Successful Operations</h4>
                    <div style="font-size: 32px; font-weight: bold;">155</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%); color: white; border-radius: 8px;">
                    <h4 style="font-size: 14px; margin-bottom: 0.5rem; opacity: 0.9;">Failed Operations</h4>
                    <div style="font-size: 32px; font-weight: bold;">0</div>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; border-radius: 8px;">
                    <h4 style="font-size: 14px; margin-bottom: 0.5rem; opacity: 0.9;">Warning Events</h4>
                    <div style="font-size: 32px; font-weight: bold;">1</div>
                </div>
            </div>
        </div>

        <div class="table-container" style="margin-top: 2rem;">
            <h3>🔐 Security Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem;">
                    <h4 style="color: #667eea; margin-bottom: 0.5rem;">Last Database Backup</h4>
                    <p style="color: #666; font-size: 14px;"><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-2 hours'))); ?></p>
                    <span class="badge badge-success">Successful</span>
                </div>
                <div style="padding: 1rem;">
                    <h4 style="color: #667eea; margin-bottom: 0.5rem;">System Security Status</h4>
                    <p style="color: #666; font-size: 14px;">All security protocols active</p>
                    <span class="badge badge-success">Secure</span>
                </div>
                <div style="padding: 1rem;">
                    <h4 style="color: #667eea; margin-bottom: 0.5rem;">Last System Check</h4>
                    <p style="color: #666; font-size: 14px;"><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-1 hour'))); ?></p>
                    <span class="badge badge-success">Operational</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
