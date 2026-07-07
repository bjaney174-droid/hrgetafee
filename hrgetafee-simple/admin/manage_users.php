<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
check_role(1);

$admin = get_employee_info($conn, $_SESSION['employee_id']);
$message = '';

// Create new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    
    if (empty($username) || empty($password) || empty($role_id) || empty($employee_id)) {
        $message = '<div class="alert alert-danger">All fields are required</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role_id, employee_id, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssii", $username, $password, $role_id, $employee_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ User created successfully</div>';
        } else {
            $message = '<div class="alert alert-danger">Username already exists or error occurred</div>';
        }
        $stmt->close();
    }
}

// Get employees and users
$employees = $conn->query("SELECT * FROM employees ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT u.*, e.first_name, e.last_name, r.role_name FROM users u JOIN employees e ON u.employee_id = e.employee_id JOIN roles r ON u.role_id = r.role_id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <h2>Manage Users</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($admin['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">← Back to Dashboard</a>

        <?php if ($message) echo $message; ?>

        <!-- Create User Form -->
        <div class="card" style="max-width: 500px; margin-bottom: 30px;">
            <h3>➕ Create New User</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="employee">Employee</label>
                    <select name="employee_id" id="employee" required>
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_id']; ?>">
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> (<?php echo $emp['employee_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role_id" id="role" required>
                        <option value="2">HR Staff</option>
                        <option value="3">Employee</option>
                    </select>
                </div>

                <button type="submit" name="create_user" class="btn btn-primary" style="width: 100%;">Create User</button>
            </form>
        </div>

        <!-- Users List -->
        <div class="table-container">
            <h3>📋 All Users (<?php echo count($users); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo format_date($user['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>