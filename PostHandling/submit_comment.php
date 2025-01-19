<?php
session_start();
include("../connection/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Error: User not logged in";
    exit();
}

// Check if the necessary data is provided
if (isset($_POST['post_id']) && isset($_POST['comment_text'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id']);
    $comment_text = mysqli_real_escape_string($con, trim($_POST['comment_text']));

    if (!empty($comment_text)) {
        // Insert the new comment into the database
        $query = "INSERT INTO comments (user_id, post_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "iis", $user_id, $post_id, $comment_text);
        if (mysqli_stmt_execute($stmt)) {
            // Fetch the updated comment count
            $comment_count_query = "SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = ?";
            $stmt = mysqli_prepare($con, $comment_count_query);
            mysqli_stmt_bind_param($stmt, "i", $post_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $comment_count = $row['comment_count'];

            // Return the updated comment count
            echo $comment_count;
        } else {
            echo "Error: Could not save comment";
        }
    } else {
        echo "Error: Comment cannot be empty";
    }
} else {
    echo "Error: Invalid data";
}
?>
