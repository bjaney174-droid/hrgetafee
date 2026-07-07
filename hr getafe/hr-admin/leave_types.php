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
        $leave_type_name = $_POST['leave_type_name'];
        $max_days = $_POST['max_days'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO leave_types (leave_type_name, max_days_per_year, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $leave_type_name, $max_days, $description);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Leave type added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error adding leave type</div>';
        }
        $stmt->close();
    } 
    else if ($action === 'edit') {
        $leave_type_id = $_POST['leave_type_id'];
        $leave_type_name = $_POST['leave_type_name'];
        $max_days = $_POST['max_days'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE leave_types SET leave_type_name = ?, max_days_per_year = ?, description = ? WHERE leave_type_id = ?");
        $stmt->bind_param("sisi", $leave_type_name, $max_days, $description, $leave_type_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Leave type updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error updating leave type</div>';
        }
        $stmt->close();
    }
    else if ($action === 'delete') {
        $leave_type_id = $_POST['leave_type_id'];
        
        $stmt = $conn->prepare("DELETE FROM leave_types WHERE leave_type_id = ?");
        $stmt->bind_param("i", $leave_type_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Leave type deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error deleting leave type</div>';
        }
        $stmt->close();
    }
}

// Get all leave types
$leave_types = $conn->query("SELECT * FROM leave_types ORDER BY leave_type_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Types Management - HRGetafe</title>
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
        <h2>HRGetafe - Leave Types Management</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Dashboard</a>

        <?php if ($message) echo $message; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>📋 Leave Types Management</h3>
            <p>Configure all leave types available in the system</p>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add New Leave Type</button>
        </div>

        <div class="table-container">
            <h3>All Leave Types</h3>
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Max Days Per Year</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($leave_types)): ?>
                        <?php foreach ($leave_types as $type): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($type['leave_type_name']); ?></strong></td>
                            <td><?php echo $type['max_days_per_year']; ?> days</td>
                            <td><?php echo htmlspecialchars($type['description']); ?></td>
                            <td>
                                <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" onclick="editLeaveType(<?php echo $type['leave_type_id']; ?>, '<?php echo htmlspecialchars($type['leave_type_name']); ?>', <?php echo $type['max_days_per_year']; ?>, '<?php echo htmlspecialchars($type['description']); ?>')">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this leave type?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="leave_type_id" value="<?php echo $type['leave_type_id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No leave types found</td>
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
                <h2>Add New Leave Type</h2>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="leave_type_name">Leave Type Name *</label>
                    <input type="text" id="leave_type_name" name="leave_type_name" required>
                </div>

                <div class="form-group">
                    <label for="max_days">Max Days Per Year *</label>
                    <input type="number" id="max_days" name="max_days" required min="1">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" class="btn btn-success">Add Leave Type</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Leave Type</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_leave_type_id" name="leave_type_id">
                
                <div class="form-group">
                    <label for="edit_leave_type_name">Leave Type Name *</label>
                    <input type="text" id="edit_leave_type_name" name="leave_type_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_max_days">Max Days Per Year *</label>
                    <input type="number" id="edit_max_days" name="max_days" required min="1">
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" class="btn btn-success">Update Leave Type</button>
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

        function editLeaveType(id, name, maxDays, description) {
            document.getElementById('edit_leave_type_id').value = id;
            document.getElementById('edit_leave_type_name').value = name;
            document.getElementById('edit_max_days').value = maxDays;
            document.getElementById('edit_description').value = description;
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
