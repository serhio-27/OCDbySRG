// Функция для закрытия модального окна
function closeModal() {
    const modal = document.getElementById('doctorModal');
    if (modal) {
        modal.classList.remove('show');
        
        // Очищаем данные формы при закрытии
        if (app) {
            app.resetForm();
        }
    }
}

let app = null;

// Инициализация модального окна
document.addEventListener('DOMContentLoaded', function() {
    // Создаем и монтируем Vue приложение
    app = Vue.createApp({
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
                const days = [];
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                
                // Получаем первый день месяца
                const firstDay = new Date(year, month, 1);
                // Получаем последний день месяца
                const lastDay = new Date(year, month + 1, 0);
                
                // Корректируем день недели для русской локали (ПН = 1, ВС = 7)
                let firstDayOfWeek = firstDay.getDay();
                firstDayOfWeek = firstDayOfWeek === 0 ? 7 : firstDayOfWeek;
                
                // Добавляем пустые дни в начало
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
            resetForm() {
                this.doctor = null;
                this.selectedDate = null;
                this.selectedTime = null;
                this.complaint = '';
                this.bookedSlots = [];
            },
            async loadDoctorInfo(doctorId) {
                try {
                    const response = await fetch(`api/getDoctorInfo.php?id=${doctorId}`);
                    const data = await response.json();
                    this.doctor = data;
                    await this.loadBookedSlots();
                } catch (error) {
                    console.error('Ошибка загрузки информации о враче:', error);
                }
            },
            async loadBookedSlots() {
                if (!this.doctor) return;
                
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
                        closeModal();
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

    const modal = document.getElementById('doctorModal');
    const closeBtn = modal.querySelector('.close');
    
    // Закрытие по клику на крестик
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Закрытие по клику вне модального окна
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Закрытие по нажатию Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
});

// Функция открытия модального окна
function showDoctorInfo(doctorId) {
    const modal = document.getElementById('doctorModal');
    if (modal) {
        modal.classList.add('show');
        if (app) {
            app.loadDoctorInfo(doctorId);
        }
    }
}

// Делаем функцию доступной глобально
window.showDoctorInfo = showDoctorInfo;