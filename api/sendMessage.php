<?php
session_start();
include_once('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? null;
$message = $_POST['message'] ?? '';

if (!$appointment_id) {
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

$file_path = null;

// Обработка загруженного файла
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/consultation_files/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '_' . $_FILES['file']['name'];
    $file_path = 'uploads/consultation_files/' . $file_name;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $file_name)) {
        // Файл успешно загружен
    } else {
        echo json_encode(['error' => 'Ошибка при загрузке файла']);
        exit;
    }
}

// Сохраняем сообщение
try {
    $stmt = $db->prepare("
        INSERT INTO consultation_messages (appointment_id, sender_id, message, file_path)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$appointment_id, $user['id'], $message, $file_path]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка при отправке сообщения']);
} 