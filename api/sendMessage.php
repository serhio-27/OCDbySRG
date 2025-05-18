<?php
session_start();
include_once('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

// Инициализируем переменные
$appointment_id = null;
$message = '';
$file_path = null;
$file_original_name = null;
$file_type = null;

// Определяем тип входящих данных
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;
$isJson = strpos($contentType, 'application/json') !== false;

// Получаем данные в зависимости от типа запроса
if ($isMultipart || isset($_FILES['file'])) {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $message = $_POST['message'] ?? '';
    
    // Обработка файла
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = [
            'image/jpeg', 'image/png', 'image/gif', 
            'application/pdf', 
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip', 'application/x-zip-compressed'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['error' => 'Неподдерживаемый тип файла']);
            exit;
        }
        
        $upload_dir = __DIR__ . '/../uploads/consultation_files/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                echo json_encode(['error' => 'Не удалось создать директорию для загрузки']);
                exit;
            }
            chmod($upload_dir, 0777);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = 'uploads/consultation_files/' . $unique_filename;
        $full_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $full_path)) {
            chmod($full_path, 0644);
            $file_original_name = $_FILES['file']['name'];
            $file_type = $mime_type;
        } else {
            $error = error_get_last();
            echo json_encode(['error' => 'Ошибка при загрузке файла: ' . ($error['message'] ?? 'Неизвестная ошибка')]);
            exit;
        }
    }
} elseif ($isJson) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $appointment_id = $data['appointment_id'] ?? null;
    $message = $data['message'] ?? '';
} else {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $message = $_POST['message'] ?? '';
}

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

// Сохраняем сообщение
try {
    $stmt = $db->prepare("
        INSERT INTO consultation_messages 
        (appointment_id, sender_id, message, file_path, file_original_name, file_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $appointment_id, 
        $user['id'], 
        $message, 
        $file_path,
        $file_original_name,
        $file_type
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Error in sendMessage.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Ошибка при отправке сообщения']);
} 