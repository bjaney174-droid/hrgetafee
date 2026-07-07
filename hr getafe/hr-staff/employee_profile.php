<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
if ($_SESSION['role_id'] != 2) {
    header("Location: ../login.php");
    exit;
}

$logged_in_employee_id = $_SESSION['employee_id'];
$logged_in_employee = get_employee_info($conn, $logged_in_employee_id);

// Get employee ID from URL
$emp_id = $_GET['id'] ?? null;
if (!$emp_id) {
    header("Location: employee_directory.php");
    exit;
}

// Get employee details
$employee = $conn->query("
    SELECT e.*, d.dept_name
    FROM employees e
    JOIN departments d ON e.dept_id = d.dept_id
    WHERE e.employee_id = $emp_id
")->fetch_assoc();

if (!$employee) {
    header("Location: employee_directory.php");
    exit;
}

// Get attendance summary
$attendance = $conn->query("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance
    WHERE employee_id = $emp_id AND MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE())
")->fetch_assoc();

// Get leave history
$leave_history = $conn->query("
    SELECT lr.*, lt.leave_type_name
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
    WHERE lr.employee_id = $emp_id
    ORDER BY lr.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get recent attendance
$recent_attendance = $conn->query("
    SELECT *
    FROM attendance
    WHERE employee_id = $emp_id
    ORDER BY attendance_date DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// Get payroll records
$payroll = $conn->query("
    SELECT *
    FROM payroll
    WHERE employee_id = $emp_id
    ORDER BY payroll_year DESC, payroll_month DESC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .profile-header h1 {
            font-size: 28px;
            margin-bottom: 0.5rem;
        }
        .profile-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .info-box h3 {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 0.5rem;
        }
        .info-box p {
            font-size: 16px;
            color: #333;
            margin: 0.3rem 0;
        }
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>HRGetafe - Employee Profile</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($logged_in_employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="employee_directory.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Directory</a>

        <!-- Profile Header -->
        <div class="profile-header">
            <h1><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
            <p><?php echo htmlspecialchars($employee['position']); ?></p>
            <p>Employee ID: <?php echo $employee['employee_code']; ?></p>
        </div>

        <!-- Profile Info Cards -->
        <div class="profile-info">
            <div class="info-box">
                <h3>🏢 DEPARTMENT</h3>
                <p><?php echo htmlspecialchars($employee['dept_name']); ?></p>
            </div>
            <div class="info-box">
                <h3>📧 EMAIL</h3>
                <p><?php echo htmlspecialchars($employee['email']); ?></p>
            </div>
            <div class="info-box">
                <h3>📱 PHONE</h3>
                <p><?php echo htmlspecialchars($employee['phone']); ?></p>
            </div>
            <div class="info-box">
                <h3>💰 SALARY</h3>
                <p>₱<?php echo number_format($employee['salary'], 2); ?></p>
            </div>
            <div class="info-box">
                <h3>📅 DATE HIRED</h3>
                <p><?php echo format_date($employee['date_hired']); ?></p>
            </div>
            <div class="info-box">
                <h3>👤 STATUS</h3>
                <p><?php echo $employee['status'] === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'; ?></p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('attendance')">📅 Attendance</button>
            <button class="tab-button" onclick="switchTab('leave')">📋 Leave History</button>
            <button class="tab-button" onclick="switchTab('payroll')">💰 Payroll</button>
        </div>

        <!-- Attendance Tab -->
        <div id="attendance" class="tab-content active">
            <div class="table-container">
                <h3>Attendance Summary - <?php echo date('F Y'); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card" style="text-align: center;">
                        <h4>Total Days</h4>
                        <div class="stat-number"><?php echo $attendance['total_days']; ?></div>
                    </div>
                    <div class="card" style="text-align: center;">
                        <h4>Present</h4>
                        <div class="stat-number" style="color: #28a745;"><?php echo $attendance['present_days']; ?></div>
                    </div>
                    <div class="card" style="text-align: center;">
                        <h4>Late</h4>
                        <div class="stat-number" style="color: #ffc107;"><?php echo $attendance['late_days']; ?></div>
                    </div>
                    <div class="card" style="text-align: center;">
                        <h4>Absent</h4>
                        <div class="stat-number" style="color: #dc3545;"><?php echo $attendance['absent_days']; ?></div>
                    </div>
                </div>

                <h4>Recent Attendance Records</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_attendance)): ?>
                            <?php foreach ($recent_attendance as $record): ?>
                            <tr>
                                <td><?php echo format_date($record['attendance_date']); ?></td>
                                <td><?php echo $record['clock_in'] ? format_datetime($record['clock_in']) : '-'; ?></td>
                                <td><?php echo $record['clock_out'] ? format_datetime($record['clock_out']) : '-'; ?></td>
                                <td>
                                    <?php if ($record['status'] === 'present'): ?>
                                        <span class="badge badge-success">Present</span>
                                    <?php elseif ($record['status'] === 'late'): ?>
                                        <span class="badge badge-warning">Late</span>
                                    <?php elseif ($record['status'] === 'absent'): ?>
                                        <span class="badge badge-danger">Absent</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending"><?php echo ucfirst($record['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No attendance records</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leave Tab -->
        <div id="leave" class="tab-content">
            <div class="table-container">
                <h3>Leave Request History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Date From</th>
                            <th>Date To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Approved By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leave_history)): ?>
                            <?php foreach ($leave_history as $leave): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                                <td><?php echo format_date($leave['start_date']); ?></td>
                                <td><?php echo format_date($leave['end_date']); ?></td>
                                <td><?php echo $leave['number_of_days']; ?></td>
                                <td><?php echo htmlspecialchars(substr($leave['reason'], 0, 30)); ?></td>
                                <td>
                                    <?php if ($leave['status'] === 'approved'): ?>
                                        <span class="badge badge-success">Approved</span>
                                    <?php elseif ($leave['status'] === 'denied'): ?>
                                        <span class="badge badge-danger">Denied</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $leave['approved_by'] ? get_employee_name($conn, $leave['approved_by']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No leave requests</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payroll Tab -->
        <div id="payroll" class="tab-content">
            <div class="table-container">
                <h3>Payroll Records</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Gross Salary</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payroll)): ?>
                            <?php foreach ($payroll as $pay): ?>
                            <tr>
                                <td><?php echo date('F Y', mktime(0, 0, 0, $pay['payroll_month'], 1, $pay['payroll_year'])); ?></td>
                                <td>₱<?php echo number_format($pay['gross_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($pay['deductions'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($pay['net_salary'], 2); ?></strong></td>
                                <td>
                                    <?php if ($pay['status'] === 'processed'): ?>
                                        <span class="badge badge-success">Processed</span>
                                    <?php elseif ($pay['status'] === 'paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No payroll records</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
