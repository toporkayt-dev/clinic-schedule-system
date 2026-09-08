<?php
/**
 * Класс для работы с резервным копированием данных
 * 
 * Функции:
 * - Экспорт записей пациентов в JSON/SQL
 * - Импорт данных из резервной копии
 * - Планирование автоматического копирования
 * - Управление старыми резервными копиями
 */

require_once __DIR__ . '/db.php';

class Backup {
    private $db;
    private $backup_dir = BACKUP_DIR;
    private $retention_days = BACKUP_RETENTION_DAYS;
    
    public function __construct($database) {
        $this->db = $database;
        $this->ensureBackupDir();
    }
    
    /**
     * Создать резервную копию (JSON формат)
     * 
     * @param int $user_id ID пользователя, инициирующего копирование
     * @return array Результат
     */
    public function createJsonBackup($user_id = null) {
        try {
            // Получение всех записей пациентов
            $records = $this->db->select('SELECT * FROM records ORDER BY date DESC');
            
            $backup_data = [
                'backup_timestamp' => date('Y-m-d H:i:s'),
                'total_records' => count($records),
                'records' => $records
            ];
            
            // Создание файла
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.json';
            $filepath = $this->backup_dir . $filename;
            
            $json_content = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (!file_put_contents($filepath, $json_content)) {
                return ['success' => false, 'error' => 'Failed to write backup file'];
            }
            
            // Логирование
            $filesize = filesize($filepath);
            $this->logBackup($user_id, 'manual', $filepath, $filesize, 'success');
            
            return [
                'success' => true,
                'message' => 'Backup created successfully',
                'filename' => $filename,
                'filepath' => $filepath,
                'records_count' => count($records),
                'file_size' => $this->formatFileSize($filesize),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->logBackup($user_id, 'manual', '', 0, 'failed', $e->getMessage());
            return ['success' => false, 'error' => 'Backup creation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Создать SQL резервную копию
     * 
     * @param int $user_id ID пользователя
     * @return array Результат
     */
    public function createSqlBackup($user_id = null) {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backup_dir . $filename;
            
            // Экспорт таблиц records
            $records = $this->db->select('SELECT * FROM records');
            
            $sql = "-- Backup created: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Total records: " . count($records) . "\n\n";
            $sql .= "DELETE FROM records;\n\n";
            
            foreach ($records as $record) {
                $sql .= "INSERT INTO records (name, date, time, phone, service, notes, created_by) VALUES (";
                $sql .= "'" . $this->db->escape($record['name']) . "', ";
                $sql .= "'" . $this->db->escape($record['date']) . "', ";
                $sql .= "'" . $this->db->escape($record['time']) . "', ";
                $sql .= "'" . $this->db->escape($record['phone']) . "', ";
                $sql .= "'" . $this->db->escape($record['service']) . "', ";
                $sql .= "'" . $this->db->escape($record['notes']) . "', ";
                $sql .= (int)$record['created_by'] . ");\n";
            }
            
            if (!file_put_contents($filepath, $sql)) {
                return ['success' => false, 'error' => 'Failed to write SQL backup file'];
            }
            
            $filesize = filesize($filepath);
            $this->logBackup($user_id, 'manual', $filepath, $filesize, 'success');
            
            return [
                'success' => true,
                'message' => 'SQL backup created successfully',
                'filename' => $filename,
                'filepath' => $filepath,
                'records_count' => count($records),
                'file_size' => $this->formatFileSize($filesize)
            ];
            
        } catch (Exception $e) {
            $this->logBackup($user_id, 'manual', '', 0, 'failed', $e->getMessage());
            return ['success' => false, 'error' => 'SQL backup creation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Восстановить данные из JSON резервной копии
     * 
     * @param string $filepath Путь к файлу резервной копии
     * @param int $user_id ID пользователя
     * @return array Результат
     */
    public function restoreFromJsonBackup($filepath, $user_id) {
        try {
            if (!file_exists($filepath)) {
                return ['success' => false, 'error' => 'Backup file not found'];
            }
            
            $json_content = file_get_contents($filepath);
            $backup_data = json_decode($json_content, true);
            
            if (!$backup_data || !isset($backup_data['records'])) {
                return ['success' => false, 'error' => 'Invalid backup file format'];
            }
            
            // Очистка текущих записей
            $this->db->delete('DELETE FROM records');
            
            // Восстановление записей
            $restored_count = 0;
            $this->db->beginTransaction();
            
            try {
                foreach ($backup_data['records'] as $record) {
                    $this->db->insert(
                        'INSERT INTO records (name, date, time, phone, service, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $record['name'],
                            $record['date'],
                            $record['time'],
                            $record['phone'],
                            $record['service'],
                            $record['notes'],
                            $user_id
                        ],
                        'ssssssi'
                    );
                    $restored_count++;
                }
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'message' => 'Data restored successfully',
                    'restored_records' => $restored_count,
                    'backup_timestamp' => $backup_data['backup_timestamp'] ?? 'unknown'
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Restore failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Получить список резервных копий
     * 
     * @return array Список файлов резервных копий
     */
    public function getBackupsList() {
        $backups = [];
        
        if (!is_dir($this->backup_dir)) {
            return $backups;
        }
        
        $files = scandir($this->backup_dir, SCANDIR_SORT_DESCENDING);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filepath = $this->backup_dir . $file;
            $backups[] = [
                'filename' => $file,
                'filepath' => $filepath,
                'size' => $this->formatFileSize(filesize($filepath)),
                'size_bytes' => filesize($filepath),
                'created' => date('Y-m-d H:i:s', filemtime($filepath)),
                'timestamp' => filemtime($filepath)
            ];
        }
        
        return $backups;
    }
    
    /**
     * Удалить резервную копию
     * 
     * @param string $filename Имя файла
     * @return array Результат
     */
    public function deleteBackup($filename) {
        // Защита от обхода пути
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return ['success' => false, 'error' => 'Invalid filename'];
        }
        
        $filepath = $this->backup_dir . $filename;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }
        
        if (!unlink($filepath)) {
            return ['success' => false, 'error' => 'Failed to delete backup'];
        }
        
        return ['success' => true, 'message' => 'Backup deleted successfully'];
    }
    
    /**
     * Очистить старые резервные копии
     * 
     * @return array Результат
     */
    public function cleanupOldBackups() {
        $deleted_count = 0;
        $cutoff_time = time() - ($this->retention_days * 86400);
        
        $backups = $this->getBackupsList();
        
        foreach ($backups as $backup) {
            if ($backup['timestamp'] < $cutoff_time) {
                if (unlink($backup['filepath'])) {
                    $deleted_count++;
                }
            }
        }
        
        return [
            'success' => true,
            'message' => 'Cleanup completed',
            'deleted_count' => $deleted_count,
            'retention_days' => $this->retention_days
        ];
    }
    
    /**
     * Получить статистику резервных копий
     * 
     * @return array Статистика
     */
    public function getBackupStats() {
        $backups = $this->getBackupsList();
        $total_size = 0;
        
        foreach ($backups as $backup) {
            $total_size += $backup['size_bytes'];
        }
        
        // Получить последнюю успешную резервную копию
        $last_backup_log = $this->db->selectOne(
            'SELECT * FROM backup_logs WHERE status = "success" ORDER BY created_at DESC LIMIT 1'
        );
        
        return [
            'total_backups' => count($backups),
            'total_size' => $this->formatFileSize($total_size),
            'total_size_bytes' => $total_size,
            'retention_days' => $this->retention_days,
            'last_backup' => $last_backup_log ? $last_backup_log['created_at'] : 'Never',
            'last_backup_size' => $last_backup_log ? $this->formatFileSize($last_backup_log['file_size']) : 'N/A'
        ];
    }
    
    /**
     * Логировать действие резервной копии
     * 
     * @param int $user_id ID пользователя
     * @param string $backup_type Тип (manual/auto)
     * @param string $filepath Путь к файлу
     * @param int $file_size Размер файла
     * @param string $status Статус (success/failed)
     * @param string $error_message Сообщение об ошибке
     */
    private function logBackup($user_id, $backup_type, $filepath, $file_size, $status, $error_message = '') {
        $this->db->insert(
            'INSERT INTO backup_logs (user_id, backup_type, file_path, file_size, status, error_message) VALUES (?, ?, ?, ?, ?, ?)',
            [$user_id, $backup_type, $filepath, $file_size, $status, $error_message],
            'issiis'
        );
    }
    
    /**
     * Убедиться, что директория резервных копий существует
     */
    private function ensureBackupDir() {
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Форматировать размер файла
     * 
     * @param int $bytes Размер в байтах
     * @return string Форматированный размер
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
