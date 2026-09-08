<?php
/**
 * API Endpoint: GET|POST|DELETE /backend/api/user_management.php
 * Управление пользователями (только для администратора)
 * 
 * GET - Получить список пользователей
 * POST - Активировать пользователя
 * DELETE - Удалить пользователя
 * 
 * Headers:
 * - Authorization: Bearer <token>
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
    // Инициализация БД и Auth класса
    $db = new Database();
    $auth = new Auth($db);
    
    // Проверка авторизации
    $user = $auth->getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    // Только администратор может управлять пользователями
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied - admin only']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Получить список всех пользователей
        $users = $auth->getAllUsers();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'users' => $users,
            'total' => count($users)
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Активировать пользователя
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required']);
            exit;
        }
        
        $action = $input['action'] ?? 'activate';
        $user_id = (int)$input['user_id'];
        
        if ($action === 'activate') {
            $result = $auth->activateUser($user_id);
            
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Удалить пользователя
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required']);
            exit;
        }
        
        $user_id = (int)$input['user_id'];
        $result = $auth->deleteUser($user_id);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Server error'
    ]);
}
