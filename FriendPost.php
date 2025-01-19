<?php
session_start();
include("connection/db.php");
include("parts/navigation1.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch friends' posts and user's own posts
$friend_query = "
    SELECT f.friend_id AS friend_id FROM friendships f
    WHERE f.user_id = ?
    UNION
    SELECT f.user_id AS friend_id FROM friendships f
    WHERE f.friend_id = ?
";
$stmt = mysqli_prepare($con, $friend_query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$friend_result = mysqli_stmt_get_result($stmt);

$friend_ids = [$user_id]; // Include user's own ID to show their posts too
while ($row = mysqli_fetch_assoc($friend_result)) {
    $friend_ids[] = $row['friend_id'];
}

$friend_placeholder = implode(',', array_fill(0, count($friend_ids), '?'));

$post_query = "
    SELECT p.id, p.user_id, p.post_content, p.post_caption, p.post_type, p.post_date, 
           r.fname, r.lname, r.profile_picture 
    FROM posts p
    JOIN reg_form r ON p.user_id = r.id
    WHERE p.user_id IN ($friend_placeholder) AND (p.post_type = 'public' OR p.post_type = 'friend')
    ORDER BY p.post_date DESC
";
$stmt = mysqli_prepare($con, $post_query);
mysqli_stmt_bind_param($stmt, str_repeat('i', count($friend_ids)), ...$friend_ids);
mysqli_stmt_execute($stmt);
$post_result = mysqli_stmt_get_result($stmt);

// Display posts
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/home.css">
    <title>Friends' Posts</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            margin: 0;
            padding: 0;
            color: #333;
        }

        h1 {
            text-align: center;
            padding: 20px;
            font-family: Arial, Helvetica, sans-serif;
            color: black;
        }

        .posts-container {
            width: 100%;
            max-width: 600px;
            margin: auto;
        }

        .post {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }

        /* Profile picture floating style */
        .profile-picture {
            display: none;
            /* Initially hidden */
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            transition: transform 0.3s ease;
            position: absolute;
            top: -10px;
            left: -10px;
            cursor: pointer;


        }

        .profile-picture.show {
            display: inline-block;
            transform: scale(1.2);
        }

        .post-content {
            margin: 10px 0;
        }

        .post-media img,
        .post-media video {
            width: 100%;
            max-height: auto;
            border-radius: 4px;
            margin-top: 10px;
            object-fit: contain;
        }

        .like-button,
        .show-comments-button {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 14px;
            text-decoration: underline;
        }

        .comment-list {
            display: none;
            padding: 0;
            list-style: none;
            margin: 0;
        }

        .comment-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            color: #666;
        }

        .comment-form {
            display: flex;
            margin-top: 10px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            resize: none;
            margin-right: 10px;
        }

        .comment-form button {
            padding: 8px 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .post-author-info {
            display: flex;
            flex-direction: column;
            margin-left: 10px;
        }

        .post-author-name {
            font-weight: bold;
        }

        .post-date {
            font-size: 0.9em;
            color: gray;
        }

        .post-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
        }
    </style>
</head>

<body>
    <h1>Friends' Posts</h1>

    <?php if (mysqli_num_rows($post_result) > 0): ?>
        <div class="posts-container">
            <?php while ($post = mysqli_fetch_assoc($post_result)): ?>
                <div class="post">
                <div class="post-header">
    <img src="uploads/<?php echo $post['profile_picture'] ?: 'default_profile.jpg'; ?>" alt="Profile Picture">
    <div class="post-author-info">
        <span class="post-author-name"><?php echo $post['fname'] . ' ' . $post['lname']; ?></span>
        <span class="post-date"><?php echo $post['post_date']; ?></span>
    </div>
    <!-- View Profile Button -->
    <div class="view-profile-button" style="margin-left:auto;">
    <?php if ($post['user_id'] == $user_id): ?>
        <!-- Redirect to 'me.php' if the post belongs to the logged-in user -->
        <a href="me.php" class="btn-view-profile" style="text-decoration: none; background: #007bff; color: white; padding: 5px 10px; border-radius: 5px;">
            My Profile
        </a>
    <?php else: ?>
        <!-- Redirect to the profile of the post author -->
        <a href="view_profile.php?user_id=<?php echo $post['user_id']; ?>" class="btn-view-profile" style="text-decoration: none; background: #007bff; color: white; padding: 5px 10px; border-radius: 5px;">
            View Profile
        </a>
    <?php endif; ?>
</div>
</div>

                    <div class="post-content">
                        <p><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
                        <?php if (!empty($post['post_caption'])): ?>
                            <p><strong>Caption:</strong> <?php echo htmlspecialchars($post['post_caption']); ?></p>
                        <?php endif; ?>

                        <!-- Fetch media related to the post -->
                        <?php
                        $media_query = "SELECT media_type, media_path FROM post_media WHERE post_id = ?";
                        $media_stmt = mysqli_prepare($con, $media_query);
                        mysqli_stmt_bind_param($media_stmt, "i", $post['id']);
                        mysqli_stmt_execute($media_stmt);
                        $media_result = mysqli_stmt_get_result($media_stmt);
                        ?>
                        <div class="post-media">
                            <?php while ($media = mysqli_fetch_assoc($media_result)): ?>
                                <?php if ($media['media_type'] == 'image'): ?>
                                    <img src="uploads/<?php echo $media['media_path']; ?>" alt="Image" width="100">
                                <?php elseif ($media['media_type'] == 'video'): ?>
                                    <video width="320" height="240" controls>
                                        <source src="uploads/<?php echo $media['media_path']; ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        </div>

                        <!-- Like button and count -->
                        <?php
                        $like_count_query = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
                        $stmt = mysqli_prepare($con, $like_count_query);
                        mysqli_stmt_bind_param($stmt, "i", $post['id']);
                        mysqli_stmt_execute($stmt);
                        $like_count_result = mysqli_stmt_get_result($stmt);
                        $like_count_row = mysqli_fetch_assoc($like_count_result);
                        $like_count = $like_count_row['like_count'];
                        ?>
                        <button class="like-button" data-post-id="<?php echo $post['id']; ?>">Like (<?php echo $like_count; ?>)</button>

                        <!-- Show/Hide comments button with comment count -->
                        <?php
                        $comment_count_query = "SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = ?";
                        $stmt = mysqli_prepare($con, $comment_count_query);
                        mysqli_stmt_bind_param($stmt, "i", $post['id']);
                        mysqli_stmt_execute($stmt);
                        $comment_count_result = mysqli_stmt_get_result($stmt);
                        $comment_count_row = mysqli_fetch_assoc($comment_count_result);
                        $comment_count = $comment_count_row['comment_count'];
                        ?>
                        <button class="show-comments-button" data-post-id="<?php echo $post['id']; ?>">
                            Show Comments (<?php echo $comment_count; ?>)
                        </button>

                        <!-- Comments section -->
                        <div class="comments-section">
                            <ul class="comment-list" id="comment-list-<?php echo $post['id']; ?>">
                                <!-- Comments will be shown here -->
                                <?php
                                $comment_query = "SELECT c.comment_text, r.fname, r.lname, c.created_at 
                                                  FROM comments c 
                                                  JOIN reg_form r ON c.user_id = r.id 
                                                  WHERE c.post_id = ? 
                                                  ORDER BY c.created_at DESC";
                                $stmt = mysqli_prepare($con, $comment_query);
                                mysqli_stmt_bind_param($stmt, "i", $post['id']);
                                mysqli_stmt_execute($stmt);
                                $comment_result = mysqli_stmt_get_result($stmt);
                                ?>
                                <?php while ($comment = mysqli_fetch_assoc($comment_result)): ?>
                                    <li>
                                        <strong><?php echo $comment['fname'] . ' ' . $comment['lname']; ?>:</strong>
                                        <?php echo htmlspecialchars($comment['comment_text']); ?>
                                        <span class="comment-date">(<?php echo $comment['created_at']; ?>)</span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>

                            <!-- Add a new comment -->
                            <form method="POST" class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <textarea name="comment_text" placeholder="Add a comment..." required></textarea>
                                <button type="submit">Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No posts to display.</p>
    <?php endif; ?>

    <script>
        // Toggle comment visibility (existing functionality)
        document.querySelectorAll('.show-comments-button').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const commentList = document.getElementById('comment-list-' + postId);
                if (commentList.style.display === 'none' || commentList.style.display === '') {
                    commentList.style.display = 'block';
                    this.textContent = 'Hide Comments';
                } else {
                    commentList.style.display = 'none';
                    this.textContent = 'Show Comments (' + commentList.children.length + ')';
                }
            });
        });

        // AJAX like submission (existing functionality)
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');

                // Send AJAX request to submit the like
                fetch('PostHandling/FriendLikePost.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            'post_id': postId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Update the like count on the button
                            button.textContent = `Like (${data.new_like_count})`;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });

        // AJAX comment submission
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent the form from submitting and reloading the page

                const postId = this.getAttribute('data-post-id');
                const commentText = this.querySelector('textarea[name="comment_text"]').value;
                const commentList = document.getElementById('comment-list-' + postId);

                // Send AJAX request to submit the comment
                fetch('PostHandling/FriendSubmitComment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            'post_id': postId,
                            'comment_text': commentText
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Append the new comment to the comment list
                            const newComment = document.createElement('li');
                            newComment.innerHTML = `<strong>${data.comment_author}:</strong> ${data.comment_text} <span class="comment-date">(${data.comment_date})</span>`;
                            commentList.appendChild(newComment);

                            // Clear the comment text area after successful submission
                            form.querySelector('textarea[name="comment_text"]').value = '';

                            // Update the comment count in the button
                            const showCommentsButton = document.querySelector(`.show-comments-button[data-post-id="${postId}"]`);
                            const commentCount = commentList.children.length;
                            showCommentsButton.textContent = `Show Comments (${commentCount})`;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>