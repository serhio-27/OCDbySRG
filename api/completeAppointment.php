<?php
session_start();
include_once('db.php');

// Проверка авторизации
if (!isset($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Проверка наличия ID приёма
if (!isset($_POST['appointment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Appointment ID is required']);
    exit;
}

$appointmentId = (int)$_POST['appointment_id'];

try {
    // Обновляем статус приёма на "completed"
    $stmt = $db->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
    $result = $stmt->execute([$appointmentId]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update appointment status']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 