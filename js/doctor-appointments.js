function completeAppointment(appointmentId) {
    if (!confirm('Вы уверены, что хотите завершить приём?')) {
        return;
    }

    const formData = new FormData();
    formData.append('appointment_id', appointmentId);

    fetch('/api/completeAppointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Перезагружаем страницу для обновления списка приёмов
            location.reload();
        } else {
            alert('Произошла ошибка при завершении приёма');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при завершении приёма');
    });
}

function startAppointment(appointmentId) {
    window.location.href = `/consultation.php?appointment_id=${appointmentId}`;
} 