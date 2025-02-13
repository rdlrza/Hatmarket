<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== 0) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit();
}

$allowed = ['jpg', 'jpeg', 'png'];
$filename = $_FILES['profile_picture']['name'];
$filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($filetype, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, JPEG & PNG files are allowed.']);
    exit();
}

// Create profiles directory if it doesn't exist
$upload_dir = 'assets/images/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$new_filename = 'profile_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $filetype;
$upload_path = $upload_dir . $new_filename;

if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
    try {
        // Get old profile picture
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $old_picture = $stmt->fetchColumn();

        // Update database with new picture
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$upload_path, $_SESSION['user_id']]);

        // Delete old profile picture if it exists and is not the default
        if ($old_picture && $old_picture !== 'assets/images/default-profile.jpg' && file_exists($old_picture)) {
            unlink($old_picture);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Profile picture updated successfully',
            'image_url' => $upload_path
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
}
?>
