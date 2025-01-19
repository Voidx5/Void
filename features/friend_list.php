<?php
session_start();
include('../connection/db.php');
include('../parts/navigation1.php');

if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch friends for the logged-in user, excluding the user themselves
$sql = "SELECT reg_form.id, reg_form.fname, reg_form.lname, reg_form.email, reg_form.profile_picture
        FROM friendships
        JOIN reg_form ON 
            (friendships.user_id = reg_form.id AND friendships.friend_id = ?) 
            OR (friendships.friend_id = reg_form.id AND friendships.user_id = ?)
        WHERE reg_form.id != ?";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Friend List</title>
    <style>
        /* General Styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        h1 {
            font-size: 24px;
            color: #333;
            margin: 20px 0;
        }

        /* Friend List Container */
        .friend-list-container {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Friend List Styling */
        .friend-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .friend-list li {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .friend-list li:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .friend-list img {
            margin-right: 15px;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        /* Friend Details */
        .friend-details {
            flex: 1;
        }

        .friend-details h2 {
            font-size: 18px;
            margin: 0;
            color: #555;
        }

        .friend-details p {
            margin: 0;
            font-size: 14px;
            color: #777;
        }

        /* No Friends Message */
        .no-friends {
            text-align: center;
            font-size: 18px;
            color: #555;
            padding: 20px;
        }

        /* View Profile Button */
        .view-profile-btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background-color: rgb(233, 163, 0);
            border: none;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .view-profile-btn:hover {
            background-color: rgb(199, 85, 4);
            transform: scale(1.05);
        }

        /* Responsiveness */
        @media (max-width: 600px) {
            .friend-list li {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .friend-list img {
                margin-bottom: 10px;
            }
        }
    </style>
    <link rel="stylesheet" href="../style/home.css">
</head>
<body>
    <h1>Friend List</h1>
    <div class="friend-list-container">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <ul class="friend-list">
                <?php while ($friend = mysqli_fetch_assoc($result)): ?>
                    <li>
                        <img src="../uploads/<?php echo !empty($friend['profile_picture']) ? $friend['profile_picture'] : 'default_profile.png'; ?>" alt="Profile Picture">
                        <div class="friend-details">
                            <h2><?php echo $friend['fname'] . " " . $friend['lname']; ?></h2>
                            <p><?php echo $friend['email']; ?></p>
                        </div>
                        <a href="../view_profile.php?user_id=<?php echo $friend['id']; ?>" class="view-profile-btn">View Profile</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-friends">You have no friends in your friend list.</p>
        <?php endif; ?>
    </div>
</body>
</html>
