<?php 
session_start(); 
include("../connection/db.php");

// Check database connection
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {     
    $email = mysqli_real_escape_string($con, $_POST['mail']);     
    $password = mysqli_real_escape_string($con, $_POST['pass']);      

    if (!empty($email) && !empty($password)) {         
        // Check if user exists in the admin table
        $admin_query = "SELECT * FROM admin WHERE LOWER(email) = LOWER('$email') LIMIT 1";         
        $admin_result = mysqli_query($con, $admin_query);

        if ($admin_result && mysqli_num_rows($admin_result) > 0) {             
            $admin_data = mysqli_fetch_assoc($admin_result);

            if (password_verify($password, $admin_data['password'])) {                 
                $_SESSION['admin_id'] = $admin_data['id'];                 
                $_SESSION['email'] = $admin_data['email'];                  
                header("Location: ../admin.php");                 
                die;             
            } else {                 
                echo "<script>alert('Invalid email or password. Please try again.');</script>";
            }         
        } else {
            // Check if user exists in the reg_form table
            $user_query = "SELECT * FROM reg_form WHERE LOWER(email) = LOWER('$email') LIMIT 1";         
            $user_result = mysqli_query($con, $user_query);

            if ($user_result && mysqli_num_rows($user_result) > 0) {             
                $user_data = mysqli_fetch_assoc($user_result);

                if (password_verify($password, $user_data['pass'])) {                 
                    $_SESSION['user_id'] = $user_data['id'];                 
                    $_SESSION['email'] = $user_data['email'];                  
                    header("Location: ../PublicPost.php");                 
                    die;             
                } else {                 
                    echo "<script>alert('Invalid email or password. Please try again.');</script>";
                }         
            } else {             
                echo "<script>alert('Invalid email or password. Please try again.');</script>";
            } 
        }
    } else {         
        echo "<script>alert('Please enter both email and password!');</script>";
    } 
} 
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-size: cover;
        transition: background 1s ease-in-out;
        background-color: black;
    }

    .login {
    background-color: rgba(0, 0, 0, 0.6); /* Improved readability */
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.8); /* Base shadow */
    animation: smoothGlow 3s infinite ease-in-out, slideUp 1s ease-out; /* Combine animations */
    text-align: center;
    color: navy;
    width: 300px;
}

@keyframes smoothGlow {
    0% {
        box-shadow: 0px 0px 0px rgba(255, 255, 255, 0.5);
    }
    25% {
        box-shadow: 0px 0px 15px rgba(255, 255, 255, 0.7);
    }
    50% {
        box-shadow: 0px 0px 25px rgba(255, 255, 255, 0.9);
    }
    75% {
        box-shadow: 0px 0px 15px rgba(255, 255, 255, 0.7);
    }
    100% {
        box-shadow: 0px 0px 0px rgba(255, 255, 255, 0.5);
    }
}


@keyframes slideUp {
    0% {
        transform: translateY(20px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

    h1 {
        margin-bottom: 20px;
        font-size: 27px;
        color: #00ff0f;
    }

    label {
        display: block;
        text-align: left;
        margin: 10px 0 5px;
        font-size: 16px;
        color: rgb(225, 225, 225);
    }

    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        background-color: black;
        color: lime;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    input[type="submit"] {
        width: 100%;
        padding: 10px;
        background-color: #11b63d6b;
        border: 1px;
        color: white;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }

    input[type="submit"]:hover {
        background-color: #17fc03;
        color: black;
        font-weight: bold;
    }

    p {
        margin-top: 20px;
    }

    a {
        color: #007BFF;
        text-decoration: none;
    }

    a:hover {
        color: teal;
    }


    /* Slide Left Animation */
    @keyframes slideLeft {
        from {
            transform: translateX(-100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .background-images {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: -1;
        background-size: cover;
        background-position: center;
        animation: slideBackground 20s infinite;
    }

    @keyframes slideBackground {
        0% {
            background-image: url('images/image1.jpg');
        }

        25% {
            background-image: url('images/image2.jpg');
        }

        50% {
            background-image: url('images/image3.jpg');
        }

        75% {
            background-image: url('images/image4.jpg');
        }

        100% {
            background-image: url('images/image5.jpg');
        }
    }
    </style>
</head>

<body>

    <!-- Background Animation -->
    <div class="background-images"></div>

    <div class="login">
        <h1>Login</h1>
        <form method="POST">
            <label>Email</label>
            <input type="email" name="mail" required>
            <label>Password</label>
            <input type="password" name="pass" required>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>

</body>

</html>