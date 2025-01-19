<?php
session_start();
include('connection/db.php');
include('parts/navigation1.php');


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$query = "SELECT fname, lname, cnad, address, profile_picture, gender, email FROM reg_form WHERE id = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Fetch user's posts
$posts_query = "
    SELECT posts.id AS post_id, posts.post_content, posts.post_caption, posts.post_date, 
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count, 
           (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
    FROM posts 
    WHERE posts.user_id = ? 
    ORDER BY posts.post_date DESC";
$post_stmt = mysqli_prepare($con, $posts_query);
mysqli_stmt_bind_param($post_stmt, "i", $user_id);
mysqli_stmt_execute($post_stmt);
$posts_result = mysqli_stmt_get_result($post_stmt);


if (!empty($email) && !empty($password) && !is_numeric($email)) {

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($profile_picture["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Check if file is a valid image
    if (move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
        // Assuming you have the user ID or email to identify the record
        $user_id = $_SESSION['user_id']; // Replace with your session or identifier logic

        // Update only the profile_picture column
        $query = "UPDATE reg_form 
                      SET profile_picture = '$target_file' 
                      WHERE id = '$user_id'";

        // Execute the query
        if (mysqli_query($con, $query)) {
            echo "Profile picture updated successfully!";
        } else {
            echo "Error updating profile picture: " . mysqli_error($con);
        }
    } else {
        echo "Failed to upload the file.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/home.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .profile-container,
        .post-item,
        .post-item video {
            max-width: 700px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            transition: box-shadow 0.3s;
        }

        .post-item img {
            width: 100%;
            max-height: auto;
            border-radius: 4px;
            margin-top: 10px;
            object-fit: contain;
        }

        .profile-container:hover {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .user-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .user-info img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .user-info img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }

        .user-info h2 {
            color: #007bff;
        }

        table {
            margin: 20px auto;
            width: 80%;
            text-align: left;
        }

        table tr td {
            padding: 5px 10px;
        }

        .edit-form {
            display: none;
            margin-top: 20px;
        }

        .edit-form input {
            width: calc(100% - 20px);
            margin: 10px auto;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: block;
        }

        .edit-form .buttons {
            text-align: center;
        }

        .edit-form .buttons button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
        }

        .edit-form .buttons button:hover {
            background: #0056b3;
        }

        .user-posts h3 {
            color: #007bff;
            margin-bottom: 20px;
        }

        /* .post-item {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            transition: background 0.3s;
        } */

        .post-item:hover {
            background: #e9ecef;
        }

        /* .post-item img,
        .post-item video {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 10px;
            transition: transform 0.3s;
        }

        .post-item img:hover,
        .post-item video:hover {
            transform: scale(1.05);
        } */
    </style>
    <title>My Profile</title>
</head>

<body>
    <div class="profile-container">
        <div class="user-info">
            <img src="uploads/<?php echo $user['profile_picture'] ?: 'default_profile.png'; ?>" alt="Profile Picture">
            <h2><?php echo htmlspecialchars($user['fname'] . " " . $user['lname']); ?></h2>
            <h4><?php echo htmlspecialchars($user['address']); ?></h4>

            <table id="user-info-table">
                <tr>
                    <td><strong>Gender:</strong></td>
                    <td id="gender"><?php echo htmlspecialchars($user['gender']); ?></td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td id="phone"><?php echo htmlspecialchars($user['cnad']); ?></td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td id="email"><?php echo htmlspecialchars($user['email']); ?></td>
                </tr>

            </table>
            <button type="button" onclick="window.location.href='self/profile_update.php';">Edit Profile</button>

        </div>

        <div class="user-posts">
            <h3>Your Posts</h3>
            <?php if (mysqli_num_rows($posts_result) > 0): ?>
                <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                    <div class="post-item">
                        <small><strong>Posted on:</strong> <?php echo $post['post_date']; ?></small>
                        <p><?php echo htmlspecialchars($post['post_content']); ?></p>
                        <?php if (!empty($post['post_caption'])): ?>
                            <p><em><?php echo htmlspecialchars($post['post_caption']); ?></em></p>
                        <?php endif; ?>

                        <!-- Display media related to the post -->
                        <?php
                        $media_query = "SELECT media_type, media_path FROM post_media WHERE post_id = ?";
                        $media_stmt = mysqli_prepare($con, $media_query);
                        mysqli_stmt_bind_param($media_stmt, "i", $post['post_id']);
                        mysqli_stmt_execute($media_stmt);
                        $media_result = mysqli_stmt_get_result($media_stmt);
                        while ($media = mysqli_fetch_assoc($media_result)):
                        ?>
                            <?php if ($media['media_type'] == 'image'): ?>
                                <img src="uploads/<?php echo $media['media_path']; ?>" alt="Post Image" width="200">
                            <?php elseif ($media['media_type'] == 'video'): ?>
                                <video width="300" controls>
                                    <source src="uploads/<?php echo $media['media_path']; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        <?php endwhile; ?>

                        <!-- Post Likes and Comments -->
                        <div class="post-interactions">
                            <p>Likes: <?php echo $post['like_count']; ?> | Comments: <?php echo $post['comment_count']; ?></p>

                            <!-- Buttons to view likes, comments, and hide them -->
                            <button class="view-likes" data-post-id="<?php echo $post['post_id']; ?>">View Likes</button>
                            <button class="view-comments" data-post-id="<?php echo $post['post_id']; ?>">View Comments</button>
                            <button class="hide-interactions" data-post-id="<?php echo $post['post_id']; ?>" style="display: none;">Hide</button>

                            <!-- Container for showing likes and comments -->
                            <div class="likes-container" id="likes-<?php echo $post['post_id']; ?>" style="display: none;"></div>
                            <div class="comments-container" id="comments-<?php echo $post['post_id']; ?>" style="display: none;"></div>
                        </div>
                    </div>
                    <hr>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>

    </div>
    <script>
        // Function to handle fetching and displaying likes
        function fetchLikes(postId) {
            fetch('self/fetch_likes.php?post_id=' + postId)
                .then(response => response.json())
                .then(data => {
                    const likesContainer = document.getElementById('likes-' + postId);
                    likesContainer.innerHTML = data.likes.map(like => '<p>' + like.user_name + '</p>').join('');
                    likesContainer.style.display = 'block';

                    // Show the hide button when likes/comments are visible
                    document.querySelector('.hide-interactions[data-post-id="' + postId + '"]').style.display = 'inline';
                });
        }

        // Function to handle fetching and displaying comments
        function fetchComments(postId) {
            fetch('self/fetch_comments.php?post_id=' + postId)
                .then(response => response.json())
                .then(data => {
                    const commentsContainer = document.getElementById('comments-' + postId);
                    commentsContainer.innerHTML = data.comments.map(comment => '<p><strong>' + comment.user_name + ':</strong> ' + comment.comment_text + '</p>').join('');
                    commentsContainer.style.display = 'block';

                    // Show the hide button when likes/comments are visible
                    document.querySelector('.hide-interactions[data-post-id="' + postId + '"]').style.display = 'inline';
                });
        }

        // Function to hide likes and comments
        function hideInteractions(postId) {
            document.getElementById('likes-' + postId).style.display = 'none';
            document.getElementById('comments-' + postId).style.display = 'none';

            // Hide the hide button itself when interactions are hidden
            document.querySelector('.hide-interactions[data-post-id="' + postId + '"]').style.display = 'none';
        }

        // Attach click event listeners to buttons
        document.querySelectorAll('.view-likes').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                fetchLikes(postId);
            });
        });

        document.querySelectorAll('.view-comments').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                fetchComments(postId);
            });
        });

        document.querySelectorAll('.hide-interactions').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                hideInteractions(postId);
            });
        });
    </script>
</body>