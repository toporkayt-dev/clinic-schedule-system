<?php
/**
 * API Endpoint: GET|POST /backend/api/backup_data.php
 * Работа с резервным копированием данных пациентов
 * 
 * GET - Скачивание резервной копии
 * Headers:
 * - Authorization: Bearer <token>
 * - backup_type: json или sql (по умолчанию json)
 * 
 * POST - Восстановление данных из резервной копии
 * Headers:
 * - Authorization: Bearer <token>
 * 
 * POST параметры (JSON):
 * - action: "restore" или "list"
 * - file: имя файла для восстановления (для restore)
 * - format: "json" или "sql"
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
require_once __DIR__ . '/../backup.php';

try {
    // Инициализация БД и Backup класса
    $db = new Database();
    $auth = new Auth($db);
    $backup = new Backup($db);
    
    // Проверка авторизации
    $user = $auth->getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    // Только администратор может работать с резервными копиями
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied - admin only']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET - Скачивание резервной копии
        $format = isset($_GET['format']) ? $_GET['format'] : 'json';
        
        if ($format === 'sql') {
            $result = $backup->createSqlBackup($user['id']);
        } else {
            $result = $backup->createJsonBackup($user['id']);
        }
        
        if ($result['success']) {
            // Выдача файла на скачивание
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . filesize($result['filepath']));
            
            readfile($result['filepath']);
            exit;
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // POST - Управление резервными копиями
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
            exit;
        }
        
        $action = $input['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                // Получить список резервных копий
                $backups = $backup->getBackupsList();
                $stats = $backup->getBackupStats();
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'backups' => $backups,
                    'stats' => $stats
                ]);
                break;
                
            case 'restore':
                // Восстановление из резервной копии
                if (empty($input['filename'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Filename is required']);
                    exit;
                }
                
                $filename = basename($input['filename']); // Защита от обхода пути
                $filepath = BACKUP_DIR . $filename;
                
                if (!file_exists($filepath)) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Backup file not found']);
                    exit;
                }
                
                $result = $backup->restoreFromJsonBackup($filepath, $user['id']);
                
                if ($result['success']) {
                    http_response_code(200);
                } else {
                    http_response_code(400);
                }
                
                echo json_encode($result);
                break;
                
            case 'delete':
                // Удаление резервной копии
                if (empty($input['filename'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Filename is required']);
                    exit;
                }
                
                $result = $backup->deleteBackup($input['filename']);
                
                if ($result['success']) {
                    http_response_code(200);
                } else {
                    http_response_code(400);
                }
                
                echo json_encode($result);
                break;
                
            case 'cleanup':
                // Удалить старые резервные копии
                $result = $backup->cleanupOldBackups();
                
                http_response_code(200);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
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
