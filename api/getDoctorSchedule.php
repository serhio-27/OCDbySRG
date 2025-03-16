<?php
include_once('db.php');
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-d');

if (!$doctor_id) {
    echo json_encode(['error' => 'Не указан ID врача']);
    exit;
}

// Получаем занятые слоты
$stmt = $db->prepare("
    SELECT appointment_date, appointment_time 
    FROM appointments 
    WHERE doctor_id = ? 
    AND appointment_date >= ? 
    AND status != 'cancelled'
");
$stmt->execute([$doctor_id, $start_date]);
$booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'booked_slots' => $booked_slots
]); 