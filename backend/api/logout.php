<?php
/**
 * API Endpoint: POST /backend/api/logout.php
 * Выход пользователя из системы
 * 
 * Headers:
 * - Authorization: Bearer <token>
 * 
 * Очищает токен в базе данных
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
    
    // Инициализация БД и Auth класса
    $db = new Database();
    $auth = new Auth($db);
    
    // Получение токена из заголовков
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No token provided']);
        exit;
    }
    
    // Выход пользователя
    $result = $auth->logout($token);
    
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
