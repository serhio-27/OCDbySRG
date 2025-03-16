<?php session_start();
include_once('api/db.php');
function isAuthenticated() {
    return false;
}
if(array_key_exists('token', $_SESSION)){
    $token=$_SESSION['token'];
    $userId = $db->query("SELECT id FROM users WHERE api_token= '$token'")->fetchAll();
    if(!empty($userId)){
        header('Location: user.php');
    }
}

function showError($field){     
    if(!array_key_exists('register-errors', $_SESSION)){
        echo '';
    } else {
        $listErrors = $_SESSION['register-errors']; 
        if (array_key_exists($field, $listErrors)){  
            $error = implode(',', $listErrors[$field]); 
            echo "<span class='error'>$error</span>";
        }    
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | Карасукская ЦРБ</title>
    
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="lib/scss/font-awesome.scss">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/reg.css">
</head>
<body>
    <!-- Шапка сайта -->
    <header class="header">
        <div class="container">
            <div class="header__wrapper">
                <a href="/" class="logo">
                    <img src="img/logo2.png" alt="ЦРБ Карасук онлайн">
                </a>
                
                <nav class="main-nav">
                    <ul class="main-nav__list">
                        <li><a href="/specialists">Врачи</a></li>
                        <li><a href="/services">Услуги</a></li>
                        <li><a href="/prices">Прайс-листы</a></li>
                        <li><a href="/about">О нас</a></li>
                    </ul>
                </nav>

                <div class="user-actions">
                    <a href="/login" class="btn btn--primary">Войти</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="registration-container">
            <div class="registration-header">
                <h1>Регистрация</h1>
                <p>Пожалуйста, заполните все обязательные поля</p>
            </div>

            <form method="POST" action="api/registrationUser.php" class="register-form">
                <div class="user-type-selector">
                    <label>
                        <input type="radio" name="user_type" value="patient" checked onchange="toggleSpecialization()"> Пациент
                    </label>
                    <label>
                        <input type="radio" name="user_type" value="doctor" onchange="toggleSpecialization()"> Врач
                    </label>
                </div>

                <!-- Поле специализации (изначально скрыто) -->
                <div class="form-group" id="specialization-group" style="display: none;">
                    <label for="specialization">Специализация *</label>
                    <select name="specialization" id="specialization">
                        <option value="">Выберите специализацию</option>
                        <option value="Терапевт">Терапевт</option>
                        <option value="Кардиолог">Кардиолог</option>
                        <option value="Невролог">Невролог</option>
                        <option value="Хирург">Хирург</option>
                        <option value="Педиатр">Педиатр</option>
                        <option value="Офтальмолог">Офтальмолог</option>
                        <option value="Стоматолог">Стоматолог</option>
                        <option value="Эндокринолог">Эндокринолог</option>
                        <option value="Гинеколог">Гинеколог</option>
                        <option value="Дерматолог">Дерматолог</option>
                    </select>
                    <?php showError('specialization') ?>
                </div>

                <div class="form-group">
                    <label for="surname">Фамилия *</label>
                    <input type="text" name="surname" id="surname" required>
                    <?php showError('surname') ?>
                </div>

                <div class="form-group">
                    <label for="name">Имя *</label>
                    <input type="text" name="name" id="name" required>
                    
                </div>

                <div class="form-group">
                    <label for="patronymic">Отчество</label>
                    <input type="text" name="patronymic" id="patronymic">
                </div>

                <div class="form-group">
                    <label for="email">Электронная почта *</label>
                    <input type="email" name="email" id="email" required>
                  
                </div>

                <div class="form-group">
                    <label for="phone">Номер телефона *</label>
                    <input type="tel" name="phone" id="phone" pattern="\+7\s?[\(]{0,1}9[0-9]{2}[\)]{0,1}\s?\d{3}[-]{0,1}\d{2}[-]{0,1}\d{2}" placeholder="+7 (9XX) XXX-XX-XX" required>
                   
                </div>

                <div class="form-group">
                    <label for="password">Пароль *</label>
                    <input type="password" name="password" id="password" required>
                  
                </div>

                <div class="form-group">
                    <label for="password_confirm">Подтверждение пароля *</label>
                    <input type="password" name="password_confirm" id="password_confirm" required>
                   
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="agree" id="agree" required>
                    <label for="agree">Я даю согласие на обработку персональных данных в соответствии с ФЗ №152 от 27.07.2006</label>
                   
                </div>

                <button type="submit" class="btn btn--primary">Зарегистрироваться</button>

                <div class="links">
                    <a href="login.php">Войти в систему</a>
                    <a href="index.php">На главную</a>
                </div>
            </form>
        </div>
    </main>

    <!-- Подвал -->
    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <div class="footer__column">
                    <h4>О ЦРБ</h4>
                    <ul>
                        <li><a href="/about">О нас</a></li>
                        <li><a href="/doctors">Врачи</a></li>
                        <li><a href="/reviews">Отзывы</a></li>
                        <li><a href="/contacts">Контакты</a></li>
                    </ul>
                </div>
                <div class="footer__column">
                    <h4>Пациентам</h4>
                    <ul>
                        <li><a href="/how-it-works">Как это работает</a></li>
                        <li><a href="/faq">Частые вопросы</a></li>
                        <li><a href="/blog">Блог о здоровье</a></li>
                    </ul>
                </div>
                <div class="footer__column">
                    <h4>Документы</h4>
                    <ul>
                        <li><a href="/privacy">Политика конфиденциальности</a></li>
                        <li><a href="/terms">Пользовательское соглашение</a></li>
                        <li><a href="/license">Лицензии</a></li>
                    </ul>
                </div>
                <div class="footer__column">
                    <h4>Контакты</h4>
                    <ul>
                        <li><a href="tel:+79001234567">+7 (900) 123-45-67</a></li>
                        <li><a href="mailto:CRBKarasukOnline@mail.ru">Email: CRBKarasukOnline@mail.ru</a></li>
                        <li>
                            <div class="social-links">
                                <a href="#" class="social-link"><i class="fab fa-vk"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                                <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer__bottom">
                <p>&copy; <?php echo date('Y'); ?> Карасукская ЦРБ. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        // Маска для телефона
        document.getElementById('phone').addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+7 (' + x[2] + (x[3] ? ') ' + x[3] : '') + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });

        function toggleSpecialization() {
            const userType = document.querySelector('input[name="user_type"]:checked').value;
            const specializationGroup = document.getElementById('specialization-group');
            const specializationSelect = document.getElementById('specialization');
            
            if (userType === 'doctor') {
                specializationGroup.style.display = 'block';
                specializationSelect.required = true;
            } else {
                specializationGroup.style.display = 'none';
                specializationSelect.required = false;
                specializationSelect.value = ''; // Очищаем значение при переключении на пациента
            }
        }

        // Вызываем функцию при загрузке страницы для установки начального состояния
        document.addEventListener('DOMContentLoaded', toggleSpecialization);
    </script>
</body>
</html>