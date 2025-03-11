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