<?php
session_start();
include('../connection/db.php');
include('../parts/navigation1.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $caption = $_POST['caption'];
    $post_type = $_POST['post_type'];  // 'public' or 'friend'
    $post_content = $_POST['post_content'];  // Any text content
    $media_files = $_FILES['media'];  // Handle media upload

    // Insert the post data into the posts table
    $insert_post = "INSERT INTO posts (user_id, post_content, post_caption, post_type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $insert_post);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $post_content, $caption, $post_type);
    
    if (mysqli_stmt_execute($stmt)) {
        $post_id = mysqli_insert_id($con);

        // Handle media uploads (images/videos)
        if (!empty($media_files['name'][0])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi'];
            for ($i = 0; $i < count($media_files['name']); $i++) {
                $media_type = '';
                if (in_array($media_files['type'][$i], ['image/jpeg', 'image/png', 'image/gif'])) {
                    $media_type = 'image';
                } elseif (in_array($media_files['type'][$i], ['video/mp4', 'video/avi'])) {
                    $media_type = 'video';
                }

                if ($media_type) {
                    $file_name = basename($media_files['name'][$i]);
                    $target_file = "../uploads/" . $file_name;
                    
                    // Try to move the uploaded file
                    if (move_uploaded_file($media_files['tmp_name'][$i], $target_file)) {
                        // Insert the media into the post_media table
                        $insert_media = "INSERT INTO post_media (post_id, media_type, media_path) VALUES (?, ?, ?)";
                        $media_stmt = mysqli_prepare($con, $insert_media);
                        mysqli_stmt_bind_param($media_stmt, "iss", $post_id, $media_type, $target_file);
                        mysqli_stmt_execute($media_stmt);
                    } else {
                        echo "Error uploading file: " . $_FILES['media']['error'][$i]; // Display the error code
                    }
                }
            }
        }

        echo "Post successfully created!";
    } else {
        echo "Error creating post.";
    }
}

// Display user's profile picture and name
$user_query = "SELECT fname, lname, profile_picture FROM reg_form WHERE id = ?";
$stmt = mysqli_prepare($con, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
$profile_picture = $user['profile_picture'] ? $user['profile_picture'] : 'default_profile.png';
$full_name = $user['fname'] . " " . $user['lname'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
     <link rel="stylesheet" href="\void\style\home.css">
   <style>
    /* Base reset */
.body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

.body {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
    color: #333;
    height: 100vh;
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}

.user-info:hover {
    transform: translateY(-10px);
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
}

.user-info img {
    border-radius: 50%;
    margin-right: 15px;
    width: 50px;
    height: 50px;
}

.user-info h2 {
    font-size: 1.5rem;
    color: #333;
}

#post{
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 400px;
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
}

#post:hover {
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
}

#post textarea, input[type="text"], select {
    margin-bottom: 15px;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s ease-in-out, background-color 0.3s ease-in-out;
}

#post  textarea {
    height: 100px;
    resize: none;
}

#post  input[type="text"]:focus, textarea:focus, select:focus {
    border-color: #333;
    background-color: #f9f9f9;
}

#post  input[type="file"] {
    margin-bottom: 20px;
}

#post button {
    padding: 10px 15px;
    background-color: #333;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
}

#post button:hover {
    background-color: #555;
    transform: translateY(-2px);
}

   </style>
    <title>Create a Post</title>
</head>
<body>
    <div class="body">
    <div class="user-info">
        <img src="../uploads/<?php echo $profile_picture; ?>" alt="Profile Picture" width="50" height="50">
        <h2><?php echo $full_name; ?></h2>
    </div>

    <form method="POST" enctype="multipart/form-data" id="post">
        <textarea name="post_content" placeholder="Write something..."></textarea>
        <input type="text" name="caption" placeholder="Caption (optional)">
        <label for="post_type">Post Visibility:</label>
        <select name="post_type">
            <option value="public">Public</option>
            <option value="friend">Friends Only</option>
        </select>
        <input type="file" name="media[]" multiple>
        <button type="submit">Post</button>
    </form>
</div>
</body>
</html>
