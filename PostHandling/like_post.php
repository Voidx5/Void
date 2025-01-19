<?php
session_start();
include("../connection/db.php");

if (isset($_POST['post_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];

    // Check if the user has already liked the post
    $check_like_query = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = mysqli_prepare($con, $check_like_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        // Insert the like into the database
        $like_query = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($con, $like_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
        mysqli_stmt_execute($stmt);
    }

    // Fetch the updated like count
    $like_count_query = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
    $stmt = mysqli_prepare($con, $like_count_query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $like_count_result = mysqli_stmt_get_result($stmt);
    $like_count_row = mysqli_fetch_assoc($like_count_result);
    echo $like_count_row['like_count'];
}
?>
