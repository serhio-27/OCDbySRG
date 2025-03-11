<?php
session_start();

// Очищаем токен в базе данных
if(isset($_SESSION['token'])) {
    include_once 'db.php';
    $token = $_SESSION['token'];
    $stmt = $db->prepare("UPDATE users SET api_token = NULL WHERE api_token = ?");
    $stmt->execute([$token]);
}

// Очищаем сессию
session_unset();
session_destroy();

// Перенаправляем на главную
header('Location: ../index.php');
exit;
?> 