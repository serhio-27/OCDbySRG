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