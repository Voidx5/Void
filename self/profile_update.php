<?php
session_start();
include('../connection/db.php');
include('../parts/navigation1.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the logged-in user's details
$query = "SELECT * FROM reg_form WHERE id = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $fname = mysqli_real_escape_string($con, $_POST['fname']);
    $lname = mysqli_real_escape_string($con, $_POST['lname']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $cnad = mysqli_real_escape_string($con, $_POST['cnad']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $profile_picture = $_FILES['profile_picture'];

    // Validate new password only if provided
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!empty($new_password) || !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            echo "<script>alert('Passwords do not match.');</script>";
            exit();
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // If a new profile picture is uploaded
    if ($profile_picture['size'] > 0) {
        $target_dir = "../uploads/"; // Directory inside the current working directory
        $file_name = uniqid() . "_" . basename($profile_picture["name"]); // Unique filename
        $target_file = $target_dir . $file_name;
        $relative_file_path =  $target_file; // Path to store in the database "../" .
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!in_array($imageFileType, $allowedTypes)) {
            echo "<script>alert('Invalid file type. Please upload JPG, JPEG, PNG, or GIF files only.');</script>";
            exit();
        }

        if (!move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
            echo "<script>alert('Failed to upload the file.');</script>";
            exit();
        }

        $update_picture_query = "UPDATE reg_form SET profile_picture = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $update_picture_query);
        mysqli_stmt_bind_param($stmt, "si", $relative_file_path, $user_id);
        mysqli_stmt_execute($stmt);
    }

    // Update other profile details
    $update_query = "UPDATE reg_form SET fname = ?, lname = ?, email = ?, gender = ?, cnad = ?, address = ?";

    // Include password update if new password is provided
    if (!empty($new_password)) {
        $update_query .= ", pass = ?";
    }

    $update_query .= " WHERE id = ?";

    $stmt = mysqli_prepare($con, $update_query);

    if (!empty($new_password)) {
        mysqli_stmt_bind_param($stmt, "sssssssi", $fname, $lname, $email, $gender, $cnad, $address, $hashed_password, $user_id);
    } else {
        mysqli_stmt_bind_param($stmt, "ssssssi", $fname, $lname, $email, $gender, $cnad, $address, $user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Profile updated successfully!'); window.location.href='../me.php';</script>";
    } else {
        echo "<script>alert('Error updating profile.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../style/home.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #e3f2fd, #bbdefb);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .profile-container {
            max-width: 700px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .profile-field {
            margin-bottom: 20px;
        }

        .profile-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .profile-field input,
        .profile-field select,
        .profile-field textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <h2>Profile Information</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="profile-field">
                <label for="fname">First Name:</label>
                <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" required>
            </div>
            <div class="profile-field">
                <label for="lname">Last Name:</label>
                <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($user['lname']); ?>" required>
            </div>
            <div class="profile-field">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="profile-field">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo $user['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $user['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $user['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="profile-field">
                <label for="cnad">Contact Number:</label>
                <input type="text" id="cnad" name="cnad" value="<?php echo htmlspecialchars($user['cnad']); ?>" required>
            </div>
            <div class="profile-field">
                <label for="address">Bio:</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            <div class="profile-field">
                <label for="profile_picture">Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture">
            </div>
            <div class="profile-field">
                <label for="new_password">Enter New Password:</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <div class="profile-field">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit">Update Profile</button>
            <button type="button" onclick="window.location.href='../me.php';">Cancel</button>
        </form>
    </div>

</body>

</html>