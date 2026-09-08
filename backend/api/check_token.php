<?php
/**
 * API Endpoint: GET /backend/api/check_token.php
 * Проверка валидности JWT токена
 * 
 * Headers:
 * - Authorization: Bearer <token>
 * 
 * Ответ содержит данные пользователя если токен валидный
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Инициализация БД и Auth класса
    $db = new Database();
    $auth = new Auth($db);
    
    // Получение текущего пользователя
    $user = $auth->getCurrentUser();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized', 'is_authenticated' => false]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'is_authenticated' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Server error',
        'is_authenticated' => false
    ]);
}
