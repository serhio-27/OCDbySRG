// Функция для проверки предстоящих приёмов
function checkUpcomingAppointments() {
    const appointments = window.appointments || [];
    const now = new Date();
    
    appointments.forEach(appointment => {
        if (appointment.status === 'confirmed') {
            const appointmentDate = new Date(appointment.appointment_date + 'T' + appointment.appointment_time);
            const timeDiff = appointmentDate - now;
            
            // Проверяем, если до приёма осталось 5 минут
            if (timeDiff > 0 && timeDiff <= 5 * 60 * 1000) {
                showAppointmentNotification(appointment);
            }
        }
    });
}

// Функция для отображения уведомления
function showAppointmentNotification(appointment) {
    const notification = document.getElementById('appointmentNotification');
    const doctorInfo = document.getElementById('notificationDoctor');
    const timeInfo = document.getElementById('notificationTime');
    
    doctorInfo.textContent = `Врач: ${appointment.doctor_surname} ${appointment.doctor_name} ${appointment.doctor_patronymic}`;
    timeInfo.textContent = `Время: ${appointment.appointment_time}`;
    
    notification.classList.add('show');
    
    // Автоматически скрываем уведомление через 5 минут
    setTimeout(() => {
        notification.classList.remove('show');
    }, 5 * 60 * 1000);
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Закрытие уведомления по клику
    document.querySelector('.close-notification').addEventListener('click', function() {
        document.getElementById('appointmentNotification').classList.remove('show');
    });

    // Проверяем приёмы каждую минуту
    setInterval(checkUpcomingAppointments, 60 * 1000);
    
    // Первоначальная проверка при загрузке страницы
    checkUpcomingAppointments();
}); 