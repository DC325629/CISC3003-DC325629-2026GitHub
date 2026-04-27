<?php
session_start();

// 清除所有会话变量
$_SESSION = array();

// 如果使用 session cookie，也删除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 最后销毁会话
session_destroy();

// 重定向到首页
header('Location: index.php');
exit;