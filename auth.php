<?php
session_start();
require 'db.php';

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.html');
        exit;
    }
}

// Пример входа: phpmyadmin/ручное, или через форму:
// $_SESSION['user_id'] = 1;
// $_SESSION['user_role'] = 'admin';
?>
