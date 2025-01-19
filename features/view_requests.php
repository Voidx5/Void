<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

include("../parts/navigation1.php");
include('../connection/db.php');

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Handle Accept or Reject actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $request_id = $_POST['request_id'];
    $sender_id = $_POST['sender_id'];

    if ($action == 'accept') {
        // Accept the friend request: add the friendship (ensure no duplicates)
        $sql_accept = "INSERT INTO friendships (user_id, friend_id) 
                       SELECT ?, ? FROM DUAL 
                       WHERE NOT EXISTS (
                           SELECT 1 FROM friendships 
                           WHERE (user_id = ? AND friend_id = ?) 
                           OR (user_id = ? AND friend_id = ?)
                       )";
        $stmt_accept = mysqli_prepare($con, $sql_accept);
        mysqli_stmt_bind_param($stmt_accept, "iiiiii", $user_id, $sender_id, $user_id, $sender_id, $sender_id, $user_id);
        mysqli_stmt_execute($stmt_accept);

        // Update the friend request status to 'accepted'
        $sql_update = "UPDATE friend_requests SET status = 'accepted' WHERE id = ?";
        $stmt_update = mysqli_prepare($con, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "i", $request_id);
        mysqli_stmt_execute($stmt_update);
    } elseif ($action == 'reject') {
        // Reject the friend request: delete from friend_requests table
        $sql_reject = "DELETE FROM friend_requests WHERE id = ?";
        $stmt_reject = mysqli_prepare($con, $sql_reject);
        mysqli_stmt_bind_param($stmt_reject, "i", $request_id);
        mysqli_stmt_execute($stmt_reject);
    }
}

// Fetch pending friend requests for the logged-in user
$sql = "SELECT fr.id AS request_id, r.id AS sender_id, r.fname, r.lname, r.email, r.profile_picture
        FROM friend_requests fr
        JOIN reg_form r ON fr.sender_id = r.id
        WHERE fr.receiver_id = ? AND fr.status = 'pending'";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friend Requests</title>
    <link rel="stylesheet" href="../style/home.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
        }

        h1 {
            text-align: center;
            margin-top: 40px;
            margin-bottom: 20px;


        }

        .request-list {
            margin: 0 auto;
            width: 45%;
            border-radius: 15px;
            list-style: none;
            padding: 0;
            background-color: rgb(255, 255, 255);
        }

        .request-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }

        .request-item img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .request-item button {
            background-color: rgb(233, 163, 0);
            border-radius: 5px;
            margin-left: 25px;
            padding: 5px 10px;
        }

        .request-item button:hover {
            background-color: rgb(199, 85, 4);
            transition: 0.5s;
        }

        .view-profile-btn {
        display: inline-block;
        padding: 5px 10px; /* Adjusted padding for smaller size */
        font-size: 14px; /* Adjusted font size */
        font-weight: bold;
        color: #fff;
        background-color: rgb(233, 163, 0); /* Button color */
        border: none;
        border-radius: 5px;
        text-align: center;
        text-decoration: none; /* Remove underline */
        cursor: pointer;
        margin-left: 25px;
        transition: background-color 0.5s ease; /* Smooth transition for background color */
    }

    .view-profile-btn:hover {
        background-color: rgb(199, 85, 4); /* Hover color */
    }
    </style>
</head>

<body>
    <h1>Pending Friend Requests</h1>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <ul class="request-list">
            <?php while ($request = mysqli_fetch_assoc($result)): ?>
                <li class="request-item">
                    <img src="../uploads/<?php echo $request['profile_picture']; ?>" alt="Profile Picture">
                    <?php echo $request['fname'] . " " . $request['lname'] . " (" . $request['email'] . ")"; ?>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                        <input type="hidden" name="sender_id" value="<?php echo $request['sender_id']; ?>">
                        <button type="submit" name="action" value="accept">Accept</button>
                        <button type="submit" name="action" value="reject">Reject</button>
                        <a href="../view_profile.php?user_id=<?php echo $request['sender_id']; ?>" class="view-profile-btn">
                            View Profile
                        </a>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No pending friend requests.</p>
    <?php endif; ?>

</body>

</html>