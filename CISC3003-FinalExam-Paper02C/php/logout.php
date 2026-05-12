<?php
/**
 * Scenario C: Logout Script (C.04)
 */
session_start();
session_destroy();
header("Location: login.php");
exit;
?>