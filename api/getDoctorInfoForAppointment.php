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

try {
    $stmt = $db->prepare("
        SELECT 
            d.id,
            d.name,
            d.surname,
            d.patronymic,
            d.specialization
        FROM appointments a
        JOIN users d ON a.doctor_id = d.id
        WHERE a.id = ?
    ");
    
    $stmt->execute([$appointment_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        echo json_encode(['error' => 'Врач не найден']);
        exit;
    }
    
    echo json_encode($doctor);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка при получении данных врача']);
} 