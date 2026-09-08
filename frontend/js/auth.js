/**
 * Логика авторизации и проверки сессии
 */

const Auth = {
    /**
     * Получить текущего пользователя
     */
    getCurrentUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },

    /**
     * Получить токен
     */
    getToken() {
        return localStorage.getItem('token');
    },

    /**
     * Установить токен и пользователя
     */
    setUser(token, user) {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
    },

    /**
     * Очистить данные сессии
     */
    clearSession() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    },

    /**
     * Проверить авторизацию
     */
    async checkAuth() {
        const token = this.getToken();
        
        if (!token) {
            return false;
        }

        try {
            const response = await api.get('/check_token.php');
            const data = await response.json();
            
            if (!data.is_authenticated) {
                this.clearSession();
                return false;
            }
            
            return true;
        } catch (error) {
            console.error('Auth check error:', error);
            this.clearSession();
            return false;
        }
    },

    /**
     * Войти в систему
     */
    async login(username, password) {
        try {
            const response = await api.post('/login.php', {
                username,
                password
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.setUser(data.token, data.user);
                return { success: true, user: data.user };
            } else {
                return { success: false, error: data.error };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, error: 'Connection error' };
        }
    },

    /**
     * Зарегистрировать новго пользователя
     */
    async register(username, email, password) {
        try {
            const response = await api.post('/register.php', {
                username,
                email,
                password
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Register error:', error);
            return { success: false, error: 'Connection error' };
        }
    },

    /**
     * Выйти из системы
     */
    async logout() {
        try {
            await api.post('/logout.php');
            this.clearSession();
            return true;
        } catch (error) {
            console.error('Logout error:', error);
            this.clearSession();
            return false;
        }
    },

    /**
     * Проверить, является ли пользователь администратором
     */
    isAdmin() {
        const user = this.getCurrentUser();
        return user && user.role === 'admin';
    },

    /**
     * Проверить, является ли пользователь врачом
     */
    isDoctor() {
        const user = this.getCurrentUser();
        return user && user.role === 'doctor';
    }
};

// Проверка авторизации при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
        // На страницах авторизации не проверяем токен
        if (!window.location.pathname.includes('login') && !window.location.pathname.includes('register')) {
            const isAuthenticated = await Auth.checkAuth();
            if (!isAuthenticated) {
                window.location.href = 'login.html';
            }
        }
    });
} else {
    // Если DOM уже загружен
    if (!window.location.pathname.includes('login') && !window.location.pathname.includes('register')) {
        Auth.checkAuth().then(isAuthenticated => {
            if (!isAuthenticated) {
                window.location.href = 'login.html';
            }
        });
    }
}
