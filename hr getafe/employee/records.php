<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
if ($_SESSION['role_id'] != 4) {
    header("Location: ../login.php");
    exit;
}

$employee_id = $_SESSION['employee_id'];
$employee = get_employee_info($conn, $employee_id);

// Get attendance records
$records = $conn->query("SELECT * FROM attendance WHERE employee_id = $employee_id ORDER BY attendance_date DESC")->fetch_all(MYSQLI_ASSOC);

// Get monthly summary
$current_month = date('m');
$current_year = date('Y');
$monthly_summary = $conn->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM attendance
    WHERE employee_id = $employee_id AND MONTH(attendance_date) = $current_month AND YEAR(attendance_date) = $current_year
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>HRGetafe - My Attendance Records</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Dashboard</a>

        <!-- Monthly Summary -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <?php foreach ($monthly_summary as $summary): ?>
                <div class="card">
                    <h3><?php echo ucfirst($summary['status']); ?></h3>
                    <div class="stat-number"><?php echo $summary['count']; ?></div>
                    <p style="color: #666; font-size: 14px;">This month</p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Attendance Table -->
        <div class="table-container">
            <h3>📋 All Attendance Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo format_date($record['attendance_date']); ?></td>
                            <td><?php echo $record['clock_in'] ? format_datetime($record['clock_in']) : '-'; ?></td>
                            <td><?php echo $record['clock_out'] ? format_datetime($record['clock_out']) : '-'; ?></td>
                            <td>
                                <?php
                                if ($record['clock_in'] && $record['clock_out']) {
                                    $in = new DateTime($record['clock_in']);
                                    $out = new DateTime($record['clock_out']);
                                    $interval = $in->diff($out);
                                    echo $interval->format('%h hrs %i mins');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
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
                        <td colspan="5" class="text-center">No attendance records found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
