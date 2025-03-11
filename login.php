<?php 
session_start();
include_once('api/db.php');

if(array_key_exists('token', $_SESSION)){
    $token = $_SESSION['token'];
    $stmt = $db->prepare("SELECT id, type FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if($user){
        // Перенаправляем в зависимости от типа пользователя
        switch($user['type']) {
            case 'doctor':
                header('Location: doctor.php');
                break;
            case 'admin':
                header('Location: admin.php');
                break;
            case 'patient':
            default:
                header('Location: user.php');
                break;
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему | ЦРБ Карасук Онлайн</title>
    <link rel="stylesheet" href="styles/pages/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Вход в личный кабинет</h1>
            <p>Введите ваши учетные данные</p>
        </div>

        <form method="POST" action="api/authUser.php" class="login-form">
            <div class="form-group">
                <label for="email">Электронная почта</label>
                <input type="email" name="email" id="email" required>
                <?php 
                if(isset($_SESSION['login-errors']['email'])) {
                    echo "<span class='error'>" . implode(', ', $_SESSION['login-errors']['email']) . "</span>";
                }
                ?>
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" name="password" id="password" required>
                <?php 
                if(isset($_SESSION['login-errors']['password'])) {
                    echo "<span class='error'>" . implode(', ', $_SESSION['login-errors']['password']) . "</span>";
                }
                ?>
            </div>

            <div class="remember-me">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Запомнить меня</label>
            </div>

            <button type="submit" class="btn">Войти</button>

            <div class="links">
                <a href="forgot-password.php">Забыли пароль?</a>
                <a href="register.php">Регистрация</a>
                <a href="index.php">На главную</a>
            </div>
        </form>
    </div>

    <?php 
    // Очищаем ошибки после отображения
    if(isset($_SESSION['login-errors'])) {
        unset($_SESSION['login-errors']);
    }
    ?>
</body>
</html>