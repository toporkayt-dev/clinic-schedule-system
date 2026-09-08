<?php
/**
 * Класс для подключения и работы с базой данных
 * 
 * Использует MySQLi для безопасных запросов
 * Поддерживает prepared statements для защиты от SQL-инъекций
 */

require_once __DIR__ . '/../config.php';

class Database {
    private $connection;
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $port = DB_PORT;
    private $charset = 'utf8mb4';
    
    /**
     * Конструктор - подключение к БД
     */
    public function __construct() {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->user,
                $this->pass,
                $this->dbname,
                $this->port
            );
            
            // Проверка ошибок подключения
            if ($this->connection->connect_error) {
                throw new Exception('Connection Error: ' . $this->connection->connect_error);
            }
            
            // Установка кодировки
            $this->connection->set_charset($this->charset);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Выполнить SELECT запрос
     * 
     * @param string $sql SQL запрос
     * @param array $params Параметры для подстановки
     * @param string $types Типы параметров (s-string, i-int, d-double, b-blob)
     * @return array Массив результатов
     */
    public function select($sql, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $this->connection->error);
            }
            
            // Привязка параметров если они есть
            if (!empty($params)) {
                if (empty($types)) {
                    // Автоматическое определение типов
                    $types = $this->getTypes($params);
                }
                $stmt->bind_param($types, ...$params);
            }
            
            // Выполнение запроса
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }
            
            // Получение результатов
            $result = $stmt->get_result();
            $data = [];
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            return $data;
            
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return [];
        }
    }
    
    /**
     * Выполнить SELECT запрос и вернуть одну строку
     * 
     * @param string $sql SQL запрос
     * @param array $params Параметры для подстановки
     * @param string $types Типы параметров
     * @return array|null Массив результата или NULL
     */
    public function selectOne($sql, $params = [], $types = '') {
        $result = $this->select($sql, $params, $types);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Выполнить INSERT запрос
     * 
     * @param string $sql SQL запрос
     * @param array $params Параметры для подстановки
     * @param string $types Типы параметров
     * @return int ID вставленной записи
     */
    public function insert($sql, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $this->connection->error);
            }
            
            if (!empty($params)) {
                if (empty($types)) {
                    $types = $this->getTypes($params);
                }
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }
            
            $id = $this->connection->insert_id;
            $stmt->close();
            
            return $id;
            
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 0;
        }
    }
    
    /**
     * Выполнить UPDATE запрос
     * 
     * @param string $sql SQL запрос
     * @param array $params Параметры для подстановки
     * @param string $types Типы параметров
     * @return int Количество затронутых строк
     */
    public function update($sql, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $this->connection->error);
            }
            
            if (!empty($params)) {
                if (empty($types)) {
                    $types = $this->getTypes($params);
                }
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            return $affected;
            
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 0;
        }
    }
    
    /**
     * Выполнить DELETE запрос
     * 
     * @param string $sql SQL запрос
     * @param array $params Параметры для подстановки
     * @param string $types Типы параметров
     * @return int Количество удаленных строк
     */
    public function delete($sql, $params = [], $types = '') {
        return $this->update($sql, $params, $types);
    }
    
    /**
     * Подсчитать количество строк
     * 
     * @param string $sql SQL запрос
     * @param array $params Параметры для подстановки
     * @param string $types Типы параметров
     * @return int Количество строк
     */
    public function count($sql, $params = [], $types = '') {
        $sql = preg_replace('/^SELECT .* FROM/i', 'SELECT COUNT(*) as count FROM', $sql);
        $result = $this->selectOne($sql, $params, $types);
        return isset($result['count']) ? (int)$result['count'] : 0;
    }
    
    /**
     * Запустить транзакцию
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    /**
     * Коммитить транзакцию
     */
    public function commit() {
        $this->connection->commit();
    }
    
    /**
     * Откатить транзакцию
     */
    public function rollback() {
        $this->connection->rollback();
    }
    
    /**
     * Получить исходное соединение
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Экранировать строку
     * 
     * @param string $string Строка для экранирования
     * @return string Экранированная строка
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Получить ошибку
     * 
     * @return string Текст ошибки
     */
    public function getError() {
        return $this->connection->error;
    }
    
    /**
     * Закрыть соединение
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    /**
     * Вспомогательный метод для определения типов параметров
     * 
     * @param array $params Массив параметров
     * @return string Строка типов
     */
    private function getTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
    
    /**
     * Вывести ошибку
     * 
     * @param string $message Сообщение об ошибке
     */
    private function error($message) {
        if (DEBUG_MODE) {
            error_log('Database Error: ' . $message);
            echo json_encode(['success' => false, 'error' => $message]);
        } else {
            error_log('Database Error: ' . $message);
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }
        exit;
    }
}

// Создание глобального экземпляра БД
global $db;
if (!isset($db)) {
    $db = new Database();
}
