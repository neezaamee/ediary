<?php
// diary/delete.php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Invalid request.');
    redirect('diary/index.php');
}

$entry_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify the entry belongs to the user before deleting
$stmt = $conn->prepare("SELECT id FROM diary_entries WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Entry not found or access denied.');
    redirect('diary/index.php');
}

$stmt->close();

// Proceed with deletion
$stmt = $conn->prepare("DELETE FROM diary_entries WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $entry_id, $user_id);

if ($stmt->execute()) {
    setFlashMessage('success', 'Memory deleted successfully.');
} else {
    setFlashMessage('error', 'Failed to delete memory. Please try again.');
}

$stmt->close();
redirect('diary/index.php');
?>
