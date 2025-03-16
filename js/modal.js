// Функция для закрытия модального окна
function closeModal() {
    const modal = document.getElementById('doctorModal');
    modal.style.display = 'none';
    
    // Очищаем данные формы при закрытии
    if (app) {
        app.selectedDate = null;
        app.selectedTime = null;
        app.complaint = '';
    }
}

// Инициализация модального окна
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('doctorModal');
    const closeBtn = modal.querySelector('.close');
    
    // Закрытие по клику на крестик
    closeBtn.addEventListener('click', closeModal);
    
    // Закрытие по клику вне модального окна
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Закрытие по нажатию Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
});

// Функция открытия модального окна
function showDoctorInfo(doctorId) {
    const modal = document.getElementById('doctorModal');
    modal.style.display = 'block';
    app.loadDoctorInfo(doctorId);
}

// Функция для открытия модального окна настроек доступности
function openAccessibilitySettings() {
    const modal = document.getElementById('accessibilityModal');
    modal.style.display = 'block';
}

// Инициализация Vue приложения для настроек доступности
const accessibilityApp = Vue.createApp({
    data() {
        return {
            fontSizes: [
                { value: 'normal', preview: '16px' },
                { value: 'large', preview: '20px' },
                { value: 'x-large', preview: '24px' },
                { value: 'xx-large', preview: '28px' }
            ],
            letterSpacings: [
                { value: 'normal', label: 'обычный' },
                { value: 'increased', label: 'увеличенный' },
                { value: 'large', label: 'большой' }
            ],
            currentSettings: {
                fontSize: 'normal',
                letterSpacing: 'normal'
            }
        }
    },
    methods: {
        closeModal() {
            const modal = document.getElementById('accessibilityModal');
            modal.style.display = 'none';
        },
        setFontSize(size) {
            this.currentSettings.fontSize = size;
        },
        setLetterSpacing(spacing) {
            this.currentSettings.letterSpacing = spacing;
        },
        applySettings() {
            document.body.className = `font-${this.currentSettings.fontSize} spacing-${this.currentSettings.letterSpacing}`;
            localStorage.setItem('accessibilitySettings', JSON.stringify(this.currentSettings));
            this.closeModal();
        },
        resetSettings() {
            this.currentSettings = {
                fontSize: 'normal',
                letterSpacing: 'normal'
            };
            document.body.className = '';
            localStorage.removeItem('accessibilitySettings');
            this.closeModal();
        },
        loadSavedSettings() {
            const saved = localStorage.getItem('accessibilitySettings');
            if (saved) {
                this.currentSettings = JSON.parse(saved);
                this.applySettings();
            }
        }
    },
    mounted() {
        this.loadSavedSettings();
        
        // Добавляем обработчики закрытия модального окна
        const modal = document.getElementById('accessibilityModal');
        const closeBtn = modal.querySelector('.close');
        
        // Закрытие по клику на крестик
        closeBtn.addEventListener('click', this.closeModal);
        
        // Закрытие по клику вне модального окна
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                this.closeModal();
            }
        });
        
        // Закрытие по нажатию Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                this.closeModal();
            }
        });
    }
}).mount('#accessibilityModal');

// Добавляем обработчик для кнопки открытия настроек
document.addEventListener('DOMContentLoaded', function() {
    const accessibilityBtn = document.querySelector('.btn--accessibility');
    if (accessibilityBtn) {
        accessibilityBtn.addEventListener('click', openAccessibilitySettings);
    }
});

// Создаем и монтируем Vue приложение
const app = Vue.createApp({
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
        }
    },
    methods: {
        closeModal() {
            const modal = document.getElementById('doctorModal');
            modal.style.display = 'none';
            this.resetForm();
        },
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
                this.loadBookedSlots();
            } catch (error) {
                console.error('Ошибка загрузки информации о враче:', error);
            }
        }
    },
    mounted() {
        // Инициализация обработчиков в mounted
        const modal = document.getElementById('doctorModal');
        const closeBtn = document.querySelector('#doctorModal .close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }
        
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                this.closeModal();
            }
        });
        
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                this.closeModal();
            }
        });
    }
}).mount('#doctorModal');

// Глобальная функция для открытия модального окна
window.showDoctorInfo = function(doctorId) {
    const modal = document.getElementById('doctorModal');
    modal.style.display = 'block';
    app.loadDoctorInfo(doctorId);
}

// Удаляем дублирующиеся обработчики событий
document.removeEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('doctorModal');
    const closeBtn = modal.querySelector('.close');
    closeBtn.removeEventListener('click', closeModal);
    window.removeEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
});