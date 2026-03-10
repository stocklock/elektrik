<?php
require_once 'db.php';

// Простейшая защита данных
function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
?>