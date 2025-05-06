const accessibilityApp = Vue.createApp({
    data() {
        return {
            showModal: false,
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
        openModal() {
            const modal = document.getElementById('accessibilityModal');
            modal.style.display = 'block';
        },
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
        toggleHighContrast() {
            this.currentSettings.highContrast = !this.currentSettings.highContrast;
        },
        applySettings() {
            document.body.className = `font-${this.currentSettings.fontSize} spacing-${this.currentSettings.letterSpacing}` + (this.currentSettings.highContrast ? ' high-contrast' : '');
            localStorage.setItem('accessibilitySettings', JSON.stringify(this.currentSettings));
            this.closeModal();
        },
        resetSettings() {
            this.currentSettings = {
                fontSize: 'normal',
                letterSpacing: 'normal',
                highContrast: false
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
        
        // Закрытие по клику вне модального окна
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('accessibilityModal');
            if (event.target === modal) {
                this.closeModal();
            }
        });
        
        // Закрытие по нажатию Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        });
    }
}).mount('#accessibilityApp');

// Добавляем обработчик для кнопки в шапке
document.addEventListener('DOMContentLoaded', () => {
    const accessibilityBtn = document.querySelector('.btn--accessibility');
    if (accessibilityBtn) {
        accessibilityBtn.addEventListener('click', () => {
            accessibilityApp.openModal();
        });
    }
}); 