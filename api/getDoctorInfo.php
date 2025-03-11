<?php
include_once('db.php');
header('Content-Type: application/json');

$doctor_id = $_GET['id'] ?? null;

if (!$doctor_id) {
    echo json_encode(['error' => 'Не указан ID врача']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            surname,
            patronymic,
            specialization,
            phone,
            email,
            type
        FROM users 
        WHERE id = ? AND type = 'doctor'
    ");
    
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        echo json_encode(['error' => 'Врач не найден']);
        exit;
    }
    
    // Форматируем данные для отображения
    $doctor['full_name'] = $doctor['surname'] . ' ' . $doctor['name'] . ' ' . $doctor['patronymic'];
    
    // Удаляем чувствительные данные перед отправкой
    unset($doctor['type']);
    
    echo json_encode($doctor);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка при получении данных врача']);
}
?> 