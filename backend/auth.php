<?php
/**
 * Класс для работы с авторизацией и JWT токенами
 * 
 * Функции:
 * - Регистрация пользователя
 * - Вход в систему
 * - Проверка токена
 * - Выход из системы
 * - Управление сессиями
 */

require_once __DIR__ . '/db.php';

class Auth {
    private $db;
    private $jwt_secret = JWT_SECRET;
    private $jwt_algorithm = JWT_ALGORITHM;
    private $token_expiry = TOKEN_EXPIRY;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Регистрация нового пользователя
     * 
     * @param string $username Имя пользователя
     * @param string $email Email
     * @param string $password Пароль
     * @param string $role Роль (admin, doctor, user)
     * @return array Результат регистрации
     */
    public function register($username, $email, $password, $role = 'user') {
        // Валидация входных данных
        if (!$this->validateUsername($username)) {
            return ['success' => false, 'error' => 'Invalid username format'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        // Проверка, существует ли уже пользователь
        $existing = $this->db->selectOne(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$username, $email],
            'ss'
        );
        
        if ($existing) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }
        
        // Хеширование пароля
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Вставка нового пользователя
        $user_id = $this->db->insert(
            'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)',
            [$username, $email, $password_hash, $role, 0],
            'ssssi'
        );
        
        if (!$user_id) {
            return ['success' => false, 'error' => 'Registration failed'];
        }
        
        return [
            'success' => true,
            'message' => 'User registered successfully. Waiting for admin approval.',
            'user_id' => $user_id
        ];
    }
    
    /**
     * Вход в систему
     * 
     * @param string $username Имя пользователя
     * @param string $password Пароль
     * @return array Результат входа с токеном
     */
    public function login($username, $password) {
        // Валидация входных данных
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password required'];
        }
        
        // Поиск пользователя
        $user = $this->db->selectOne(
            'SELECT id, username, password, email, role, is_active FROM users WHERE username = ?',
            [$username],
            's'
        );
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Проверка, активирован ли пользователь
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'User account is not activated'];
        }
        
        // Проверка пароля
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Создание JWT токена
        $token = $this->generateToken($user['id'], $user['username'], $user['role']);
        
        // Сохранение токена в БД
        $this->db->update(
            'UPDATE users SET token = ? WHERE id = ?',
            [$token, $user['id']],
            'si'
        );
        
        // Логирование действия
        $this->logAction($user['id'], 'LOGIN', 'users', $user['id']);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Проверить валидность токена
     * 
     * @param string $token JWT токен
     * @return array|false Данные токена или false
     */
    public function verifyToken($token) {
        try {
            // Проверка формата токена
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            // Декодирование header и payload
            $header = json_decode(base64_decode($parts[0]), true);
            $payload = json_decode(base64_decode($parts[1]), true);
            
            if (!$header || !$payload) {
                return false;
            }
            
            // Проверка срока действия
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            // Проверка сигнатуры
            $signature = $this->signToken($parts[0], $parts[1]);
            if ($signature !== $parts[2]) {
                return false;
            }
            
            // Проверка в БД
            $user = $this->db->selectOne(
                'SELECT id, username, role FROM users WHERE id = ? AND token = ?',
                [$payload['user_id'], $token],
                'is'
            );
            
            if (!$user) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Получить текущего пользователя из токена
     * 
     * @return array|null Данные пользователя или null
     */
    public function getCurrentUser() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            return null;
        }
        
        $payload = $this->verifyToken($token);
        
        if (!$payload) {
            return null;
        }
        
        return $this->db->selectOne(
            'SELECT id, username, email, role FROM users WHERE id = ?',
            [$payload['user_id']],
            'i'
        );
    }
    
    /**
     * Выход из системы
     * 
     * @param string $token JWT токен
     * @return array Результат выхода
     */
    public function logout($token) {
        $payload = $this->verifyToken($token);
        
        if (!$payload) {
            return ['success' => false, 'error' => 'Invalid token'];
        }
        
        // Очистка токена в БД
        $this->db->update(
            'UPDATE users SET token = NULL WHERE id = ?',
            [$payload['user_id']],
            'i'
        );
        
        // Логирование действия
        $this->logAction($payload['user_id'], 'LOGOUT', 'users', $payload['user_id']);
        
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    /**
     * Активировать пользователя (только админ)
     * 
     * @param int $user_id ID пользователя
     * @return array Результат
     */
    public function activateUser($user_id) {
        $affected = $this->db->update(
            'UPDATE users SET is_active = 1 WHERE id = ?',
            [$user_id],
            'i'
        );
        
        if ($affected) {
            $this->logAction(null, 'ACTIVATE_USER', 'users', $user_id);
            return ['success' => true, 'message' => 'User activated'];
        }
        
        return ['success' => false, 'error' => 'Failed to activate user'];
    }
    
    /**
     * Удалить пользователя (только админ)
     * 
     * @param int $user_id ID пользователя
     * @return array Результат
     */
    public function deleteUser($user_id) {
        // Нельзя удалить главного админа
        if ($user_id == 1) {
            return ['success' => false, 'error' => 'Cannot delete main admin'];
        }
        
        $affected = $this->db->delete(
            'DELETE FROM users WHERE id = ?',
            [$user_id],
            'i'
        );
        
        if ($affected) {
            $this->logAction(null, 'DELETE_USER', 'users', $user_id);
            return ['success' => true, 'message' => 'User deleted'];
        }
        
        return ['success' => false, 'error' => 'Failed to delete user'];
    }
    
    /**
     * Получить всех пользователей (только админ)
     * 
     * @return array Список пользователей
     */
    public function getAllUsers() {
        return $this->db->select(
            'SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at DESC'
        );
    }
    
    /**
     * Сгенерировать JWT токен
     * 
     * @param int $user_id ID пользователя
     * @param string $username Имя пользователя
     * @param string $role Роль
     * @return string JWT токен
     */
    private function generateToken($user_id, $username, $role) {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->jwt_algorithm
        ];
        
        $payload = [
            'user_id' => $user_id,
            'username' => $username,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + $this->token_expiry
        ];
        
        $header_encoded = $this->base64UrlEncode(json_encode($header));
        $payload_encoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->signToken($header_encoded, $payload_encoded);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }
    
    /**
     * Подписать токен
     * 
     * @param string $header Закодированный header
     * @param string $payload Закодированный payload
     * @return string Сигнатура
     */
    private function signToken($header, $payload) {
        $message = $header . '.' . $payload;
        $signature = hash_hmac('sha256', $message, $this->jwt_secret, true);
        return $this->base64UrlEncode($signature);
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Данные
     * @return string Закодированные данные
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Получить Bearer токен из заголовков
     * 
     * @return string|null Токен или null
     */
    private function getBearerToken() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }
        
        $matches = [];
        if (preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Валидация имени пользователя
     * 
     * @param string $username Имя пользователя
     * @return bool Валидно ли
     */
    private function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username);
    }
    
    /**
     * Логирование действия
     * 
     * @param int $user_id ID пользователя
     * @param string $action Действие
     * @param string $table_name Таблица
     * @param int $record_id ID записи
     */
    private function logAction($user_id, $action, $table_name, $record_id) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $this->db->insert(
            'INSERT INTO action_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, ?, ?, ?, ?)',
            [$user_id, $action, $table_name, $record_id, $ip],
            'issis'
        );
    }
}
