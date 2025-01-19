<?php
include('../connection/db.php');

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];

    $query = "
        SELECT reg_form.fname AS user_name, comments.comment_text 
        FROM comments 
        JOIN reg_form ON comments.user_id = reg_form.id 
        WHERE comments.post_id = ?";
    
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $comments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = [
            'user_name' => $row['user_name'],
            'comment_text' => $row['comment_text']
        ];
    }

    echo json_encode(['comments' => $comments]);
}
?>
