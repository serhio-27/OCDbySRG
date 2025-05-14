<?php 
session_start();
include_once './db.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Логируем входные данные (только для отладки)
    error_log('Login attempt - Email: ' . $_POST['email']);
    
    // Очищаем данные от лишних пробелов
    $formData = array_map('trim', $_POST);
    
    $fields = [
        'email',
        'password'
    ];
    $errors = [];

    // Защита от XSS
    foreach($formData as $key => $value){
        $formData[$key] = htmlspecialchars($value);
    }
  
    // Базовая проверка на заполненность
    foreach ($fields as $field) {
        if (!isset($formData[$field]) || empty($formData[$field])) {
            $errors[$field][] = 'Поле обязательно для заполнения';
        }
    }

    if (empty($errors)) {
        try {
            // Получаем пользователя по email
            $stmt = $db->prepare("
                SELECT id, email, password, type 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$formData['email']]);
            $user = $stmt->fetch();

            // Логируем результат поиска пользователя
            error_log('User found: ' . ($user ? 'Yes' : 'No'));
            if ($user) {
                error_log('User type: ' . $user['type']);
            }

            if (!$user) {
                $errors['email'][] = 'Пользователь не найден';
            } else {
                // Проверяем пароль
                $password_verify_result = password_verify($formData['password'], $user['password']);
                error_log('Password verification result: ' . ($password_verify_result ? 'Success' : 'Failed'));
                
                if ($password_verify_result) {
                    // Генерируем токен
                    $token = bin2hex(random_bytes(32));
                    
                    // Обновляем токен в базе
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET api_token = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$token, $user['id']]);

                    // Сохраняем данные в сессию
                    $_SESSION['token'] = $token;
                    $_SESSION['user_type'] = $user['type'];

                    error_log('Authentication successful. Redirecting to: ' . $user['type']);

                    // Перенаправляем в зависимости от типа пользователя
                    switch ($user['type']) {
                        case 'doctor':
                            header('Location: ../doctor.php');
                            break;
                        case 'admin':
                            header('Location: ../admin.php');
                            break;
                        case 'patient':
                        default:
                            header('Location: ../user.php');
                            break;
                    }
                    exit;
                } else {
                    $errors['password'][] = 'Неверный пароль';
                }
            }
        } catch (Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            $errors['system'][] = 'Ошибка авторизации';
        }
    }

    // Если есть ошибки
    if (!empty($errors)) {
        $_SESSION['login-errors'] = $errors;
        error_log('Login errors: ' . print_r($errors, true));
        header('Location: ../login.php');
        exit;
    }
}
?> 