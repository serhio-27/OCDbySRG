<?php
// Инициализация сессии
session_start();
// Подключение к базе данных
include_once('api/db.php');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Контактная информация Карасукской ЦРБ">
    <title>Контакты - Карасукская ЦРБ</title>
    
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/index.css">
    <link rel="stylesheet" href="styles/pages/contacts.css"> <!-- Подключение стилей для страницы контактов -->
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <!-- Подключаем шапку -->
    <?php include 'includes/header.php'; ?>

    <main>
        <section class="contacts-section">
            <h1>Контактная информация</h1>
            <div class="contact-info">
                <h2>Адрес:</h2>
                <p>632862, г Карасук, ул Гагарина, дом 1, литера А</p>

                <h2>Контакты:</h2>
                <p>Телефон: +7 (38355) 33118</p>
                <p>Факс: +7 (38355) 33118</p>
                <p>Веб-сайт: <a href="http://krscrb.mznso.ru">http://krscrb.mznso.ru</a></p>
                <p>Электронная почта: <a href="mailto:karasukcrb@nso.ru">karasukcrb@nso.ru</a></p>
                <p>Телефон регистратуры: +7 (38355) 40-272</p>

                <h2>Реквизиты:</h2>
                <p>Государственное бюджетное учреждение здравоохранения Новосибирской области "Карасукская центральная районная больница"</p>
                <ul>
                    <li>ИНН: 5422100059</li>
                    <li>КПП: 542201001</li>
                    <li>ОКТМО: 25084946</li>
                    <li>ОКФС: 13</li>
                    <li>ОКОПФ: 21</li>
                    <li>ОКВЭД: 85.11.1</li>
                    <li>ОКПО: 01936100</li>
                    <li>ОКОГУ: 2300229</li>
                </ul>
            </div>
        </section>
    </main>

    <!-- Подключаем подвал -->
    <?php include 'includes/footer.php'; ?>
</body>
</html> 