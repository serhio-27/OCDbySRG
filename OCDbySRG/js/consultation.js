const consultationApp = Vue.createApp({
    data() {
        return {
            appointmentId: null,
            messages: [],
            newMessage: '',
            patientInfo: null,
            currentUserId: null,
            updateInterval: null
        }
    },
    methods: {
        async loadMessages() {
            if (!this.appointmentId) return;
            
            try {
                const response = await fetch(`api/getConsultationMessages.php?appointment_id=${this.appointmentId}`);
                const data = await response.json();
                if (data.messages) {
                    this.messages = data.messages;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            } catch (error) {
                console.error('Ошибка загрузки сообщений:', error);
            }
        },
        async sendMessage() {
            if (!this.newMessage.trim()) return;
            
            try {
                const response = await fetch('api/sendMessage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: this.appointmentId,
                        message: this.newMessage
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.newMessage = '';
                    await this.loadMessages();
                }
            } catch (error) {
                console.error('Ошибка отправки сообщения:', error);
            }
        },
        scrollToBottom() {
            const container = this.$refs.chatContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },
        formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString('ru', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        startUpdateInterval() {
            this.updateInterval = setInterval(() => {
                this.loadMessages();
            }, 5000);
        },
        stopUpdateInterval() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        },
        reset() {
            this.appointmentId = null;
            this.messages = [];
            this.newMessage = '';
            this.patientInfo = null;
            this.stopUpdateInterval();
        }
    }
}).mount('#consultationApp');

// Функции для управления модальным окном
function startConsultation(appointmentId) {
    const modal = document.getElementById('consultationModal');
    modal.style.display = 'block';
    consultationApp.appointmentId = appointmentId;
    consultationApp.loadMessages();
    consultationApp.startUpdateInterval();
}

function closeConsultation() {
    const modal = document.getElementById('consultationModal');
    modal.style.display = 'none';
    consultationApp.reset();
}

// Закрытие по клику вне модального окна
window.onclick = function(event) {
    const modal = document.getElementById('consultationModal');
    if (event.target === modal) {
        closeConsultation();
    }
} 