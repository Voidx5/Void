<?php
session_start();
include 'connection/db.php'; // Database connection file
include("parts/navigation1.php"); // Navigation bar

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the current user's ID and the profile user's ID
$current_user_id = $_SESSION['user_id'];
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Redirect to the main page if no profile user ID is provided
if ($profile_user_id === 0) {
    echo "<script>alert('No profile specified.'); window.location.href = 'index.php';</script>";
    exit;
}

// Fetch the profile user's information
$stmt = $con->prepare("SELECT fname, lname, gender, address, profile_picture FROM reg_form WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$profile_result = $stmt->get_result();
$profile_user = $profile_result->fetch_assoc();

// Redirect if the user does not exist
if (!$profile_user) {
    echo "<script>alert('Profile not found.'); window.location.href = 'index.php';</script>";
    exit;
}

// Check friendship status
$is_friend = $con->query("
    SELECT id FROM friendships 
    WHERE (user_id = $current_user_id AND friend_id = $profile_user_id) 
       OR (user_id = $profile_user_id AND friend_id = $current_user_id)
")->num_rows > 0;

$is_request_sent = $con->query("
    SELECT id FROM friend_requests 
    WHERE sender_id = $current_user_id AND receiver_id = $profile_user_id AND status = 'pending'
")->num_rows > 0;

$is_request_received = $con->query("
    SELECT id FROM friend_requests 
    WHERE sender_id = $profile_user_id AND receiver_id = $current_user_id AND status = 'pending'
")->num_rows > 0;

// Fetch posts based on friendship status
$post_query = $is_friend
    ? "SELECT p.id, p.post_content, p.post_caption, p.post_date, p.post_type, m.media_type, m.media_path
       FROM posts p
       LEFT JOIN post_media m ON p.id = m.post_id
       WHERE p.user_id = $profile_user_id
       ORDER BY p.post_date DESC"
    : "SELECT p.id, p.post_content, p.post_caption, p.post_date, p.post_type, m.media_type, m.media_path
       FROM posts p
       LEFT JOIN post_media m ON p.id = m.post_id
       WHERE p.user_id = $profile_user_id AND p.post_type = 'public'
       ORDER BY p.post_date DESC";

$posts = $con->query($post_query)->fetch_all(MYSQLI_ASSOC);

// Friend request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($current_user_id === $profile_user_id) {
        echo "<script>alert('You cannot send a friend request to yourself.');</script>";
        exit;
    }

    if (isset($_POST['add_friend'])) {
        // Send a friend request without the created_at column
        $stmt = $con->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $current_user_id, $profile_user_id);
        $stmt->execute();
        echo "<script>alert('Friend request sent.'); window.location.href = 'view_profile.php?user_id=$profile_user_id';</script>";
    }
    

    if (isset($_POST['cancel_request'])) {
        $stmt = $con->prepare("DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $current_user_id, $profile_user_id);
        $stmt->execute();
        echo "<script>alert('Friend request canceled.'); window.location.href = 'view_profile.php?user_id=$profile_user_id';</script>";
    }

    if (isset($_POST['remove_friend'])) {
        $stmt = $con->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $current_user_id, $profile_user_id, $profile_user_id, $current_user_id);
        $stmt->execute();
        echo "<script>alert('Friend removed.'); window.location.href = 'view_profile.php?user_id=$profile_user_id';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/home.css">
    <title><?= htmlspecialchars($profile_user['fname']) ?>'s Profile</title>
    <style>

        body {
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
        }
        .body {
            background:white;
            margin: 0 auto;
            padding: 20px;
            max-width: 800px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-section img {
            border-radius: 50%;
            margin-right: 20px;
        }

        .friend-actions {
            margin-bottom: 20px;
        }

        .friend-actions form {
            display: inline;
        }

        .post {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ccc;
        }

        .post img, .post video {
            margin-bottom: 10px;
        }
        button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="body">
    <h1><?= htmlspecialchars($profile_user['fname'] . " " . $profile_user['lname']) ?>'s Profile</h1>
    <div class="profile-section">
        <img src="uploads/<?= htmlspecialchars($profile_user['profile_picture'] ?: 'default_profile.png') ?>" alt="Profile Picture" width="150" height="150">
        <p><b>Bio:</b> <?= htmlspecialchars($profile_user['address'] ?? 'Not specified') ?></p>
        <p>&emsp;<b>Gender:</b> <?= htmlspecialchars($profile_user['gender'] ?? 'Not specified') ?></p>
    </div>

    <div class="friend-actions">
        <?php if ($current_user_id !== $profile_user_id): ?>
            <?php if ($is_friend): ?>
                <form method="POST"><button type="submit" name="remove_friend">Remove Friend</button></form>
            <?php elseif ($is_request_sent): ?>
                <form method="POST"><button type="submit" name="cancel_request">Cancel Friend Request</button></form>
            <?php elseif ($is_request_received): ?>
                <p>Friend request received. Respond in your notifications.</p>
            <?php else: ?>
                <form method="POST"><button type="submit" name="add_friend">Add Friend</button></form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <h2>Posts</h2>
    <?php if ($posts): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <?php if ($post['media_path']): ?>
                    <?php if ($post['media_type'] === 'image'): ?>
                        <img src="uploads/<?= htmlspecialchars($post['media_path']) ?>" alt="Post Image" width="300" >
                    <?php elseif ($post['media_type'] === 'video'): ?>
                        <video controls width="300">
                            <source src="uploads/<?= htmlspecialchars($post['media_path']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                <?php endif; ?>
                <p><?= nl2br(htmlspecialchars($post['post_content'])) ?></p>
                <p><strong><?= htmlspecialchars($post['post_caption'] ?? '') ?></strong></p>
                <small>Posted on: <?= htmlspecialchars($post['post_date']) ?> | Visibility: <?= htmlspecialchars($post['post_type']) ?></small>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No posts to show.</p>
    <?php endif; ?>
    </div>
</body>
</html>
