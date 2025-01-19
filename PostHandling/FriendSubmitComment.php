<?php
session_start();
include("../connection/db.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment_text = mysqli_real_escape_string($con, $_POST['comment_text']);

    // Insert the comment into the database
    $insert_comment_query = "INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($con, $insert_comment_query);
    mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment_text);
    mysqli_stmt_execute($stmt);

    // Fetch the user's name for displaying the comment
    $user_query = "SELECT fname, lname FROM reg_form WHERE id = ?";
    $stmt = mysqli_prepare($con, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user_row = mysqli_fetch_assoc($user_result);
    $comment_author = $user_row['fname'] . ' ' . $user_row['lname'];

    // Return the new comment data in JSON format
    echo json_encode([
        'status' => 'success',
        'comment_text' => htmlspecialchars($comment_text),
        'comment_author' => htmlspecialchars($comment_author),
        'comment_date' => date('Y-m-d H:i:s') // Send the current timestamp
    ]);
}
?>
