const consultationApp = Vue.createApp({
    data() {
        return {
            appointmentId: null,
            messages: [],
            newMessage: '',
            patientInfo: null,
            currentUserId: window.currentUserId,
            updateInterval: null,
            selectedFile: null,
            showImageModal: false,
            selectedImage: null
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
            if (!this.newMessage.trim() && !this.selectedFile) return;
            
            try {
                const formData = new FormData();
                formData.append('appointment_id', this.appointmentId);
                formData.append('message', this.newMessage.trim());
                
                if (this.selectedFile) {
                    formData.append('file', this.selectedFile);
                }
                
                const response = await fetch('api/sendMessage.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    this.newMessage = '';
                    this.selectedFile = null;
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                    await this.loadMessages();
                } else {
                    console.error('Ошибка:', data.error);
                    alert(data.error || 'Ошибка при отправке сообщения');
                }
            } catch (error) {
                console.error('Ошибка отправки сообщения:', error);
                alert('Произошла ошибка при отправке сообщения');
            }
        },
        scrollToBottom() {
            const container = this.$refs.chatContainer;
            if (container) {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }
        },
        formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString('ru', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        isOwnMessage(message) {
            return message.sender_id === this.currentUserId;
        },
        triggerFileUpload() {
            this.$refs.fileInput.click();
        },
        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('Файл слишком большой. Максимальный размер: 10MB');
                    event.target.value = '';
                    return;
                }
                this.selectedFile = file;
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
        getFileIcon(fileType) {
            if (!fileType) return 'fas fa-file';
            if (fileType.startsWith('image/')) return 'fas fa-file-image';
            if (fileType.includes('pdf')) return 'fas fa-file-pdf';
            if (fileType.includes('word')) return 'fas fa-file-word';
            if (fileType.includes('excel')) return 'fas fa-file-excel';
            if (fileType.includes('text')) return 'fas fa-file-alt';
            return 'fas fa-file';
        },
        openImage(imagePath) {
            this.selectedImage = imagePath;
            this.showImageModal = true;
        },
        closeImageModal() {
            this.showImageModal = false;
            this.selectedImage = null;
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
            this.selectedFile = null;
            this.showImageModal = false;
            this.selectedImage = null;
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