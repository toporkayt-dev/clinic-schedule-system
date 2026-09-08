/**
 * API клиент для работы с бэкендом
 */

const API_BASE_URL = '/backend/api';

class APIClient {
    constructor() {
        this.token = localStorage.getItem('token');
    }

    /**
     * Выполнить HTTP запрос
     * @param {string} endpoint - Endpoint API
     * @param {object} options - Опции fetch
     * @returns {Promise} Результат запроса
     */
    async request(endpoint, options = {}) {
        const url = API_BASE_URL + endpoint;
        
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            if (response.status === 401) {
                // Токен невалидный - перенаправить на логин
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = 'login.html';
            }

            return response;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * GET запрос
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, { method: 'GET' });
    }

    /**
     * POST запрос
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * PUT запрос
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * DELETE запрос
     */
    async delete(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'DELETE',
            body: JSON.stringify(data)
        });
    }
}

// Глобальный экземпляр API клиента
const api = new APIClient();
