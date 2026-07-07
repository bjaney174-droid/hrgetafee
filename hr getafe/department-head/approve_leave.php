<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();
if ($_SESSION['role_id'] != 3) {
    header("Location: ../login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action'];
    
    $employee_id = $_SESSION['employee_id'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE leave_requests SET status = 'approved', approved_by = $employee_id, approved_date = NOW() WHERE leave_id = $leave_id");
        $message = '<div class="alert alert-success">✓ Leave request approved!</div>';
    } else if ($action === 'deny') {
        $conn->query("UPDATE leave_requests SET status = 'denied', approved_by = $employee_id, approved_date = NOW() WHERE leave_id = $leave_id");
        $message = '<div class="alert alert-danger">✗ Leave request denied!</div>';
    }
}

header("Location: dashboard.php");
exit;
?>
