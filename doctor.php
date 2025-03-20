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
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.complaint,
        p.surname as patient_surname,
        p.name as patient_name,
        p.patronymic as patient_patronymic,
        p.phone as patient_phone,
        CASE 
            WHEN a.appointment_date > CURRENT_DATE THEN 'future'
            WHEN a.appointment_date = CURRENT_DATE AND a.appointment_time >= CURRENT_TIME THEN 'today'
            ELSE 'past'
        END as time_status
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    WHERE a.doctor_id = ? 
    AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute([$doctor['id']]);
$upcoming_appointments = $stmt->fetchAll();

// Получаем историю приёмов
$stmt = $db->prepare("
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.complaint,
        p.surname as patient_surname,
        p.name as patient_name,
        p.patronymic as patient_patronymic
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    WHERE a.doctor_id = ? 
    AND (
        a.status IN ('completed', 'cancelled')
        OR a.appointment_date < CURRENT_DATE
        OR (a.appointment_date = CURRENT_DATE AND a.appointment_time < CURRENT_TIME)
    )
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$doctor['id']]);
$past_appointments = $stmt->fetchAll();

// Временно раскомментируйте для отладки
// var_dump($upcoming_appointments);
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
                    <?php if (empty($upcoming_appointments)): ?>
                        <p class="no-data">Нет запланированных приёмов</p>
                    <?php else: ?>
                        <?php foreach ($upcoming_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <div class="appointment-date">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?>
                                        <i class="far fa-clock"></i>
                                        <?= date('H:i', strtotime($appointment['appointment_time'])) ?>
                                    </div>
                                    <span class="appointment-status status-<?= strtolower($appointment['status']) ?>">
                                        <?php
                                        $statusText = [
                                            'pending' => 'Ожидает подтверждения',
                                            'confirmed' => 'Подтверждён'
                                        ];
                                        echo $statusText[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="appointment-patient">
                                    <h4>Пациент:</h4>
                                    <p><?= htmlspecialchars($appointment['patient_surname'] . ' ' . 
                                                          $appointment['patient_name'] . ' ' . 
                                                          $appointment['patient_patronymic']) ?></p>
                                    <p class="patient-phone">
                                        <i class="fas fa-phone"></i>
                                        <?= htmlspecialchars($appointment['patient_phone']) ?>
                                    </p>
                                </div>
                                <?php if (!empty($appointment['complaint'])): ?>
                                    <div class="appointment-details">
                                        <div class="complaint">
                                            <h4>Жалобы:</h4>
                                            <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="appointment-actions">
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                        <button class="btn btn--primary" onclick="confirmAppointment(<?= $appointment['id'] ?>)">
                                            Подтвердить
                                        </button>
                                        <button class="btn btn--secondary" onclick="cancelAppointment(<?= $appointment['id'] ?>)">
                                            Отменить
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] === 'confirmed'): ?>
                                        <button class="btn btn--primary" onclick="startConsultation(<?= $appointment['id'] ?>)">
                                            Начать приём
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- История приёмов -->
            <div class="profile-section">
                <h2>История приёмов</h2>
                <div class="appointments-list">
                    <?php if (empty($past_appointments)): ?>
                        <p class="no-data">История приёмов пуста</p>
                    <?php else: ?>
                        <?php foreach ($past_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <div class="appointment-date">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?>
                                        <i class="far fa-clock"></i>
                                        <?= date('H:i', strtotime($appointment['appointment_time'])) ?>
                                    </div>
                                    <span class="appointment-status status-<?= strtolower($appointment['status']) ?>">
                                        <?php
                                        $statusText = [
                                            'completed' => 'Завершён',
                                            'cancelled' => 'Отменён'
                                        ];
                                        echo $statusText[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="appointment-patient">
                                    <h4>Пациент:</h4>
                                    <p><?= htmlspecialchars($appointment['patient_surname'] . ' ' . 
                                                          $appointment['patient_name'] . ' ' . 
                                                          $appointment['patient_patronymic']) ?></p>
                                </div>
                                <?php if (!empty($appointment['complaint'])): ?>
                                    <div class="appointment-details">
                                        <div class="complaint">
                                            <h4>Жалобы:</h4>
                                            <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
        function confirmAppointment(appointmentId) {
            if (confirm('Подтвердить приём?')) {
                fetch('api/updateAppointmentStatus.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        status: 'confirmed'
                    })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          location.reload();
                      } else {
                          alert('Ошибка при обновлении статуса');
                      }
                  });
            }
        }

        function cancelAppointment(appointmentId) {
            if (confirm('Отменить приём?')) {
                fetch('api/updateAppointmentStatus.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        status: 'cancelled'
                    })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          location.reload();
                      } else {
                          alert('Ошибка при отмене приёма');
                      }
                  });
            }
        }

        function startConsultation(appointmentId) {
            const modal = document.getElementById('consultationModal');
            modal.style.display = 'block';
            consultationApp.appointmentId = appointmentId;
            consultationApp.loadMessages();
        }
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
                         :class="['message', message.sender_id === currentUserId ? 'message-own' : 'message-other']">
                        <div class="message-header">
                            <span class="message-sender">{{ message.surname }} {{ message.name }}</span>
                            <span class="message-time">{{ formatTime(message.created_at) }}</span>
                        </div>
                        <div class="message-content">{{ message.message }}</div>
                    </div>
                </div>
                
                <div class="chat-input">
                    <textarea v-model="newMessage" 
                             @keyup.enter="sendMessage"
                             placeholder="Введите сообщение..."
                             rows="2"></textarea>
                    <button @click="sendMessage" 
                            :disabled="!newMessage.trim()"
                            class="btn btn--primary">
                        Отправить
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="js/consultation.js"></script>
</body>
</html>
