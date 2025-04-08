<?php
session_start();
include_once './db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка авторизации
    if (!isset($_SESSION['token'])) {
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        exit;
    }

    try {
        // Получаем ID пользователя
        $stmt = $db->prepare("SELECT id FROM users WHERE api_token = ?");
        $stmt->execute([$_SESSION['token']]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
            exit;
        }

        // Валидация данных (ДОБАВЛЕНО ОМС)
        $passport_series = preg_replace('/\D/', '', $_POST['passport_series']);
        $passport_number = preg_replace('/\D/', '', $_POST['passport_number']);
        $passport_issued_by = trim($_POST['passport_issued_by']);
        $passport_issue_date = $_POST['passport_issue_date'];
        $snils = preg_replace('/[^\d]/', '', $_POST['snils']);
        $oms_number = preg_replace('/\D/', '', $_POST['oms_number']);
        $oms_insurance_company = trim($_POST['oms_insurance_company']); 

        // Проверка формата данных (ДОБАВЛЕНА ПРОВЕРКА ОМС)
        if (strlen($passport_series) !== 4) {
            echo json_encode(['success' => false, 'error' => 'Неверный формат серии паспорта']);
            exit;
        }
        if (strlen($passport_number) !== 6) {
            echo json_encode(['success' => false, 'error' => 'Неверный формат номера паспорта']);
            exit;
        }
        if (strlen($snils) !== 11) {
            echo json_encode(['success' => false, 'error' => 'Неверный формат СНИЛС']);
            exit;
        }
        if (strlen($oms_number) !== 16) {
            echo json_encode(['success' => false, 'error' => 'Неверный формат номера ОМС']);
            exit;
        }

        // Форматируем СНИЛС для хранения
        $snils = substr($snils, 0, 3) . '-' . 
                 substr($snils, 3, 3) . '-' . 
                 substr($snils, 6, 3) . ' ' . 
                 substr($snils, 9, 2);

        // Проверяем существование записи
        $stmt = $db->prepare("SELECT id FROM personal_documents WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Обновляем существующую запись 
            $stmt = $db->prepare("
                UPDATE personal_documents 
                SET passport_series = ?,
                    passport_number = ?,
                    passport_issued_by = ?,
                    passport_issue_date = ?,
                    snils = ?,
                    oms_number = ?,         
                    oms_insurance_company = ? 
                WHERE user_id = ?
            ");
        } else {
            // Создаем новую запись 
            $stmt = $db->prepare("
                INSERT INTO personal_documents 
                (passport_series, passport_number, passport_issued_by, 
                 passport_issue_date, snils, oms_number, oms_insurance_company, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
        }

        $params = $existing 
            ? [
                $passport_series,
                $passport_number,
                $passport_issued_by,
                $passport_issue_date,
                $snils,
                $oms_number,               
                $oms_insurance_company,   
                $user['id']
              ]
            : [
                $passport_series,
                $passport_number,
                $passport_issued_by,
                $passport_issue_date,
                $snils,
                $oms_number,              
                $oms_insurance_company,    
                $user['id']
              ];

        $result = $stmt->execute($params);

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'error' => 'Указанные документы уже зарегистрированы в системе']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка сохранения данных: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Неверный метод запроса']);
}
?>
