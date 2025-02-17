<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];
    $user_id = $_SESSION['user_id'];

    // Update user information
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
    $stmt->execute([$username, $email, $bio, $user_id]);

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            // Create upload directory if it doesn't exist
            $upload_dir = 'assets/images/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Update profile image in database
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
            }
        }
    }

    // Redirect back to profile page
    header("Location: profile.php?success=1");
    exit();
}
?>
