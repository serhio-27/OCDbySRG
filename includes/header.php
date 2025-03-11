<?php
// Проверяем, не была ли сессия уже запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Подключаем базу данных, если она еще не подключена
if (!isset($db)) {
    include_once __DIR__ . '/../api/db.php';
}
?>

<header class="header">
    <div class="container">
        <div class="header__wrapper">
            <div class="logo">
                <a href="index.php">
                    <img src="img/logo2.png" alt="Логотип ЦРБ">
                </a>
            </div>
            <nav class="main-nav">
                <ul class="main-nav__list">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="departments.php">Отделения</a></li>
                    <li><a href="doctors.php">Врачи</a></li>
                    <li><a href="contacts.php">Контакты</a></li>
                </ul>
            </nav>
            <button class="btn btn--accessibility" @click="openAccessibilitySettings">
                <i class="fas fa-universal-access"></i> Версия для слабовидящих
            </button>
            <div class="auth-buttons">
                <?php if (isset($_SESSION['token'])): 
                    // Определяем тип пользователя
                    $stmt = $db->prepare("SELECT type FROM users WHERE api_token = ?");
                    $stmt->execute([$_SESSION['token']]);
                    $userType = $stmt->fetchColumn();
                    
                    // Формируем ссылку в зависимости от типа пользователя
                    $cabinetLink = match($userType) {
                        'doctor' => 'doctor.php',
                        'admin' => 'admin.php',
                        default => 'user.php'
                    };
                ?>
                    <a href="<?= $cabinetLink ?>" class="btn btn--primary">
                        <i class="fas fa-user"></i> Личный кабинет
                    </a>
                    <a href="api/logout.php" class="btn btn--secondary">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn--primary">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                    <a href="register.php" class="btn btn--secondary">
                        <i class="fas fa-user-plus"></i> Регистрация
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header> 