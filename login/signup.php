<?php
session_start();
include("../connection/db.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $fname = mysqli_real_escape_string($con, $_POST['fname']);
    $lname = mysqli_real_escape_string($con, $_POST['lname']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $cnad = mysqli_real_escape_string($con, $_POST['number']);
    $address = mysqli_real_escape_string($con, $_POST['add']);
    $email = mysqli_real_escape_string($con, $_POST['mail']);
    $password = password_hash($_POST['pass'], PASSWORD_BCRYPT);
    $profile_picture = $_FILES['profile_picture'];

    // Default profile picture path
    $target_dir = "../uploads/";
    $default_profile_pic = $target_dir . "default_profile.jpg";
    $target_file = $default_profile_pic;

    // Check if email already exists
    $email_check_query = "SELECT * FROM reg_form WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($con, $email_check_query);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Email already exists. Please use a different email.');</script>";
    } else {
        // Check if a profile picture was uploaded
        if ($profile_picture['error'] === UPLOAD_ERR_OK) {
            $uploaded_file = $target_dir . basename($profile_picture["name"]);
            $imageFileType = strtolower(pathinfo($uploaded_file, PATHINFO_EXTENSION));

            if (!file_exists('../uploads')) {
                mkdir('../uploads', 0777, true);
            }

            // Validate and move the uploaded file
            if (move_uploaded_file($profile_picture["tmp_name"], $uploaded_file)) {
                $target_file = $uploaded_file; // Use uploaded file if successful
            } else {
                echo "<script>alert('Error uploading file. Using default profile picture.');</script>";
            }
        }

        // Insert into the database
        $query = "INSERT INTO reg_form (fname, lname, gender, cnad, address, email, pass, profile_picture) 
                  VALUES ('$fname', '$lname', '$gender', '$cnad', '$address', '$email', '$password', '$target_file')";
        mysqli_query($con, $query);

        echo "<script>alert('Successfully Registered!');</script>";
        header('Location: http://localhost/void/login/login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: backgroundChange 20s infinite;
            background-size: cover;
            background-position: center;
        }

        @keyframes backgroundChange {
            0% { background-image: url('https://images.pexels.com/photos/620337/pexels-photo-620337.jpeg?auto=compress&cs=tinysrgb&w=600'); }
            25% { background-image: url('https://media.istockphoto.com/id/493109012/photo/lake.jpg?s=612x612&w=0&k=20&c=eW8t-StiKlVGLVC0Fw98ZJxG9r0uUZvyrtOBrPDZjb0='); }
            50% { background-image: url('https://media.istockphoto.com/id/830690680/photo/beautiful-autumn-landscape-altai-mountains-russia.jpg?s=612x612&w=0&k=20&c=3KR56OEvCXjoTTgMb4xv0kENfBeRPxKuj0ZoYRLvr2M='); }
            75% { background-image: url('https://images.pexels.com/photos/1743165/pexels-photo-1743165.jpeg?auto=compress&cs=tinysrgb&w=400'); }
            100% { background-image: url('https://images.pexels.com/photos/346529/pexels-photo-346529.jpeg?auto=compress&cs=tinysrgb&w=400'); }
        }

        .signup {
            max-width: 350px;
            padding: 25px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 30px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
            color: rgb(219, 231, 224);
            text-align: center;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        form input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
            background-color: grey;
        }

        form input[type="submit"] {
            background-color: teal;
            color: lime;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        form input[type="submit"]:hover {
            background-color: #218838;
        }

        span#wrong_pass_alert {
            display: block;
            margin-top: 5px;
            font-size: 14px;
            font-weight: bold;
        }

        .match {
            color: green;
        }

        .mismatch {
            color: red;
        }

        table {
            margin-top: 10px;
            margin-bottom: 10px;
            width: 100%;
        }
    </style>
    <script>
        function checkPasswordMatch() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const alertSpan = document.getElementById("wrong_pass_alert");

            if (password === confirmPassword) {
                alertSpan.textContent = "✔ Passwords match!";
                alertSpan.className = "match";
            } else {
                alertSpan.textContent = "✘ Passwords do not match!";
                alertSpan.className = "mismatch";
            }
        }
    </script>
</head>
<body>
    <div class="signup">
        <h1>Sign Up</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="grid-container">
                <div>
                    <label>First Name</label>
                    <input type="text" name="fname" required>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="lname" required>
                </div>
            </div>
            <label>Bio</label>
            <input type="text" name="add" value="Live Free" required>
            <table>
                <tr>
                    <th>Gender</th>
                    <td><label for="male">Male</label><br>
                    <input type="radio" id="male" name="gender" value="Male"></td>
                    <td><label for="female">Female</label><br>
                    <input type="radio" id="female" name="gender" value="Female"></td>
                    <td><label for="other">Other</label><br>
                    <input type="radio" id="other" name="gender" value="Other"></td>
                </tr>
            </table>
            <div class="grid-container">
                <div>
                    <label>Contact Number</label>
                    <input type="tel" name="number" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="mail" required>
                </div>
            </div>
            <div class="grid-container">
                <div>
                    <label>Password</label>
                    <input type="password" id="password" name="pass" required>
                </div>
                <div>
                    <label>Confirm Password</label>
                    <input type="password" id="confirm_password" onkeyup="checkPasswordMatch()" required>
                </div>
            </div>
            <span id="wrong_pass_alert"></span>
            <label>Profile Picture</label>
            <input type="file" name="profile_picture">
            <input type="submit" value="Sign Up">
        </form>
        <br>
        Have an account? <a href="login.php">Login</a>
    </div>
</body>
</html>