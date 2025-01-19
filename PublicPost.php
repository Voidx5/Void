<?php
session_start();
include("connection/db.php");
include("parts/navigation1.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all public posts
$post_query = "
    SELECT p.id, p.user_id, p.post_content, p.post_caption, p.post_type, p.post_date, 
           r.fname, r.lname, r.profile_picture 
    FROM posts p
    JOIN reg_form r ON p.user_id = r.id
    WHERE p.post_type = 'public'
    ORDER BY p.post_date DESC
";
$post_result = mysqli_query($con, $post_query);

// Function to get comment count
function getCommentCount($post_id)
{
    global $con;
    $comment_count_query = "SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = ?";
    $stmt = mysqli_prepare($con, $comment_count_query);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $comment_count_result = mysqli_stmt_get_result($stmt);
    $comment_count_row = mysqli_fetch_assoc($comment_count_result);
    return $comment_count_row['comment_count'];
}

// Display posts
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/home.css">
    <title>Public Posts</title>
    <style>
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

        #heart {
            display: none;
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
    <h1>Public Posts</h1>

    <?php if (mysqli_num_rows($post_result) > 0): ?>
        <div class="posts-container">
            <?php while ($post = mysqli_fetch_assoc($post_result)): ?>
                <div class="post">
                <div class="post-header">
    <img src="uploads/<?php echo $post['profile_picture'] ?: 'default_profile.png'; ?>" alt="Profile Picture">
    <div class="post-author-info">
        <span class="post-author-name"><?php echo $post['fname'] . ' ' . $post['lname']; ?></span>
        <span class="post-date"><?php echo $post['post_date']; ?></span>
    </div>
    <!-- Add View Profile button -->
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
                        <button class="like-button" data-post-id="<?php echo $post['id']; ?>">
                            Like (<?php echo $like_count; ?>)
                        </button>

                        <!-- Show/Hide comments button with dynamic comment count -->
                        <button class="show-comments-button" data-post-id="<?php echo $post['id']; ?>">
                            Comments (<?php echo getCommentCount($post['id']); ?>)
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

                            <!-- Add a new comment with AJAX -->
                            <form class="comment-form" method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <textarea name="comment_text" placeholder="Add a comment..." required></textarea>
                                <button type="button" class="comment-submit" data-post-id="<?php echo $post['id']; ?>">Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No public posts to display.</p>
    <?php endif; ?>

    <!-- JavaScript to toggle comments and handle AJAX for likes and comments -->
    <script>
        // Handle the Like button click using AJAX
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const likeButton = this;

                // Perform an AJAX request to handle the like action
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'PostHandling/like_post.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // Update the like count without refreshing the page
                        likeButton.innerHTML = 'Like (' + xhr.responseText + ')';
                    }
                };
                xhr.send('post_id=' + postId);
            });
        });

        // Handle showing and hiding comments
        document.querySelectorAll('.show-comments-button').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const commentList = document.getElementById('comment-list-' + postId);
                if (commentList.style.display === 'none' || commentList.style.display === '') {
                    commentList.style.display = 'block';
                    this.textContent = 'Hide Comments';
                } else {
                    commentList.style.display = 'none';
                    this.textContent = 'Comments';

                }
            });
        });

        // Handle comment submission using AJAX
        document.querySelectorAll('.comment-submit').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                const commentForm = this.closest('form');
                const commentText = commentForm.querySelector('textarea').value;
                const commentList = document.getElementById('comment-list-' + postId);
                const commentCountButton = document.querySelector('.show-comments-button[data-post-id="' + postId + '"]');

                // Perform an AJAX request to submit the comment
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'PostHandling/submit_comment.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // Add the new comment to the comment list
                        const newComment = document.createElement('li');
                        newComment.innerHTML = '<strong>You:</strong> ' + commentText + ' <span class="comment-date">(Just now)</span>';
                        commentList.prepend(newComment);

                        // Update the comment count
                        const newCommentCount = parseInt(commentCountButton.textContent.match(/\d+/)[0]) + 1;
                        commentCountButton.textContent = 'Comments (' + newCommentCount + ')';

                        // Clear the comment form
                        commentForm.querySelector('textarea').value = '';
                    }
                };
                xhr.send('post_id=' + postId + '&comment_text=' + encodeURIComponent(commentText));
            });
        });
    </script>
</body>

</html>