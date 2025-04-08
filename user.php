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
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/modal.css">
    <link rel="stylesheet" href="styles/consultation.css">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
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
    <div class="doctors-list">
        <?php $visibleDoctors = array_slice($doctors, 0, 3); ?>
        <?php foreach ($visibleDoctors as $doctor): ?>
        <div class="doctor-card">
            <h3><?= htmlspecialchars($doctor['surname'] . ' ' . $doctor['name'] . ' ' . $doctor['patronymic']) ?></h3>
            <p class="specialization"><?= htmlspecialchars($doctor['specialization']) ?></p>
            <button class="btn btn--secondary" 
                    onclick="showDoctorInfo(<?= $doctor['id'] ?>)">
                Подробнее
            </button>
        </div>
        <?php endforeach; ?>
        <button class="btn btn--primary" onclick="showAllDoctors()">Показать всех</button>
    </div>
</div>

            <!-- После блока doctors-list -->
            <div class="profile-section">
                <h2>История приёмов</h2>
                <div class="appointments-list">
                    <?php if (empty($appointments)): ?>
                        <p class="no-data">История приёмов пуста</p>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
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
                                            'confirmed' => 'Подтверждён',
                                            'completed' => 'Завершён',
                                            'cancelled' => 'Отменён'
                                        ];
                                        echo $statusText[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="appointment-doctor">
                                    <h4>Врач:</h4>
                                    <p><?= htmlspecialchars($appointment['doctor_surname'] . ' ' . 
                                                          $appointment['doctor_name'] . ' ' . 
                                                          $appointment['doctor_patronymic']) ?></p>
                                    <p class="specialization"><?= htmlspecialchars($appointment['specialization']) ?></p>
                                </div>
                                <?php if (!empty($appointment['complaint'])): ?>
                                    <div class="appointment-details">
                                        <div class="complaint">
                                            <h4>Жалобы:</h4>
                                            <p><?= htmlspecialchars($appointment['complaint']) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                    <div class="appointment-actions">
                                        <button class="btn btn--primary" onclick="startConsultation(<?= $appointment['id'] ?>)">
                                            Открыть чат с врачом
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                                <a href="#" class="social-link"><i class="fab fa-vk" aria-hidden="true"></i></a>
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


    <!-- Модальное окно с информацией о враче -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <span class="close" title="Закрыть">&times;</span>
            <div v-if="doctor">
                <div class="doctor-info">
                    <h2>{{ doctor.full_name }}</h2>
                    <p class="specialization">{{ doctor.specialization }}</p>
                    <p class="contacts">
                        <span v-if="doctor.phone"><i class="fas fa-phone"></i> {{ doctor.phone }}</span>
                        <span v-if="doctor.email"><i class="fas fa-envelope"></i> {{ doctor.email }}</span>
                    </p>
                    
                    <div class="appointment-form">
                        <h3>Записаться на приём</h3>
                        <div class="calendar">
                            <div class="calendar-header">
                                <button @click="prevMonth">&lt;</button>
                                <span>{{ currentMonthYear }}</span>
                                <button @click="nextMonth">&gt;</button>
                            </div>
                            <div class="calendar-grid">
                                <div v-for="day in daysOfWeek" :key="day" class="calendar-day-header">
                                    {{ day }}
                                </div>
                                <div v-for="date in calendarDays" 
                                     :key="date.date"
                                     :class="['calendar-day', {
                                         'disabled': !isDateAvailable(date.date),
                                         'selected': selectedDate === date.date
                                     }]"
                                     @click="selectDate(date.date)">
                                    {{ date.dayOfMonth }}
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="selectedDate" class="time-slots">
                            <h4>Доступное время:</h4>
                            <div class="time-grid">
                                <button v-for="time in availableTimeSlots"
                                        :key="time"
                                        :class="['time-slot', { selected: selectedTime === time }]"
                                        @click="selectTime(time)">
                                    {{ time }}
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="selectedDate && selectedTime" class="complaint-section">
                            <h4>Опишите ваши симптомы:</h4>
                            <textarea v-model="complaint" 
                                     placeholder="Кратко опишите причину обращения"
                                     rows="4"></textarea>
                        </div>
                        
                        <button class="btn btn--primary" 
                                @click="submitAppointment"
                                :disabled="!canSubmit">
                            Записаться на приём
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    
                    <!-- Добавляем скрытый input для файла -->
                    <input type="file" 
                           ref="fileInput" 
                           @change="handleFileUpload" 
                           style="display: none"
                           accept="image/*,.pdf,.doc,.docx">
                    
                    <!-- Добавляем кнопку загрузки файла -->
                    <button @click="triggerFileUpload" 
                            class="btn btn--primary btn-attach">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    
                    <button @click="sendMessage" 
                            :disabled="!canSendMessage"
                            class="btn btn--primary">
                        Отправить
                    </button>
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

    // Инициализация Vue приложения
    const { createApp } = Vue;

    const app = createApp({
        data() {
            return {
                doctor: null,
                currentDate: new Date(),
                selectedDate: null,
                selectedTime: null,
                complaint: '',
                bookedSlots: [],
                daysOfWeek: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                timeSlots: ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', 
                           '14:00', '14:30', '15:00', '15:30', '16:00', '16:30']
            }
        },
        computed: {
            currentMonthYear() {
                return this.currentDate.toLocaleString('ru', { month: 'long', year: 'numeric' });
            },
            calendarDays() {
                // Логика формирования дней календаря
                const days = [];
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                
                // Добавляем пустые дни в начало
                let firstDayOfWeek = firstDay.getDay() || 7;
                for (let i = 1; i < firstDayOfWeek; i++) {
                    days.push({ date: null, dayOfMonth: '' });
                }
                
                // Добавляем дни месяца
                for (let i = 1; i <= lastDay.getDate(); i++) {
                    const date = new Date(year, month, i);
                    days.push({
                        date: date.toISOString().split('T')[0],
                        dayOfMonth: i
                    });
                }
                
                return days;
            },
            availableTimeSlots() {
                if (!this.selectedDate) return [];
                
                return this.timeSlots.filter(time => {
                    return !this.bookedSlots.some(slot => 
                        slot.appointment_date === this.selectedDate && 
                        slot.appointment_time === time + ':00'
                    );
                });
            },
            canSubmit() {
                return this.selectedDate && this.selectedTime && this.complaint.trim();
            }
        },
        methods: {
            async loadDoctorInfo(doctorId) {
                try {
                    const response = await fetch(`api/getDoctorInfo.php?id=${doctorId}`);
                    this.doctor = await response.json();
                    this.loadBookedSlots();
                } catch (error) {
                    console.error('Ошибка загрузки информации о враче:', error);
                }
            },
            async loadBookedSlots() {
                try {
                    const response = await fetch(`api/getDoctorSchedule.php?doctor_id=${this.doctor.id}`);
                    const data = await response.json();
                    this.bookedSlots = data.booked_slots;
                } catch (error) {
                    console.error('Ошибка загрузки расписания:', error);
                }
            },
            prevMonth() {
                this.currentDate = new Date(this.currentDate.getFullYear(), 
                                          this.currentDate.getMonth() - 1);
            },
            nextMonth() {
                this.currentDate = new Date(this.currentDate.getFullYear(), 
                                          this.currentDate.getMonth() + 1);
            },
            isDateAvailable(date) {
                if (!date) return false;
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return new Date(date) >= today;
            },
            selectDate(date) {
                if (this.isDateAvailable(date)) {
                    this.selectedDate = date;
                    this.selectedTime = null;
                }
            },
            selectTime(time) {
                this.selectedTime = time;
            },
            async submitAppointment() {
                if (!this.canSubmit) return;

                const formData = new FormData();
                formData.append('doctor_id', this.doctor.id);
                formData.append('appointment_date', this.selectedDate);
                formData.append('appointment_time', this.selectedTime);
                formData.append('complaint', this.complaint);

                try {
                    const response = await fetch('api/createAppointment.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Запись успешно создана');
                        location.reload();
                    } else {
                        alert(result.error || 'Ошибка при создании записи');
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при создании записи');
                }
            }
        }
    }).mount('#doctorModal');

    // Обновляем функцию показа модального окна
    function showDoctorInfo(doctorId) {
        const modal = document.getElementById('doctorModal');
        modal.style.display = 'block';
        app.loadDoctorInfo(doctorId);
    }

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
    // Маска для номера паспорта
    </script>

    <script src="js/modal.js"></script>
    <script src="js/patient-consultation.js"></script> 
    <script>
        //показ всех врачей
function showAllDoctors() {
    const doctorsList = document.querySelector('.doctors-list');
    doctorsList.innerHTML = `<?php foreach ($doctors as $doctor): ?>
        <div class="doctor-card">
            <h3><?= htmlspecialchars($doctor['surname'] . ' ' . $doctor['name'] . ' ' . $doctor['patronymic']) ?></h3>
            <p class="specialization"><?= htmlspecialchars($doctor['specialization']) ?></p>
            <button class="btn btn--secondary" 
                    onclick="showDoctorInfo(<?= $doctor['id'] ?>)">
                Подробнее
            </button>
        </div>
        <?php endforeach; ?>`;
}
</script>
</body>
</html>
