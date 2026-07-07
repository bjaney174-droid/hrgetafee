<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
check_role(1);

$admin = get_employee_info($conn, $_SESSION['employee_id']);
$message = '';
$error = '';

// Validate employee data
function validate_employee($data, $conn, $edit_id = null) {
    $errors = [];
    
    if (empty($data['employee_code'])) {
        $errors[] = 'Employee code is required';
    } else if (strlen($data['employee_code']) < 3) {
        $errors[] = 'Employee code must be at least 3 characters';
    }
    
    if (empty($data['first_name'])) {
        $errors[] = 'First name is required';
    }
    
    if (empty($data['last_name'])) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($data['position'])) {
        $errors[] = 'Position is required';
    }
    
    if (empty($data['salary']) || $data['salary'] < 0) {
        $errors[] = 'Valid salary is required';
    }
    
    if (empty($data['date_hired'])) {
        $errors[] = 'Date hired is required';
    }
    
    // Check duplicate employee code
    $check_code = $conn->query("SELECT employee_id FROM employees WHERE employee_code = '{$conn->real_escape_string($data['employee_code'])}' AND employee_id != " . ($edit_id ?? 0));
    if ($check_code->num_rows > 0) {
        $errors[] = 'Employee code already exists';
    }
    
    // Check duplicate email
    $check_email = $conn->query("SELECT employee_id FROM employees WHERE email = '{$conn->real_escape_string($data['email'])}' AND employee_id != " . ($edit_id ?? 0));
    if ($check_email->num_rows > 0) {
        $errors[] = 'Email already exists';
    }
    
    return $errors;
}

// Create employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $errors = validate_employee($_POST, $conn);
    
    if (!empty($errors)) {
        $error = '<div class="alert alert-danger"><strong>Errors:</strong><ul style="margin: 10px 0; padding-left: 20px;">';
        foreach ($errors as $err) {
            $error .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $error .= '</ul></div>';
    } else {
        $employee_code = $conn->real_escape_string($_POST['employee_code']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $position = $conn->real_escape_string($_POST['position']);
        $salary = (float)$_POST['salary'];
        $date_hired = $conn->real_escape_string($_POST['date_hired']);
        
        $stmt = $conn->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, phone, position, salary, date_hired, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssssssds", $employee_code, $first_name, $last_name, $email, $phone, $position, $salary, $date_hired);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success"><strong>✓ Success!</strong> Employee added successfully.</div>';
        } else {
            $error = '<div class="alert alert-danger"><strong>Error:</strong> Failed to add employee. Please try again.</div>';
        }
        $stmt->close();
    }
}

// Update employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $edit_id = (int)$_POST['employee_id'];
    $errors = validate_employee($_POST, $conn, $edit_id);
    
    if (!empty($errors)) {
        $error = '<div class="alert alert-danger"><strong>Errors:</strong><ul style="margin: 10px 0; padding-left: 20px;">';
        foreach ($errors as $err) {
            $error .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $error .= '</ul></div>';
    } else {
        $employee_code = $conn->real_escape_string($_POST['employee_code']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $position = $conn->real_escape_string($_POST['position']);
        $salary = (float)$_POST['salary'];
        $date_hired = $conn->real_escape_string($_POST['date_hired']);
        $status = $conn->real_escape_string($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE employees SET employee_code=?, first_name=?, last_name=?, email=?, phone=?, position=?, salary=?, date_hired=?, status=? WHERE employee_id=?");
        $stmt->bind_param("ssssssdsi", $employee_code, $first_name, $last_name, $email, $phone, $position, $salary, $date_hired, $status, $edit_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success"><strong>✓ Success!</strong> Employee updated successfully.</div>';
        } else {
            $error = '<div class="alert alert-danger"><strong>Error:</strong> Failed to update employee. Please try again.</div>';
        }
        $stmt->close();
    }
}

// Delete employee
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Check if employee has associated user
    $check_user = $conn->query("SELECT COUNT(*) as count FROM users WHERE employee_id = $delete_id");
    $user_count = $check_user->fetch_assoc()['count'];
    
    if ($user_count > 0) {
        $error = '<div class="alert alert-danger"><strong>Cannot Delete!</strong> This employee has an associated user account. Deactivate the user first.</div>';
    } else {
        if ($conn->query("DELETE FROM employees WHERE employee_id = $delete_id")) {
            $message = '<div class="alert alert-success"><strong>✓ Success!</strong> Employee deleted successfully.</div>';
        } else {
            $error = '<div class="alert alert-danger"><strong>Error:</strong> Failed to delete employee.</div>';
        }
    }
}

// Get employees
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR employee_code LIKE '%$search%' OR email LIKE '%$search%')";
}

$employees = $conn->query("SELECT * FROM employees $where ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);

// Get employee for edit if requested
$edit_employee = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $result = $conn->query("SELECT * FROM employees WHERE employee_id = $edit_id");
    $edit_employee = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .edit-mode-banner {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Employee Management</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($admin['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">← Back to Dashboard</a>

        <?php if ($message) echo $message; ?>
        <?php if ($error) echo $error; ?>

        <!-- Add/Edit Employee Form -->
        <div class="form-section">
            <?php if ($edit_employee): ?>
                <div class="edit-mode-banner">
                    <strong>✏️ Editing Employee:</strong> <?php echo htmlspecialchars($edit_employee['first_name'] . ' ' . $edit_employee['last_name']); ?>
                </div>
            <?php endif; ?>
            
            <h3><?php echo $edit_employee ? '✏️ Edit Employee' : '➕ Add New Employee'; ?></h3>
            
            <form method="POST">
                <?php if ($edit_employee): ?>
                    <input type="hidden" name="employee_id" value="<?php echo $edit_employee['employee_id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="employee_code">Employee Code *</label>
                        <input type="text" name="employee_code" id="employee_code" 
                               value="<?php echo htmlspecialchars($edit_employee['employee_code'] ?? ''); ?>"
                               placeholder="e.g., GETAFE-2026-001" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name"
                               value="<?php echo htmlspecialchars($edit_employee['first_name'] ?? ''); ?>"
                               placeholder="First name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name"
                               value="<?php echo htmlspecialchars($edit_employee['last_name'] ?? ''); ?>"
                               placeholder="Last name" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" name="email" id="email"
                               value="<?php echo htmlspecialchars($edit_employee['email'] ?? ''); ?>"
                               placeholder="name@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" name="phone" id="phone"
                               value="<?php echo htmlspecialchars($edit_employee['phone'] ?? ''); ?>"
                               placeholder="09123456789">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position *</label>
                        <input type="text" name="position" id="position"
                               value="<?php echo htmlspecialchars($edit_employee['position'] ?? ''); ?>"
                               placeholder="e.g., HR Staff" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="salary">Salary (₱) *</label>
                        <input type="number" name="salary" id="salary" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($edit_employee['salary'] ?? ''); ?>"
                               placeholder="25000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_hired">Date Hired *</label>
                        <input type="date" name="date_hired" id="date_hired"
                               value="<?php echo htmlspecialchars($edit_employee['date_hired'] ?? ''); ?>"
                               required>
                    </div>
                    
                    <?php if ($edit_employee): ?>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select name="status" id="status" required>
                            <option value="active" <?php echo ($edit_employee['status'] === 'active' ? 'selected' : ''); ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_employee['status'] === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" name="<?php echo $edit_employee ? 'edit_employee' : 'add_employee'; ?>" class="btn btn-primary" style="flex: 1;">
                        <?php echo $edit_employee ? '✓ Update Employee' : '➕ Add Employee'; ?>
                    </button>
                    <?php if ($edit_employee): ?>
                        <a href="manage_employees.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Search -->
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
            <form method="GET" action="">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Search by name, code, or email..." 
                           value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="manage_employees.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Employees Table -->
        <div class="table-container">
            <h3>👥 All Employees (<?php echo count($employees); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($emp['employee_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td>₱<?php echo number_format($emp['salary'], 2); ?></td>
                            <td>
                                <?php if ($emp['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="manage_employees.php?edit_id=<?php echo $emp['employee_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">✏️ Edit</a>
                                <a href="manage_employees.php?delete_id=<?php echo $emp['employee_id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Delete this employee? Make sure there is no user account associated.');">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 30px;">
                            <?php echo !empty($search) ? 'No employees found matching your search' : 'No employees yet. Add one to get started!'; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>