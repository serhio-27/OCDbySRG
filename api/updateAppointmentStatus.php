<?php
session_start();
include_once('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

// Проверяем, что запрос от врача
$stmt = $db->prepare("SELECT id FROM users WHERE api_token = ? AND type = 'doctor'");
$stmt->execute([$_SESSION['token']]);
$doctor = $stmt->fetch();

if (!$doctor) {
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = $data['appointment_id'] ?? null;
$status = $data['status'] ?? null;

if (!$appointment_id || !$status) {
    echo json_encode(['error' => 'Неверные параметры']);
    exit;
}

// Проверяем, что запись принадлежит этому врачу
$stmt = $db->prepare("
    SELECT id 
    FROM appointments 
    WHERE id = ? AND doctor_id = ?
");
$stmt->execute([$appointment_id, $doctor['id']]);

if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Запись не найдена']);
    exit;
}

// Обновляем статус
try {
    $stmt = $db->prepare("
        UPDATE appointments 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $appointment_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка при обновлении статуса']);
} 