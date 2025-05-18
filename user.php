<?php
session_start();
include_once('api/db.php');

// Проверка авторизации
if (!isset($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя
$token = $_SESSION['token'];
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ? AND type = 'patient'");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

// Получаем документы пользователя
$stmt = $db->prepare("
    SELECT * FROM personal_documents 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$documents = $stmt->fetch();

// Получаем список врачей
$stmt = $db->query("
    SELECT id, name, surname, patronymic, specialization 
    FROM users 
    WHERE type = 'doctor'
    ORDER BY specialization, surname
");
$doctors = $stmt->fetchAll();

// Получаем историю приёмов
$stmt = $db->prepare("
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.complaint,
        d.surname as doctor_surname,
        d.name as doctor_name,
        d.patronymic as doctor_patronymic,
        d.specialization
    FROM appointments a
    JOIN users d ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$user['id']]);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | ЦРБ Карасук Онлайн</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/modal.css">
    <link rel="stylesheet" href="styles/consultation.css">
    <link rel="stylesheet" href="styles/accessibility.css">
    <style>
        .modal-content {
            max-width: 600px;
            width: 90%;
            padding: 20px;
        }
        
        .doctor-info {
            margin-bottom: 20px;
        }
        
        .appointment-form {
            margin-top: 20px;
        }
        
        .complaint-section {
            margin-top: 20px;
        }
        
        .complaint-section textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        /* Стили для уведомления */
        .appointment-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            z-index: 1000;
            display: none;
            max-width: 300px;
            border-left: 4px solid #4CAF50;
            animation: slideIn 0.5s ease-out;
        }

        .appointment-notification.show {
            display: block;
        }

        .appointment-notification h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .appointment-notification p {
            margin: 5px 0;
            color: #666;
        }

        .appointment-notification .close-notification {
            position: absolute;
            top: 5px;
            right: 10px;
            cursor: pointer;
            color: #999;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
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
                        <li><a href="doctors.php">Врачи</a></li>
                        <li><a href="departments.php">Отделения</a></li>
                        <li><a href="services.php">Услуги</a></li>
                        <li><a href="contacts.php">Контакты</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <button class="btn btn--accessibility" type="button">
                        <i class="fa-solid fa-universal-access"></i> Версия для слабовидящих
                    </button>
                    <a href="api/logout.php" class="btn btn--secondary">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
            </div>
        </div>
    </header>


    <!-- Основной контент -->
    <main class="container">
        <div class="profile-grid">
            <!-- Левая колонка - Личные данные -->
            <div class="profile-section">
                <h2>Личные данные</h2>
                <form action="api/updateProfile.php" method="POST" class="profile-form">
                    <div class="form-group">
                        <label>Фамилия</label>
                        <input type="text" name="surname" value="<?= htmlspecialchars($user['surname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Отчество</label>
                        <input type="text" name="patronymic" value="<?= htmlspecialchars($user['patronymic']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn--primary">Сохранить изменения</button>
                </form>
            </div>

   <!-- Правая колонка - Список врачей -->
<div class="profile-section">
    <h2>Доступные врачи</h2>
    <div class="doctors-list scrollable">
        <?php foreach ($doctors as $doctor): ?>
        <div class="doctor-card">
            <h3><?= htmlspecialchars($doctor['surname'] . ' ' . $doctor['name'] . ' ' . $doctor['patronymic']) ?></h3>
            <p class="specialization"><?= htmlspecialchars($doctor['specialization']) ?></p>
            <button class="btn btn--secondary" 
                    onclick="showDoctorInfo(<?= $doctor['id'] ?>)">
                Подробнее
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

            <!-- Текущие и запланированные приёмы -->
            <div class="appointments-section">
                <h2>Текущие и запланированные приёмы</h2>
                <div class="appointments-list">
                    <?php foreach ($appointments as $appointment): ?>
                        <?php if ($appointment['status'] !== 'completed'): ?>
                            <div class="appointment-card">
                                <div class="appointment-info">
                                    <div class="date-time">
                                        <span class="date"><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></span>
                                        <span class="time"><?= date('H:i', strtotime($appointment['appointment_time'])) ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <div class="patient-info">
                                            <h3>Врач:</h3>
                                            <p><?= htmlspecialchars($appointment['doctor_surname'] . ' ' . $appointment['doctor_name']) ?></p>
                                            <p class="specialization"><?= htmlspecialchars($appointment['specialization']) ?></p>
                                        </div>
                                        <?php if (!empty($appointment['complaint'])): ?>
                                            <div class="complaints">
                                                <h3>Жалобы:</h3>
                                                <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="status">
                                            <span class="badge <?= $appointment['status'] === 'confirmed' ? 'confirmed' : 'pending' ?>">
                                                <?= $appointment['status'] === 'confirmed' ? 'Подтверждён' : 'Ожидает подтверждения' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                    <div class="appointment-actions">
                                        <button class="btn btn--primary" onclick="startConsultation(<?= $appointment['id'] ?>)">
                                            Открыть чат
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- История приёмов -->
            <div class="appointments-section">
                <h2>История приёмов</h2>
                <div class="appointments-list history">
                    <?php foreach ($appointments as $appointment): ?>
                        <?php if ($appointment['status'] === 'completed'): ?>
                            <div class="appointment-card completed">
                                <div class="appointment-info">
                                    <div class="date-time">
                                        <span class="date"><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></span>
                                        <span class="time"><?= date('H:i', strtotime($appointment['appointment_time'])) ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <div class="patient-info">
                                            <h3>Врач:</h3>
                                            <p><?= htmlspecialchars($appointment['doctor_surname'] . ' ' . $appointment['doctor_name']) ?></p>
                                            <p class="specialization"><?= htmlspecialchars($appointment['specialization']) ?></p>
                                        </div>
                                        <?php if (!empty($appointment['complaint'])): ?>
                                            <div class="complaints">
                                                <h3>Жалобы:</h3>
                                                <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <div class="status">
                                            <span class="badge completed">Завершён</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- В секции личных данных добавляем: -->
            <div class="profile-section">
                <h2>Документы</h2>
                <form id="documentsForm" class="profile-form">
                    <div class="documents-grid">
                        <!-- Паспортные данные -->
                        <div class="document-section">
                            <h3>Паспортные данные</h3>
                            <div class="form-group">
                                <label>Серия паспорта</label>
                                <input type="text" name="passport_series" 
                                       value="<?= htmlspecialchars($documents['passport_series'] ?? '') ?>" 
                                       pattern="\d{4}" maxlength="4" placeholder="0000">
                            </div>
                            <div class="form-group">
                                <label>Номер паспорта</label>
                                <input type="text" name="passport_number" 
                                       value="<?= htmlspecialchars($documents['passport_number'] ?? '') ?>" 
                                       pattern="\d{6}" maxlength="6" placeholder="000000">
                            </div>
                            <div class="form-group">
                                <label>Кем выдан</label>
                                <input type="text" name="passport_issued_by" 
                                       value="<?= htmlspecialchars($documents['passport_issued_by'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Дата выдачи</label>
                                <input type="date" name="passport_issue_date" 
                                       value="<?= htmlspecialchars($documents['passport_issue_date'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- СНИЛС -->
                        <div class="document-section">
                            <h3>СНИЛС</h3>
                            <div class="form-group">
                                <label>Номер СНИЛС</label>
                                <input type="text" name="snils" 
                                       value="<?= htmlspecialchars($documents['snils'] ?? '') ?>" 
                                       placeholder="000-000-000 00">
                            </div>
                        </div>
                         <!-- ОМС -->
            <div class="document-section">
                <h3>ОМС</h3>
                <div class="form-group">
                    <label>Номер полиса ОМС</label>
                    <input type="text" name="oms_number" 
                           value="<?= htmlspecialchars($documents['oms_number'] ?? '') ?>" 
                           placeholder="0000000000000000" maxlength="16">
                </div>
                <div class="form-group">
                    <label>Страховая компания</label>
                    <input type="text" name="oms_insurance_company" 
                           value="<?= htmlspecialchars($documents['oms_insurance_company'] ?? '') ?>" 
                           placeholder="Название компании">
                </div>
            </div>
        </div>
                    <button type="submit" class="btn btn--primary">Сохранить документы</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Модальное окно для консультации -->
    <div id="consultationModal" class="modal">
        <div class="modal-content consultation-modal">
            <span class="close" onclick="closeConsultation()">&times;</span>
            <div id="consultationApp">
                <div class="consultation-header">
                    <h2>Консультация</h2>
                    <div v-if="doctorInfo" class="doctor-info">
                        <p>Врач: {{ doctorInfo.surname }} {{ doctorInfo.name }} {{ doctorInfo.patronymic }}</p>
                        <p>Специализация: {{ doctorInfo.specialization }}</p>
                    </div>
                </div>
                
                <div class="chat-container" ref="chatContainer">
                    <div v-if="messages.length === 0" class="no-messages">
                        Начните консультацию
                    </div>
                    <div v-for="message in messages" :key="message.id" 
                         :class="['message', isOwnMessage(message) ? 'message-own' : 'message-other']">
                        <div class="message-header">
                            <span class="message-sender">{{ message.surname }} {{ message.name }}</span>
                            <span class="message-time">{{ formatTime(message.created_at) }}</span>
                        </div>
                        <div class="message-content">{{ message.message }}</div>
                        <!-- Отображение файла -->
                        <div v-if="message.file_path" class="message-file">
                            <!-- Для изображений -->
                            <img v-if="isImage(message.file_path)" 
                                 :src="message.file_path" 
                                 class="message-image"
                                 @click="openImage(message.file_path)"
                                 :alt="message.file_original_name">
                            <!-- Для других файлов -->
                            <a v-else 
                               :href="message.file_path" 
                               class="file-link" 
                               download>
                                <i :class="getFileIcon(message.file_type)"></i>
                                <span>{{ message.file_original_name || getFileName(message.file_path) }}</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input">
                    <textarea v-model="newMessage" 
                             @keyup.enter="sendMessage"
                             placeholder="Введите сообщение..."
                             rows="2"></textarea>
                    <input type="file" 
                           ref="fileInput" 
                           @change="handleFileUpload" 
                           style="display: none"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    <button @click="triggerFileUpload" 
                            class="btn btn-attach" 
                            title="Прикрепить файл">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button @click="sendMessage" 
                            :disabled="!canSendMessage"
                            class="btn btn--primary">
                        Отправить
                    </button>
                </div>

                <!-- Модальное окно для просмотра изображений -->
                <div v-if="showImageModal" class="image-modal" @click="closeImageModal">
                    <img :src="selectedImage" @click.stop>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для настроек доступности -->
    <div id="accessibilityModal" class="modal">
        <div class="modal-content">
            <div id="accessibilityApp">
                <span class="close" @click="closeModal" title="Закрыть">&times;</span>
                <h2>Настройки для слабовидящих</h2>
                <div class="form-group">
                    <label>Размер шрифта:</label>
                    <div class="font-size-options">
                        <button v-for="size in fontSizes" :key="size.value" :class="['font-size-btn', {selected: currentSettings.fontSize === size.value}]" @click="setFontSize(size.value)">
                            {{ size.preview }}
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Межбуквенный интервал:</label>
                    <div class="letter-spacing-options">
                        <button v-for="spacing in letterSpacings" :key="spacing.value" :class="['letter-spacing-btn', {selected: currentSettings.letterSpacing === spacing.value}]" @click="setLetterSpacing(spacing.value)">
                            {{ spacing.label }}
                        </button>
                    </div>
                </div>
                <div class="accessibility-actions">
                    <button class="btn btn--primary" @click="applySettings">Применить</button>
                    <button class="btn btn--secondary" @click="resetSettings">Сбросить</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Маска для СНИЛС
    document.querySelector('input[name="snils"]').addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,3})(\d{0,2})/);
        e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '') + (x[4] ? ' ' + x[4] : '');
    });

    document.getElementById('documentsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);

        fetch('api/updateDocuments.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Документы успешно сохранены');
            } else {
                alert(data.error || 'Ошибка сохранения документов');
            }
        })
        .catch(error => {
            alert('Произошла ошибка при сохранении документов');
            console.error(error);
        });
    });

    // Маска для серии паспорта
    document.querySelector('input[name="passport_series"]').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '').substr(0, 4);
    });

    // Маска для номера паспорта
    document.querySelector('input[name="passport_number"]').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '').substr(0, 6);
    });
    </script>

    <script>
        window.currentUserId = <?= (int)$user['id'] ?>;
    </script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="js/modal.js" defer></script>
    <script src="js/patient-consultation.js" defer></script>
    <script src="js/accessibility.js" defer></script>
    <script src="js/appointment-notification.js" defer></script>

    <script>
        // Передаем данные о приёмах в глобальную переменную для использования в appointment-notification.js
        window.appointments = <?= json_encode($appointments) ?>;
    </script>

    <!-- Уведомление о предстоящем приёме -->
    <div id="appointmentNotification" class="appointment-notification">
        <span class="close-notification">&times;</span>
        <h4>Напоминание о приёме</h4>
        <p id="notificationDoctor"></p>
        <p id="notificationTime"></p>
    </div>

    <!-- Модальное окно для записи к врачу -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div v-if="doctor" class="doctor-info">
                <h2>Запись на приём</h2>
                <h3>{{ doctor.surname }} {{ doctor.name }} {{ doctor.patronymic }}</h3>
                <p class="specialization">{{ doctor.specialization }}</p>
            </div>
            
            <div v-if="doctor" class="calendar-section">
                <div class="calendar-header">
                    <button @click="prevMonth" class="btn btn--secondary">&lt;</button>
                    <h3>{{ currentMonthYear }}</h3>
                    <button @click="nextMonth" class="btn btn--secondary">&gt;</button>
                </div>
                
                <div class="calendar">
                    <div class="weekdays">
                        <div v-for="day in daysOfWeek" :key="day" class="weekday">{{ day }}</div>
                    </div>
                    <div class="days">
                        <div v-for="day in calendarDays" 
                             :key="day.date"
                             :class="['day', {
                                 'selected': day.date === selectedDate,
                                 'available': isDateAvailable(day.date),
                                 'unavailable': !isDateAvailable(day.date)
                             }]"
                             @click="selectDate(day.date)">
                            {{ day.dayOfMonth }}
                        </div>
                    </div>
                </div>
                
                <div v-if="selectedDate" class="time-slots">
                    <h4>Доступное время:</h4>
                    <div class="time-slots-grid">
                        <button v-for="time in availableTimeSlots"
                                :key="time"
                                :class="['time-slot', { selected: time === selectedTime }]"
                                @click="selectTime(time)">
                            {{ time }}
                        </button>
                    </div>
                </div>
                
                <div v-if="selectedTime" class="complaint-section">
                    <h4>Опишите жалобы:</h4>
                    <textarea v-model="complaint" 
                             rows="4" 
                             placeholder="Опишите ваши жалобы..."></textarea>
                </div>
                
                <button v-if="selectedTime" 
                        class="btn btn--primary submit-btn" 
                        @click="submitAppointment"
                        :disabled="!canSubmit">
                    Записаться на приём
                </button>
            </div>
        </div>
    </div>
</body>
</html>
