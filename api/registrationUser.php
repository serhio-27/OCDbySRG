<?php 
session_start();
include_once './db.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $formData = array_map('trim', $_POST); // Очищаем данные от лишних пробелов
    $fields = [
        'name',
        'surname',
        'email',
        'phone',
        'password',
        'password_confirm',
        'agree'
    ];
    $errors = [];

    foreach($formData as $key => $value){
        $formData[$key] = htmlspecialchars($value);
    }
 
    // Базовая проверка на заполненность
    foreach ($fields as $field) {
        if (!isset($formData[$field]) || empty($formData[$field])) {
            $errors[$field][] = 'Заполните это поле';
        }
    }

    // Проверка паролей
    if ($formData['password'] !== $formData['password_confirm']) {
        $errors['password_confirm'][] = 'Пароли не совпадают';
    }

    // Проверка уникальности email и телефона
    $stmt = $db->prepare("SELECT phone, email FROM users WHERE phone = ? OR email = ?");
    $stmt->execute([$formData['phone'], $formData['email']]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['phone'] == $formData['phone']) {
            $errors['phone'][] = 'Этот номер телефона уже зарегистрирован';
        }
        if ($user['email'] == $formData['email']) {
            $errors['email'][] = 'Этот email уже зарегистрирован';
        }
    }

    // Если нет ошибок, регистрируем пользователя
    if (empty($errors)) {
        try {
            // Определяем тип пользователя
            $userType = isset($formData['user_type']) && $formData['user_type'] === 'doctor' ? 'doctor' : 'patient';
            
            $stmt = $db->prepare("
                INSERT INTO users (
                    name,
                    surname,
                    email,
                    phone,
                    password,
                    agree,
                    type,
                    specialization
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $formData['name'],
                $formData['surname'],
                $formData['email'],
                $formData['phone'],
                password_hash($formData['password'], PASSWORD_DEFAULT),
                $formData['agree'] ? 1 : 0,
                $userType,
                ($userType === 'doctor' && isset($formData['specialization'])) ? $formData['specialization'] : null
            ]);

            if ($result) {
                $_SESSION['register-success'] = true;
                header('Location: ../login.php');
                exit;
            } else {
                throw new Exception('Ошибка при выполнении запроса');
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors['system'][] = 'Ошибка при регистрации. Попробуйте позже.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['register-errors'] = $errors;
        header('Location: ../register.php');
        exit;
    }
}

// Добавим отладочную информацию
error_log('POST data: ' . print_r($_POST, true));
?> 