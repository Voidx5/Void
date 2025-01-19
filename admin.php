<?php
session_start();
include 'connection/db.php'; // Include your database connection file
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Message holder for notifications
$message = "";

// Log admin activity
function logAdminActivity($adminId, $actionType, $targetTable, $targetId, $con) {
    $stmt = $con->prepare("INSERT INTO admin_activity (admin_id, action_type, target_table, target_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $adminId, $actionType, $targetTable, $targetId);
    $stmt->execute();
}

// Add a new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT); // Hash the password

    if (!empty($email) && !empty($password)) {
        $stmt = $con->prepare("INSERT INTO admin (email, password) VALUES (?, ?)");
        if ($stmt->execute([$email, $password])) {
            $message = "Admin added successfully.";
            logAdminActivity($_SESSION['admin_id'], 'create_admin', 'admin', $con->insert_id, $con);
        } else {
            $message = "Error adding admin.";
        }
    } else {
        $message = "All fields are required.";
    }
}

// Search for users or admins
$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = trim($_POST['search_term']);
    if (!empty($searchTerm)) {
        $searchTermWildcard = "%$searchTerm%";

        // Search in reg_form
        $stmt = $con->prepare("SELECT id, fname, lname, email FROM reg_form WHERE fname LIKE ? OR lname LIKE ? OR email LIKE ?");
        $stmt->bind_param("sss", $searchTermWildcard, $searchTermWildcard, $searchTermWildcard);
        $stmt->execute();
        $result = $stmt->get_result();
        $searchResults['users'] = [];
        while ($row = $result->fetch_assoc()) {
            $searchResults['users'][] = $row;
        }

        // Search in admin
        $stmt = $con->prepare("SELECT id, email FROM admin WHERE email LIKE ?");
        $stmt->bind_param("s", $searchTermWildcard);
        $stmt->execute();
        $result = $stmt->get_result();
        $searchResults['admins'] = [];
        while ($row = $result->fetch_assoc()) {
            $searchResults['admins'][] = $row;
        }
    } else {
        $message = "Search term cannot be empty.";
    }
}

// Delete user or admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $target_table = $_POST['target_table'];

    if ($target_table === 'reg_form') {
        $stmt = $con->prepare("DELETE FROM reg_form WHERE id = ?");
        $actionType = 'delete_user';
    } elseif ($target_table === 'admin') {
        $stmt = $con->prepare("DELETE FROM admin WHERE id = ?");
        $actionType = 'delete_admin';
    }

    if (isset($stmt) && $stmt->execute([$user_id])) {
        $message = "Record deleted successfully.";
        logAdminActivity($_SESSION['admin_id'], $actionType, $target_table, $user_id, $con);
    } else {
        $message = "Error deleting record.";
    }
}

// Fetch all admin activities for the notice board
$stmt = $con->prepare("SELECT aa.id, aa.action_type, aa.target_table, aa.target_id, aa.action_timestamp, a.email AS admin_email FROM admin_activity aa JOIN admin a ON aa.admin_id = a.id ORDER BY aa.action_timestamp DESC");
$stmt->execute();
$adminActivities = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/home.css">

    <title>Admin Panel</title>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f9;
    color: #333;
}

h1, h2, h3 {
    text-align: center;
    margin: 20px 0;
    color: #444;
}

p {
    text-align: center;
    color: #555;
}

/* Navbar */
.navbar {
    background-color:rgb(116, 48, 0);
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #fff;
}

.navbar a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    margin-right: 15px;
    transition: color 0.3s ease;
}

.navbar a:hover {
    color: #dfe6e9;
}

/* Container Styles */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

/* Form Styles */
form {
    margin: 20px 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

form input,
form button {
    padding: 10px 15px;
    margin: 10px 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

form button {
    background-color: #0056b3;
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

form button:hover {
    background-color: #003f7f;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 16px;
}

table th, table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

table th {
    background-color:rgb(114, 114, 114);
    color: #fff;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #f1f1f1;
}

/* Buttons */
button {
    padding: 8px 12px;
    background-color: #d63031;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #c0392b;
}

button.add-btn {
    background-color: #00b894;
}

button.add-btn:hover {
    background-color: #009975;
}

button.delete-btn {
    background-color: #e74c3c;
}

button.delete-btn:hover {
    background-color: #c0392b;
}

/* Responsive Design */
@media (max-width: 768px) {
    table {
        font-size: 14px;
    }

    .navbar {
        flex-direction: column;
        text-align: center;
    }

    form {
        flex-direction: column;
        align-items: center;
    }
}

</style>
    
</head>
<body>
    
<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo or Brand Name -->
        <a href="PublicPost.php" class="navbar-brand">
        <div class="void-text" onclick="voidx();">VO!D</div>        </a>

        </a>

        <!-- Navbar Links -->
        <ul class="navbar-links">


            <li><a href="login/logout.php">Logout</a></li>
        </ul>

        <!-- Search Bar -->
    </div>
</nav>

<!-- JavaScript to toggle the dropdown -->
<script>
function toggleDropdown() {
    var dropdown = document.getElementById("friendsDropdown");
    if (dropdown.style.display === "block") {
        dropdown.style.display = "none";
    } else {
        dropdown.style.display = "block";
    }
}
function voidx() {
    window.location.href = "http://localhost/void/admin.php";
}
</script>
    <h1>Admin Panel</h1>
    <?php if (!empty($message)) echo "<p>$message</p>"; ?>

    <h2>Add New Admin</h2>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="Admin Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_admin">Add Admin</button>
    </form>

    <h2>Search Users or Admins</h2>
    <form method="POST" action="">
        <input type="text" name="search_term" placeholder="Search by name or email" required>
        <button type="submit" name="search">Search</button>
    </form>

    <?php if (!empty($searchResults)): ?>
        <h3>Search Results</h3>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Action</th>
            </tr>
            <?php if (!empty($searchResults['users'])): ?>
                <?php foreach ($searchResults['users'] as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= $user['fname'] . " " . $user['lname'] ?></td>
                        <td><?= $user['email'] ?></td>
                        <td>User</td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="target_table" value="reg_form">
                                <button type="submit" name="delete_user">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($searchResults['admins'])): ?>
                <?php foreach ($searchResults['admins'] as $admin): ?>
                    <tr>
                        <td><?= $admin['id'] ?></td>
                        <td>-</td> <!-- No name column in admin table -->
                        <td><?= $admin['email'] ?></td>
                        <td>Admin</td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                <input type="hidden" name="target_table" value="admin">
                                <button type="submit" name="delete_user">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    <?php endif; ?>

    <h2>Admin Activity Notice Board</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Admin Email</th>
            <th>Action Type</th>
            <th>Target Table</th>
            <th>Target ID</th>
            <th>Timestamp</th>
        </tr>
        <?php while ($activity = $adminActivities->fetch_assoc()): ?>
            <tr>
                <td><?= $activity['id'] ?></td>
                <td><?= $activity['admin_email'] ?></td>
                <td><?= $activity['action_type'] ?></td>
                <td><?= $activity['target_table'] ?></td>
                <td><?= $activity['target_id'] ?></td>
                <td><?= $activity['action_timestamp'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>