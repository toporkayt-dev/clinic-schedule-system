<?php
/**
 * API Endpoint: POST /backend/api/login.php
 * Авторизация пользователя и получение JWT токена
 * 
 * POST параметры:
 * - username (string) - имя пользователя
 * - password (string) - пароль
 * 
 * Ответ содержит JWT токен для последующих запросов
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

try {
    // Проверка метода
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Получение JSON данных
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    
    // Валидация обязательных полей
    if (empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username and password are required']);
        exit;
    }
    
    // Инициализация БД и Auth класса
    $db = new Database();
    $auth = new Auth($db);
    
    // Попытка входа
    $result = $auth->login(
        trim($input['username']),
        $input['password']
    );
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(401);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Server error'
    ]);
}
