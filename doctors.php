<?php
session_start();
include_once('api/db.php');

// Получаем параметр поиска
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Формируем SQL запрос с поиском
$sql = "
    SELECT id, name, surname, patronymic, specialization 
    FROM users 
    WHERE type = 'doctor'
";

if (!empty($search)) {
    $sql .= " AND (
        CONCAT(surname, ' ', name, ' ', COALESCE(patronymic, '')) LIKE ? 
        OR specialization LIKE ?
    )";
}

$sql .= " ORDER BY surname, name";

$stmt = $db->prepare($sql);

// Если есть поисковый запрос, добавляем параметры
if (!empty($search)) {
    $searchParam = "%{$search}%";
    $stmt->execute([$searchParam, $searchParam]);
} else {
    $stmt->execute();
}

$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Наши врачи | Карасукская ЦРБ</title>
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/doctors.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
     <!-- Шапка сайта -->
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
                        <li><a href="contacts.php">Контакты</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <a href="api/logout.php" class="btn btn--secondary">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Основной контент -->
    <main class="container">
        <section class="doctors-section">
            <h1>Наши врачи</h1>
            
            <!-- Форма поиска -->
            <form class="search-form" action="" method="GET">
                <div class="search-wrapper">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Поиск по ФИО или специализации"
                           class="search-input">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Список врачей -->
            <div class="doctors-grid">
                <?php if (empty($doctors)): ?>
                    <div class="no-results">
                        <p>По вашему запросу ничего не найдено</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <div class="doctor-photo">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="doctor-info">
                                <h3>
                                    <a href="doctor.php?id=<?= $doctor['id'] ?>">
                                        <?= htmlspecialchars($doctor['surname'] . ' ' . $doctor['name'] . ' ' . $doctor['patronymic']) ?>
                                    </a>
                                </h3>
                                <p class="specialization"><?= htmlspecialchars($doctor['specialization']) ?></p>
                                <?php if (isset($_SESSION['token'])): ?>
                                    <a href="appointment.php?doctor_id=<?= $doctor['id'] ?>" class="btn btn--primary">Записаться на приём</a>
                                <?php else: ?>
                                    <div class="auth-prompt">
                                        <p>Для записи на приём необходимо 
                                           <a href="login.php">войти</a> или 
                                           <a href="register.php">зарегистрироваться</a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Подвал -->
    <?php include 'includes/footer.php'; ?>
</body>
</html> 