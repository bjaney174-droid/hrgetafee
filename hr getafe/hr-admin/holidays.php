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
        $holiday_name = $_POST['holiday_name'];
        $holiday_date = $_POST['holiday_date'];
        $is_special = isset($_POST['is_special']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO holidays (holiday_name, holiday_date, is_special) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $holiday_name, $holiday_date, $is_special);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Holiday added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error adding holiday</div>';
        }
        $stmt->close();
    } 
    else if ($action === 'edit') {
        $holiday_id = $_POST['holiday_id'];
        $holiday_name = $_POST['holiday_name'];
        $holiday_date = $_POST['holiday_date'];
        $is_special = isset($_POST['is_special']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE holidays SET holiday_name = ?, holiday_date = ?, is_special = ? WHERE holiday_id = ?");
        $stmt->bind_param("ssii", $holiday_name, $holiday_date, $is_special, $holiday_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Holiday updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error updating holiday</div>';
        }
        $stmt->close();
    }
    else if ($action === 'delete') {
        $holiday_id = $_POST['holiday_id'];
        
        $stmt = $conn->prepare("DELETE FROM holidays WHERE holiday_id = ?");
        $stmt->bind_param("i", $holiday_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✓ Holiday deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">✗ Error deleting holiday</div>';
        }
        $stmt->close();
    }
}

// Get all holidays ordered by date
$holidays = $conn->query("SELECT * FROM holidays ORDER BY holiday_date DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Management - HRGetafe</title>
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
        <h2>HRGetafe - Holiday Management</h2>
        <div class="navbar-user">
            <span>Welcome, <strong><?php echo htmlspecialchars($employee['first_name']); ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 1.5rem;">← Back to Dashboard</a>

        <?php if ($message) echo $message; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>📅 Holiday Management</h3>
            <p>Set official holidays and special non-working days for the organization</p>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add New Holiday</button>
        </div>

        <div class="table-container">
            <h3>All Holidays</h3>
            <table>
                <thead>
                    <tr>
                        <th>Holiday Name</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($holidays)): ?>
                        <?php foreach ($holidays as $holiday): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($holiday['holiday_name']); ?></strong></td>
                            <td><?php echo format_date($holiday['holiday_date']); ?></td>
                            <td>
                                <?php if ($holiday['is_special']): ?>
                                    <span class="badge badge-warning">Special Non-Working Day</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Regular Holiday</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;" onclick="editHoliday(<?php echo $holiday['holiday_id']; ?>, '<?php echo htmlspecialchars($holiday['holiday_name']); ?>', '<?php echo $holiday['holiday_date']; ?>', <?php echo $holiday['is_special']; ?>)">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this holiday?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="holiday_id" value="<?php echo $holiday['holiday_id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No holidays found</td>
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
                <h2>Add New Holiday</h2>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="holiday_name">Holiday Name *</label>
                    <input type="text" id="holiday_name" name="holiday_name" required>
                </div>

                <div class="form-group">
                    <label for="holiday_date">Date *</label>
                    <input type="date" id="holiday_date" name="holiday_date" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_special">
                        Mark as Special Non-Working Day
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" class="btn btn-success">Add Holiday</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Holiday</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_holiday_id" name="holiday_id">
                
                <div class="form-group">
                    <label for="edit_holiday_name">Holiday Name *</label>
                    <input type="text" id="edit_holiday_name" name="holiday_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_holiday_date">Date *</label>
                    <input type="date" id="edit_holiday_date" name="holiday_date" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_special" name="is_special">
                        Mark as Special Non-Working Day
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" class="btn btn-success">Update Holiday</button>
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

        function editHoliday(id, name, date, isSpecial) {
            document.getElementById('edit_holiday_id').value = id;
            document.getElementById('edit_holiday_name').value = name;
            document.getElementById('edit_holiday_date').value = date;
            document.getElementById('edit_is_special').checked = isSpecial == 1;
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
