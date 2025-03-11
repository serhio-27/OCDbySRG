<?php
session_start();
include_once('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = $data['appointment_id'] ?? null;
$message = $data['message'] ?? null;

if (!$appointment_id || !$message) {
    echo json_encode(['error' => 'Неверные параметры']);
    exit;
}

// Получаем ID отправителя
$stmt = $db->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$_SESSION['token']]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Пользователь не найден']);
    exit;
}

// Проверяем права доступа к консультации
$stmt = $db->prepare("
    SELECT id FROM appointments 
    WHERE id = ? AND (doctor_id = ? OR patient_id = ?)
");
$stmt->execute([$appointment_id, $user['id'], $user['id']]);

if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

// Сохраняем сообщение
try {
    $stmt = $db->prepare("
        INSERT INTO consultation_messages (appointment_id, sender_id, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$appointment_id, $user['id'], $message]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка при отправке сообщения']);
} 