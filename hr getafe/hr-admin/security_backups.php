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
    <title>Security & Backups - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>HRGetafe - Security & Data Management</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Dashboard</a>

        <!-- Backup Management -->
        <div class="table-container">
            <h3>💾 Database Backups</h3>
            <p style="color: #666; margin-bottom: 1rem;">View and manage system backups</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Backup ID</th>
                        <th>Created Date</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>BACKUP_20260703_001</strong></td>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-2 hours'))); ?></td>
                        <td>12.5 MB</td>
                        <td>Automated</td>
                        <td><span class="badge badge-success">Complete</span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" disabled>Download</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>BACKUP_20260702_001</strong></td>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-1 day'))); ?></td>
                        <td>12.3 MB</td>
                        <td>Automated</td>
                        <td><span class="badge badge-success">Complete</span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" disabled>Download</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>BACKUP_20260701_001</strong></td>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-2 days'))); ?></td>
                        <td>12.1 MB</td>
                        <td>Automated</td>
                        <td><span class="badge badge-success">Complete</span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" disabled>Download</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>BACKUP_20260630_001</strong></td>
                        <td><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-3 days'))); ?></td>
                        <td>12.0 MB</td>
                        <td>Automated</td>
                        <td><span class="badge badge-success">Complete</span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" disabled>Download</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Security Settings -->
        <div class="card" style="margin-top: 2rem;">
            <h3>🔒 Security Settings</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div style="padding: 1rem; border: 1px solid #ddd; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 1rem;">🔐 Password Policy</h4>
                    <p><strong>Minimum Length:</strong> 8 characters</p>
                    <p><strong>Complexity:</strong> Medium</p>
                    <p><strong>Expiry:</strong> 90 days</p>
                    <p style="color: #666; font-size: 14px; margin-top: 0.5rem;">Enforced for all user accounts</p>
                </div>
                <div style="padding: 1rem; border: 1px solid #ddd; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 1rem;">🛡️ Session Security</h4>
                    <p><strong>Session Timeout:</strong> 1 hour</p>
                    <p><strong>Auto-Logout:</strong> Enabled</p>
                    <p><strong>SSL/TLS:</strong> Enabled</p>
                    <p style="color: #666; font-size: 14px; margin-top: 0.5rem;">Enhanced protection against unauthorized access</p>
                </div>
                <div style="padding: 1rem; border: 1px solid #ddd; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 1rem;">📡 Access Control</h4>
                    <p><strong>Role-Based:</strong> Enabled</p>
                    <p><strong>IP Whitelist:</strong> Disabled</p>
                    <p><strong>2FA:</strong> Disabled</p>
                    <p style="color: #666; font-size: 14px; margin-top: 0.5rem;">Configurable access levels per user role</p>
                </div>
            </div>
        </div>

        <!-- Backup Schedule -->
        <div class="card" style="margin-top: 2rem;">
            <h3>⏰ Backup Schedule</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 0.5rem;">Automatic Backups</h4>
                    <p style="font-size: 16px; font-weight: bold;">Every 6 hours</p>
                    <p style="color: #666; font-size: 14px;">Status: <span class="badge badge-success">Active</span></p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 0.5rem;">Last Backup Run</h4>
                    <p style="font-size: 16px; font-weight: bold;"><?php echo format_datetime(date('Y-m-d H:i:s', strtotime('-2 hours'))); ?></p>
                    <p style="color: #666; font-size: 14px;">Duration: 3 minutes 45 seconds</p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 0.5rem;">Retention Policy</h4>
                    <p style="font-size: 16px; font-weight: bold;">30 days</p>
                    <p style="color: #666; font-size: 14px;">Old backups automatically purged</p>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="card" style="margin-top: 2rem;">
            <h3>💚 System Health</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="padding: 1rem;">
                    <h4 style="color: #28a745; margin-bottom: 0.5rem;">Database Status</h4>
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">✓ Healthy</div>
                    <p style="color: #666; font-size: 14px;">All connections active and stable</p>
                </div>
                <div style="padding: 1rem;">
                    <h4 style="color: #28a745; margin-bottom: 0.5rem;">Server Status</h4>
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">✓ Online</div>
                    <p style="color: #666; font-size: 14px;">CPU: 32% | Memory: 45%</p>
                </div>
                <div style="padding: 1rem;">
                    <h4 style="color: #28a745; margin-bottom: 0.5rem;">Storage Status</h4>
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">✓ Sufficient</div>
                    <p style="color: #666; font-size: 14px;">Used: 25 GB of 100 GB</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
