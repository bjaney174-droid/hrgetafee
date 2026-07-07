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
$message = '';

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $dept_name = $_POST['dept_name'];
        
        $stmt = $conn->prepare("INSERT INTO departments (dept_name) VALUES (?)");
        $stmt->bind_param("s", $dept_name);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Department added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error adding department</div>';
        }
        $stmt->close();
    } 
    else if ($action === 'edit') {
        $dept_id = $_POST['dept_id'];
        $dept_name = $_POST['dept_name'];
        
        $stmt = $conn->prepare("UPDATE departments SET dept_name = ? WHERE dept_id = ?");
        $stmt->bind_param("si", $dept_name, $dept_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Department updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error updating department</div>';
        }
        $stmt->close();
    }
    else if ($action === 'delete') {
        $dept_id = $_POST['dept_id'];
        
        // Check if department has employees
        $check = $conn->query("SELECT COUNT(*) as count FROM employees WHERE dept_id = $dept_id");
        $result = $check->fetch_assoc();
        
        if ($result['count'] > 0) {
            $message = '<div class="alert alert-danger">✗ Cannot delete department with existing employees</div>';
        } else {
            $stmt = $conn->prepare("DELETE FROM departments WHERE dept_id = ?");
            $stmt->bind_param("i", $dept_id);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">✓ Department deleted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">✗ Error deleting department</div>';
            }
            $stmt->close();
        }
    }
}

// Get all departments with employee count
$departments = $conn->query("
    SELECT d.*, COUNT(e.employee_id) as employee_count
    FROM departments d
    LEFT JOIN employees e ON d.dept_id = e.dept_id
    GROUP BY d.dept_id
    ORDER BY d.dept_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - HRGetafe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>HRGetafe - Department Management</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Dashboard</a>

        <?php if ($message) echo $message; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>🏢 Department Management</h3>
            <p>Create and manage organizational departments</p>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add New Department</button>
        </div>

        <div class="table-container">
            <h3>All Departments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Employees</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['dept_name']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo $dept['employee_count']; ?></span></td>
                            <td>
                                <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" onclick="editDepartment(<?php echo $dept['dept_id']; ?>, '<?php echo htmlspecialchars($dept['dept_name']); ?>')">Edit</button>
                                <?php if ($dept['employee_count'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this department?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="dept_id" value="<?php echo $dept['dept_id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" disabled title="Cannot delete department with employees">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No departments found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Department</h2>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="dept_name">Department Name *</label>
                    <input type="text" id="dept_name" name="dept_name" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" class="btn btn-success">Add Department</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Department</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_dept_id" name="dept_id">
                
                <div class="form-group">
                    <label for="edit_dept_name">Department Name *</label>
                    <input type="text" id="edit_dept_name" name="dept_name" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" class="btn btn-success">Update Department</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function editDepartment(id, name) {
            document.getElementById('edit_dept_id').value = id;
            document.getElementById('edit_dept_name').value = name;
            openModal('editModal');
        }

        window.onclick = function(event) {
            let modal = event.target;
            if (modal.classList.contains('modal')) {
                modal.classList.remove('show');
            }
        }
    </script>
</body>
</html>
