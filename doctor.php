<?php
session_start();
include_once('api/db.php');

// Получаем ID врача из URL
$doctorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Проверка авторизации
if (!isset($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

// Получаем данные врача
$token = $_SESSION['token'];
$stmt = $db->prepare("SELECT * FROM users WHERE api_token = ? AND type = 'doctor'");
$stmt->execute([$token]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: login.php');
    exit;
}

// Получаем документы врача
$stmt = $db->prepare("
    SELECT * FROM personal_documents 
    WHERE user_id = ?
");
$stmt->execute([$doctor['id']]);
$documents = $stmt->fetch();

// Получаем текущие и запланированные приёмы
$stmt = $db->prepare("
    SELECT a.*, p.surname as patient_surname, p.name as patient_name, p.phone as patient_phone
    FROM appointments a 
    JOIN users p ON a.patient_id = p.id 
    WHERE a.doctor_id = ? AND a.status IN ('pending', 'confirmed') AND p.type = 'patient'
    ORDER BY a.appointment_date ASC
");
$stmt->execute([$doctor['id']]);
$activeAppointments = $stmt->fetchAll();

// Получаем историю приёмов
$stmt = $db->prepare("
    SELECT a.*, p.surname as patient_surname, p.name as patient_name, p.phone as patient_phone
    FROM appointments a 
    JOIN users p ON a.patient_id = p.id 
    WHERE a.doctor_id = ? AND a.status = 'completed' AND p.type = 'patient'
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$doctor['id']]);
$completedAppointments = $stmt->fetchAll();

// Временно раскомментируйте для отладки
// var_dump($activeAppointments);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет врача | ЦРБ Карасук Онлайн</title>
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/doctor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/consultation.css">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
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
        <div class="profile-grid">
            <!-- Левая колонка - Личные данные -->
            <div class="profile-section">
                <h2>Личные данные</h2>
                <form action="api/updateDoctorProfile.php" method="POST" class="profile-form">
                    <div class="form-group">
                        <label>Фамилия</label>
                        <input type="text" name="surname" value="<?= htmlspecialchars($doctor['surname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Отчество</label>
                        <input type="text" name="patronymic" value="<?= htmlspecialchars($doctor['patronymic']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Должность</label>
                        <input type="text" name="specialization" value="<?= htmlspecialchars($doctor['specialization']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn--primary">Сохранить изменения</button>
                </form>
            </div>

            <!-- Правая колонка -->
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
                    </div>
                    <button type="submit" class="btn btn--primary">Сохранить документы</button>
                </form>
            </div>

            <!-- Приёмы -->
            <div class="profile-section">
                <h2>Текущие и запланированные приёмы</h2>
                <div class="appointments-list">
                    <?php if (empty($activeAppointments)): ?>
                        <p class="no-data">Нет запланированных приёмов</p>
                    <?php else: ?>
                        <?php foreach ($activeAppointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-info">
                                    <div class="date-time">
                                        <span class="date"><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></span>
                                        <span class="time"><?= date('H:i', strtotime($appointment['appointment_date'])) ?></span>
                                    </div>
                                    <div class="patient-info">
                                        <h3>Пациент:</h3>
                                        <p><?= htmlspecialchars($appointment['patient_surname'] . ' ' . $appointment['patient_name']) ?></p>
                                        <p>Телефон: <?= htmlspecialchars($appointment['patient_phone']) ?></p>
                                    </div>
                                    <div class="complaints">
                                        <h3>Жалобы:</h3>
                                        <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                    </div>
                                    <div class="status">
                                        <span class="badge <?= $appointment['status'] === 'confirmed' ? 'confirmed' : 'pending' ?>">
                                            <?= $appointment['status'] === 'confirmed' ? 'Подтверждён' : 'Ожидает подтверждения' ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                    <div class="appointment-actions">
                                        <button class="btn btn--primary" onclick="startAppointment(<?= $appointment['id'] ?>)">
                                            Начать приём
                                        </button>
                                        <button class="btn btn--secondary" onclick="completeAppointment(<?= $appointment['id'] ?>)">
                                            Завершить приём
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- История приёмов -->
            <div class="profile-section">
                <h2>История приёмов</h2>
                <div class="appointments-list history">
                    <?php if (empty($completedAppointments)): ?>
                        <p class="no-data">История приёмов пуста</p>
                    <?php else: ?>
                        <?php foreach ($completedAppointments as $appointment): ?>
                            <div class="appointment-card completed">
                                <div class="appointment-info">
                                    <div class="date-time">
                                        <span class="date"><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></span>
                                        <span class="time"><?= date('H:i', strtotime($appointment['appointment_date'])) ?></span>
                                    </div>
                                    <div class="patient-info">
                                        <h3>Пациент:</h3>
                                        <p><?= htmlspecialchars($appointment['patient_surname'] . ' ' . $appointment['patient_name']) ?></p>
                                        <p>Телефон: <?= htmlspecialchars($appointment['patient_phone']) ?></p>
                                    </div>
                                    <div class="complaints">
                                        <h3>Жалобы:</h3>
                                        <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                    </div>
                                    <div class="status">
                                        <span class="badge completed">Завершён</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Подвал -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Добавляем в секцию скриптов
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

        // Маска для СНИЛС
        document.querySelector('input[name="snils"]').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,3})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '') + (x[4] ? ' ' + x[4] : '');
        });

        // Маска для серии паспорта
        document.querySelector('input[name="passport_series"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substr(0, 4);
        });

        // Маска для номера паспорта
        document.querySelector('input[name="passport_number"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substr(0, 6);
        });

        // Функции для работы с приёмами
        function startAppointment(appointmentId) {
            const modal = document.getElementById('consultationModal');
            modal.style.display = 'block';
            consultationApp.appointmentId = appointmentId;
            consultationApp.loadMessages();
        }

        function completeAppointment(appointmentId) {
            if (confirm('Завершить приём?')) {
                fetch('api/updateAppointmentStatus.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        status: 'completed'
                    })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          location.reload();
                      } else {
                          alert('Ошибка при завершении приёма');
                      }
                  });
            }
        }

        window.currentUserId = <?= (int)$doctor['id'] ?>;
    </script>

    <!-- Модальное окно для консультации (добавить перед закрывающим тегом body) -->
    <div id="consultationModal" class="modal">
        <div class="modal-content consultation-modal">
            <span class="close" onclick="closeConsultation()">&times;</span>
            <div id="consultationApp">
                <div class="consultation-header">
                    <h2>Консультация</h2>
                    <div v-if="patientInfo" class="patient-info">
                        <p>Пациент: {{ patientInfo.surname }} {{ patientInfo.name }} {{ patientInfo.patronymic }}</p>
                        <p>Жалобы: {{ patientInfo.complaint }}</p>
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
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                    <button @click="triggerFileUpload" 
                            class="btn btn-attach" 
                            title="Прикрепить файл">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button @click="sendMessage" 
                            :disabled="!newMessage.trim() && !selectedFile"
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
    <script src="js/consultation.js"></script>
</body>
</html>
