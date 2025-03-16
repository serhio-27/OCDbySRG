const consultationApp = Vue.createApp({
    data() {
        return {
            appointmentId: null,
            messages: [],
            newMessage: '',
            doctorInfo: null,
            currentUserId: null,
            updateInterval: null,
            selectedFile: null,
            showImageModal: false,
            selectedImage: null
        }
    },
    computed: {
        canSendMessage() {
            return this.newMessage.trim() || this.selectedFile;
        }
    },
    methods: {
        async loadDoctorInfo() {
            try {
                const response = await fetch(`api/getDoctorInfoForAppointment.php?appointment_id=${this.appointmentId}`);
                const data = await response.json();
                if (!data.error) {
                    this.doctorInfo = data;
                }
            } catch (error) {
                console.error('Ошибка загрузки информации о враче:', error);
            }
        },
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
            if (!this.canSendMessage) return;
            
            const formData = new FormData();
            formData.append('appointment_id', this.appointmentId);
            formData.append('message', this.newMessage);
            
            if (this.selectedFile) {
                formData.append('file', this.selectedFile);
            }
            
            try {
                const response = await fetch('api/sendMessage.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    this.newMessage = '';
                    this.selectedFile = null;
                    this.$refs.fileInput.value = '';
                    this.loadMessages();
                } else {
                    alert(result.error || 'Ошибка при отправке сообщения');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при отправке сообщения');
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
            this.doctorInfo = null;
            this.stopUpdateInterval();
        },
        triggerFileUpload() {
            this.$refs.fileInput.click();
        },
        handleFileUpload(event) {
            this.selectedFile = event.target.files[0];
            if (this.selectedFile) {
                this.sendMessage();
            }
        },
        isImage(filePath) {
            if (!filePath) return false;
            const ext = filePath.split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        },
        getFileName(filePath) {
            if (!filePath) return '';
            return filePath.split('/').pop();
        },
        openImage(imagePath) {
            this.selectedImage = imagePath;
            this.showImageModal = true;
        },
        closeImageModal() {
            this.showImageModal = false;
            this.selectedImage = null;
        }
    }
}).mount('#consultationApp');

// Функции для управления модальным окном
function startConsultation(appointmentId) {
    const modal = document.getElementById('consultationModal');
    modal.style.display = 'block';
    consultationApp.appointmentId = appointmentId;
    consultationApp.loadDoctorInfo();
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