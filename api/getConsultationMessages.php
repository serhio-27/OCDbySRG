<?php
session_start();
include_once('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    echo json_encode(['error' => 'Не указан ID приёма']);
    exit;
}

// Проверяем права доступа
$stmt = $db->prepare("
    SELECT u.id, u.type
    FROM users u
    JOIN appointments a ON (a.doctor_id = u.id OR a.patient_id = u.id)
    WHERE u.api_token = ? AND a.id = ?
");
$stmt->execute([$_SESSION['token'], $appointment_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

// Получаем сообщения
$stmt = $db->prepare("
    SELECT 
        m.id,
        m.message,
        m.created_at,
        m.sender_id,
        m.file_path,
        u.name,
        u.surname,
        u.type as sender_type
    FROM consultation_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.appointment_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$appointment_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['messages' => $messages]); 