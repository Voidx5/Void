<?php
session_start();
include('../connection/db.php');
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id']; // assuming receiver_id is sent via POST

// Check if they are already friends
$check_friendship = "SELECT * FROM friendships 
                     WHERE (user_id = ? AND friend_id = ?) 
                     OR (friend_id = ? AND user_id = ?)";
$stmt = mysqli_prepare($con, $check_friendship);
mysqli_stmt_bind_param($stmt, "iiii", $sender_id, $receiver_id, $sender_id, $receiver_id);
mysqli_stmt_execute($stmt);
$friendship_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($friendship_result) > 0) {
    // They are already friends, no need to send another request
    echo "You are already friends with this user.";
} else {
    // Check if a pending friend request already exists
    $check_request = "SELECT * FROM friend_requests 
                      WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
    $request_stmt = mysqli_prepare($con, $check_request);
    mysqli_stmt_bind_param($request_stmt, "ii", $sender_id, $receiver_id);
    mysqli_stmt_execute($request_stmt);
    $request_result = mysqli_stmt_get_result($request_stmt);

    if (mysqli_num_rows($request_result) > 0) {
        // A pending request already exists
        echo "Friend request already sent.";
    } else {
        // Send a new friend request
        $send_request = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
        $insert_stmt = mysqli_prepare($con, $send_request);
        mysqli_stmt_bind_param($insert_stmt, "ii", $sender_id, $receiver_id);
        if (mysqli_stmt_execute($insert_stmt)) {
            echo "Friend request sent successfully!";
        } else {
            echo "Error sending friend request.";
        }
    }
}
?>
