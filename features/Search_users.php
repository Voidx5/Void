<?php
session_start();
include('../connection/db.php');
include('../parts/navigation1.php');

if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

$sender_id = $_SESSION['user_id'];

// Handle the "Add Friend", "Cancel Request", and "Remove Friend" logic via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receiver_id'])) {
    $receiver_id = $_POST['receiver_id'];
    $action = $_POST['action']; // 'add', 'cancel', or 'remove'

    if ($action == 'add') {
        // Check if they are already friends
        $check_friendship = "SELECT * FROM friendships 
                             WHERE (user_id = ? AND friend_id = ?) 
                             OR (friend_id = ? AND user_id = ?)";
        $stmt = mysqli_prepare($con, $check_friendship);
        mysqli_stmt_bind_param($stmt, "iiii", $sender_id, $receiver_id, $sender_id, $receiver_id);
        mysqli_stmt_execute($stmt);
        $friendship_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($friendship_result) > 0) {
            echo "You are already friends with this user.";
        } else {
            // Check if a pending friend request already exists
            $check_request = "SELECT * FROM friend_requests 
                              WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
            $request_stmt = mysqli_prepare($con, $check_request);
            mysqli_stmt_bind_param($request_stmt, "ii", $sender_id, $receiver_id);
            mysqli_stmt_execute($request_stmt);
            $request_result = mysqli_stmt_get_result($request_stmt);

            if (mysqli_num_rows($request_result) > 0) {
                echo "Friend request already sent.";
            } else {
                // Send a new friend request
                $send_request = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
                $insert_stmt = mysqli_prepare($con, $send_request);
                mysqli_stmt_bind_param($insert_stmt, "ii", $sender_id, $receiver_id);
                if (mysqli_stmt_execute($insert_stmt)) {
                    echo "Friend request sent successfully!";
                } else {
                    echo "Error sending friend request.";
                }
            }
        }
    } elseif ($action == 'cancel') {
        // Cancel the friend request
        $cancel_request = "DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ?";
        $cancel_stmt = mysqli_prepare($con, $cancel_request);
        mysqli_stmt_bind_param($cancel_stmt, "ii", $sender_id, $receiver_id);
        if (mysqli_stmt_execute($cancel_stmt)) {
            echo "Friend request canceled.";
        } else {
            echo "Error canceling friend request.";
        }
    } elseif ($action == 'remove') {
        // Remove the friend (delete the friendship)
        $remove_friendship = "DELETE FROM friendships 
                              WHERE (user_id = ? AND friend_id = ?) 
                              OR (friend_id = ? AND user_id = ?)";
        $remove_stmt = mysqli_prepare($con, $remove_friendship);
        mysqli_stmt_bind_param($remove_stmt, "iiii", $sender_id, $receiver_id, $sender_id, $receiver_id);
        if (mysqli_stmt_execute($remove_stmt)) {
            echo "Friend removed successfully.";
        } else {
            echo "Error removing friend.";
        }
    }
    exit();
}

// Display search form and results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_term'])) {
    $search_term = $_POST['search_term'];

    // SQL query to search users by fname, lname, email and get profile_picture, excluding the current user
    $sql = "SELECT id, fname, lname, email, profile_picture FROM reg_form 
            WHERE (fname LIKE ? OR lname LIKE ? OR email LIKE ?) 
            AND id != ?";
    $stmt = mysqli_prepare($con, $sql);
    $search_term = "%$search_term%";
    mysqli_stmt_bind_param($stmt, "sssi", $search_term, $search_term, $search_term, $sender_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if any results are found
    if (mysqli_num_rows($result) > 0) {
        echo "<ul class='search-results'>";
        while ($user = mysqli_fetch_assoc($result)) {
            $receiver_id = $user['id'];
            $full_name = $user['fname'] . " " . $user['lname'];
            $email = $user['email'];
            $profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default_profile.png'; // Default profile pic

            // Check if they are friends
            $check_friendship = "SELECT * FROM friendships 
                                 WHERE (user_id = ? AND friend_id = ?) 
                                 OR (friend_id = ? AND user_id = ?)";
            $stmt2 = mysqli_prepare($con, $check_friendship);
            mysqli_stmt_bind_param($stmt2, "iiii", $sender_id, $receiver_id, $sender_id, $receiver_id);
            mysqli_stmt_execute($stmt2);
            $friendship_result = mysqli_stmt_get_result($stmt2);

            // Check if a pending friend request exists
            $check_request = "SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
            $request_stmt = mysqli_prepare($con, $check_request);
            mysqli_stmt_bind_param($request_stmt, "ii", $sender_id, $receiver_id);
            mysqli_stmt_execute($request_stmt);
            $request_result = mysqli_stmt_get_result($request_stmt);

            echo "<li>";
            echo "<img src='../uploads/$profile_picture' alt='Profile Picture' width='50' height='50'> "; // Display profile picture
            echo "$full_name ($email)";
            
            // Add the "View Profile" button
            echo " <a href='../view_profile.php?user_id=$receiver_id' class='view-profile-btn'><button>View Profile</button></a>";
            
            if (mysqli_num_rows($friendship_result) > 0) {
                // Already friends
                echo " <button class='remove-friend-btn' data-receiver-id='$receiver_id'>Remove Friend</button>";
            } elseif (mysqli_num_rows($request_result) > 0) {
                // Pending friend request
                echo " <button class='cancel-request-btn' data-receiver-id='$receiver_id'>Cancel</button>";
            } else {
                // Show "Add Friend" button if no pending request
                echo " <button class='add-friend-btn' data-receiver-id='$receiver_id'>Add Friend</button>";
            }
            
            echo "</li>";
            
        }
        echo "</ul>";
    } else {
        echo "No users found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/home.css">
    <title>Search User</title>
    <style>/* General styling */
     body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);

        }

        h1 {
            text-align: center;
            margin-top: 20px;
            color: #333;
        }

        form {
            max-width: 500px;
            margin: 20px auto;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        form input[type="text"] {
            width: 70%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        form button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .search-results {
            list-style: none;
            padding: 0;
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .search-results li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .search-results li:last-child {
            border-bottom: none;
        }

        .search-results img {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .search-results .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-results .user-info span {
            font-size: 16px;
            color: #555;
        }

        .search-results button {
            padding: 8px 12px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-results button:hover {
            background-color: #218838;
        }

        .search-results .cancel-request-btn {
            background-color: #dc3545;
        }

        .search-results .cancel-request-btn:hover {
            background-color: #c82333;
        }

        .search-results .remove-friend-btn {
            background-color: #ffc107;
        }

        .search-results .remove-friend-btn:hover {
            background-color: #e0a800;
        }

        .no-results {
            text-align: center;
            color: #888;
            font-size: 16px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Search Users</h1>
    <form method="POST">
        <input type="text" name="search_term" placeholder="Enter name or email" required>
        <button type="submit">Search</button>
    </form>

    <!-- The results will appear below -->

<script>
    // Handle the "Add Friend", "Cancel", and "Remove" button click events via AJAX
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-friend-btn') || e.target.classList.contains('cancel-request-btn') || e.target.classList.contains('remove-friend-btn')) {
            var receiverId = e.target.getAttribute('data-receiver-id');
            var action = '';

            if (e.target.classList.contains('add-friend-btn')) {
                action = 'add';
            } else if (e.target.classList.contains('cancel-request-btn')) {
                action = 'cancel';
            } else if (e.target.classList.contains('remove-friend-btn')) {
                action = 'remove';
            }

            // Send an AJAX request to handle friend request/cancel/remove
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'Search_users.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    // Update the button state based on the action
                    if (action == 'add') {
                        e.target.innerText = 'Cancel';
                        e.target.classList.remove('add-friend-btn');
                        e.target.classList.add('cancel-request-btn');
                    } else if (action == 'cancel') {
                        e.target.innerText = 'Add Friend';
                        e.target.classList.remove('cancel-request-btn');
                        e.target.classList.add('add-friend-btn');
                    } else if (action == 'remove') {
                        e.target.innerText = 'Add Friend';
                        e.target.classList.remove('remove-friend-btn');
                        e.target.classList.add('add-friend-btn');
                    }
                }
            };
            xhr.send('receiver_id=' + receiverId + '&action=' + action);
        }
    });
</script>
</html>
