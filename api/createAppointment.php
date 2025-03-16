<?php
session_start();
include_once('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

// Получаем ID пациента
$stmt = $db->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$_SESSION['token']]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Пользователь не найден']);
    exit;
}

$doctor_id = $_POST['doctor_id'] ?? null;
$appointment_date = $_POST['appointment_date'] ?? null;
$appointment_time = $_POST['appointment_time'] ?? null;
$complaint = $_POST['complaint'] ?? null;

if (!$doctor_id || !$appointment_date || !$appointment_time) {
    echo json_encode(['error' => 'Не все данные предоставлены']);
    exit;
}

// Проверяем, не занято ли время
$stmt = $db->prepare("
    SELECT id FROM appointments 
    WHERE doctor_id = ? 
    AND appointment_date = ? 
    AND appointment_time = ? 
    AND status != 'cancelled'
");
$stmt->execute([$doctor_id, $appointment_date, $appointment_time]);

if ($stmt->fetch()) {
    echo json_encode(['error' => 'Это время уже занято']);
    exit;
}

// Создаем запись
try {
    $stmt = $db->prepare("
        INSERT INTO appointments 
        (patient_id, doctor_id, appointment_date, appointment_time, complaint, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user['id'], $doctor_id, $appointment_date, $appointment_time, $complaint]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка при создании записи']);
} 