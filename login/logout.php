<?php
// session_start();
// session_unset();
// session_destroy();
// header("Location: login.php");
// die;


session_start();
// Destroy all sessions
session_unset();
session_destroy();
// Redirect to the login page
header("Location: http://localhost/void/login/login.php");
exit();

?>
