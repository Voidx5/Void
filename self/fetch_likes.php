<?php
include('../connection/db.php');

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];

    $query = "
        SELECT reg_form.fname AS user_name 
        FROM likes 
        JOIN reg_form ON likes.user_id = reg_form.id 
        WHERE likes.post_id = ?";
    
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $likes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $likes[] = ['user_name' => $row['user_name']];
    }

    echo json_encode(['likes' => $likes]);
}
?>
