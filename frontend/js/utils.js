/**
 * Утилиты для работы с датами, временем и форматированием
 */

const Utils = {
    /**
     * Форматировать дату в формат DD.MM.YYYY
     */
    formatDate(date) {
        if (typeof date === 'string') {
            date = new Date(date);
        }
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        
        return `${day}.${month}.${year}`;
    },

    /**
     * Форматировать время в формат HH:MM
     */
    formatTime(time) {
        if (!time) return '';
        return time.substring(0, 5);
    },

    /**
     * Форматировать дату и время
     */
    formatDateTime(datetime) {
        if (typeof datetime === 'string') {
            datetime = new Date(datetime);
        }
        
        return `${this.formatDate(datetime)} ${datetime.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`;
    },

    /**
     * Получить номер недели
     */
    getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    },

    /**
     * Получить начало недели
     */
    getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    },

    /**
     * Получить конец недели
     */
    getWeekEnd(date) {
        const start = this.getWeekStart(date);
        const end = new Date(start);
        end.setDate(end.getDate() + 6);
        return end;
    },

    /**
     * Показать уведомление
     */
    showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, duration);
    },

    /**
     * Показать модальное окно подтверждения
     */
    confirm(message) {
        return new Promise((resolve) => {
            if (window.confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    },

    /**
     * Скачать файл
     */
    downloadFile(blob, filename) {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    },

    /**
     * Дебаунс функции
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Копировать текст в буфер обмена
     */
    copyToClipboard(text) {
        return navigator.clipboard.writeText(text)
            .then(() => {
                this.showNotification('Скопировано в буфер обмена', 'success');
                return true;
            })
            .catch(err => {
                console.error('Ошибка копирования:', err);
                return false;
            });
    }
};
