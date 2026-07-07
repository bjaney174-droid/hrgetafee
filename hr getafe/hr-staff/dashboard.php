<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
if ($_SESSION['role_id'] != 2) {
    header("Location: ../login.php");
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee = get_employee_info($conn, $employee_id);

// Get statistics
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch_assoc()['count'];
$total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$pending_leaves = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$today_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = CURDATE() AND status = 'present'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Staff Dashboard - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>HRGetafe - HR Staff Dashboard</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Quick Statistics -->
        <div class="dashboard">
            <div class="card">
                <h3>👥 Total Employees</h3>
                <div class="stat-number"><?php echo $total_employees; ?></div>
                <p>Active staff</p>
            </div>
            <div class="card">
                <h3>🏢 Departments</h3>
                <div class="stat-number"><?php echo $total_departments; ?></div>
                <p>Organization units</p>
            </div>
            <div class="card">
                <h3>⏳ Pending Leaves</h3>
                <div class="stat-number"><?php echo $pending_leaves; ?></div>
                <p>Awaiting approval</p>
            </div>
            <div class="card">
                <h3>✅ Present Today</h3>
                <div class="stat-number"><?php echo $today_present; ?></div>
                <p>Clocked in</p>
            </div>
        </div>

        <!-- Main Features Section -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h3>🎯 Core HR Management Features</h3>
            <p>Access all employee management tools and reports</p>
        </div>

        <!-- Core Features Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <a href="dashboard.php" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>👥 Employee Management</h3>
                    <p>Add, edit, and manage employee records with complete information</p>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Go to Dashboard →</button>
                </div>
            </a>

            <a href="employee_directory.php" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>📋 Employee Directory</h3>
                    <p>View all employees with their profiles, departments, and contact info</p>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;">View Directory →</button>
                </div>
            </a>

            <a href="payroll.php" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>💰 Payroll Management</h3>
                    <p>Generate and process monthly payroll for all employees</p>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Payroll →</button>
                </div>
            </a>

            <a href="generate_reports.php" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>📊 Reports & DTR</h3>
                    <p>Generate daily time records and attendance reports</p>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Reports →</button>
                </div>
            </a>
        </div>

        <!-- Advanced Features Section -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <h3>✨ Advanced Features</h3>
            <p>Premium analytics, detailed profiles, and leave management tools</p>
        </div>

        <!-- Advanced Features Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <a href="../hr-admin/analytics.php" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>📈 Analytics Dashboard</h3>
                    <p>View detailed attendance trends, leave statistics, and performance metrics with interactive charts</p>
                    <button class="btn btn-success" style="width: 100%; margin-top: 1rem;">View Analytics →</button>
                </div>
            </a>

            <a href="employee_profile.php?id=1" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>👤 Employee Profiles</h3>
                    <p>Access complete employee profiles with attendance history, leave records, and payroll information</p>
                    <button class="btn btn-success" style="width: 100%; margin-top: 1rem;">View Profiles →</button>
                </div>
            </a>

            <a href="advanced_leave_management.php" style="text-decoration: none;">
                <div class="card" style="cursor: pointer; transition: transform 0.3s;">
                    <h3>📋 Leave Management</h3>
                    <p>Advanced leave tracking with complete approval trail and status management</p>
                    <button class="btn btn-success" style="width: 100%; margin-top: 1rem;">Leave Mgmt →</button>
                </div>
            </a>
        </div>

        <!-- System Information -->
        <div class="card">
            <h3>ℹ️ System Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <p><strong>System:</strong> HRGetafe v1.0</p>
                    <p><strong>Organization:</strong> Getafe LGU</p>
                </div>
                <div>
                    <p><strong>Database:</strong> MySQL</p>
                    <p><strong>Last Update:</strong> <?php echo date('M d, Y h:i A'); ?></p>
                </div>
                <div>
                    <p><strong>Status:</strong> <span class="badge badge-success">Operational</span></p>
                    <p><strong>Version:</strong> 1.0 Complete</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
