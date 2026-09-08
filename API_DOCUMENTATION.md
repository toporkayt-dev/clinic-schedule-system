# 📋 API Документация

## 🔐 Авторизация

### Регистрация нового пользователя

**URL:** `POST /backend/api/register.php`

**Request:**
```json
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (Success - 201):**
```json
{
  "success": true,
  "message": "User registered successfully. Waiting for admin approval.",
  "user_id": 3
}
```

**Response (Error - 400):**
```json
{
  "success": false,
  "error": "Username or email already exists"
}
```

---

### Вход в систему

**URL:** `POST /backend/api/login.php`

**Request:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@clinic.local",
    "role": "admin"
  }
}
```

**Response (Error - 401):**
```json
{
  "success": false,
  "error": "Invalid credentials"
}
```

---

### Проверка токена

**URL:** `GET /backend/api/check_token.php`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (Success - 200):**
```json
{
  "success": true,
  "is_authenticated": true,
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@clinic.local",
    "role": "admin"
  }
}
```

**Response (Error - 401):**
```json
{
  "success": false,
  "error": "Unauthorized",
  "is_authenticated": false
}
```

---

### Выход из системы

**URL:** `POST /backend/api/logout.php`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## 💾 Резервное копирование

### Создать резервную копию (JSON)

**URL:** `GET /backend/api/backup_data.php?format=json`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:** Прямое скачивание JSON файла

**Формат JSON резервной копии:**
```json
{
  "backup_timestamp": "2026-09-08 20:25:14",
  "total_records": 5,
  "records": [
    {
      "id": 1,
      "name": "Иван Петров",
      "date": "2026-09-08",
      "time": "09:00:00",
      "phone": "+7-999-123-45-67",
      "service": "Осмотр, консультация врача",
      "notes": "Боль при жевании"
    }
  ]
}
```

---

### Создать резервную копию (SQL)

**URL:** `GET /backend/api/backup_data.php?format=sql`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:** Прямое скачивание SQL файла

---

### Получить список резервных копий

**URL:** `POST /backend/api/backup_data.php`

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "action": "list"
}
```

**Response:**
```json
{
  "success": true,
  "backups": [
    {
      "filename": "backup_2026-09-08_20-25-14.json",
      "filepath": "/path/to/backup.json",
      "size": "2.45 KB",
      "size_bytes": 2510,
      "created": "2026-09-08 20:25:14",
      "timestamp": 1694188514
    }
  ],
  "stats": {
    "total_backups": 1,
    "total_size": "2.45 KB",
    "retention_days": 30,
    "last_backup": "2026-09-08 20:25:14",
    "last_backup_size": "2.45 KB"
  }
}
```

---

### Восстановить из резервной копии

**URL:** `POST /backend/api/backup_data.php`

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "action": "restore",
  "filename": "backup_2026-09-08_20-25-14.json"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Data restored successfully",
  "restored_records": 5,
  "backup_timestamp": "2026-09-08 20:25:14"
}
```

---

### Удалить резервную копию

**URL:** `POST /backend/api/backup_data.php`

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "action": "delete",
  "filename": "backup_2026-09-08_20-25-14.json"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Backup deleted successfully"
}
```

---

### Очистить старые резервные копии

**URL:** `POST /backend/api/backup_data.php`

**Headers:**
```
Authorization: Bearer <token>
```

**Request:**
```json
{
  "action": "cleanup"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cleanup completed",
  "deleted_count": 2,
  "retention_days": 30
}
```

---

## 👥 Управление пользователями (только админ)

### Получить список пользователей

**URL:** `GET /backend/api/user_management.php`

**Headers:**
```
Authorization: Bearer <admin_token>
```

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@clinic.local",
      "role": "admin",
      "is_active": 1,
      "created_at": "2026-09-08 19:00:00"
    },
    {
      "id": 3,
      "username": "newuser",
      "email": "user@example.com",
      "role": "user",
      "is_active": 0,
      "created_at": "2026-09-08 20:00:00"
    }
  ],
  "total": 2
}
```

---

### Активировать пользователя

**URL:** `POST /backend/api/user_management.php`

**Headers:**
```
Authorization: Bearer <admin_token>
```

**Request:**
```json
{
  "action": "activate",
  "user_id": 3
}
```

**Response:**
```json
{
  "success": true,
  "message": "User activated"
}
```

---

### Удалить пользователя

**URL:** `DELETE /backend/api/user_management.php`

**Headers:**
```
Authorization: Bearer <admin_token>
```

**Request:**
```json
{
  "user_id": 3
}
```

**Response:**
```json
{
  "success": true,
  "message": "User deleted"
}
```

---

## 🔑 Коды ошибок

| Код | Описание |
|-----|----------|
| 200 | OK - Успешный запрос |
| 201 | Created - Ресурс создан |
| 400 | Bad Request - Ошибка в запросе |
| 401 | Unauthorized - Требуется авторизация |
| 403 | Forbidden - Доступ запрещен |
| 404 | Not Found - Ресурс не найден |
| 405 | Method Not Allowed - Неправильный метод |
| 500 | Internal Server Error - Ошибка сервера |

---

## 📝 Примеры использования (JavaScript)

### Вход в систему
```javascript
const response = await fetch('/backend/api/login.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    username: 'admin',
    password: 'admin123'
  })
});

const data = await response.json();
if (data.success) {
  localStorage.setItem('token', data.token);
  console.log('Вход успешен:', data.user);
}
```

### Проверка авторизации
```javascript
const response = await fetch('/backend/api/check_token.php', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  }
});

const data = await response.json();
if (data.is_authenticated) {
  console.log('Вы авторизованы:', data.user);
} else {
  console.log('Требуется авторизация');
  window.location.href = 'login.html';
}
```

### Создание резервной копии
```javascript
const response = await fetch('/backend/api/backup_data.php?format=json', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  }
});

if (response.ok) {
  const blob = await response.blob();
  // Скачивание файла
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'backup.json';
  a.click();
}
```

### Получение списка резервных копий
```javascript
const response = await fetch('/backend/api/backup_data.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  },
  body: JSON.stringify({ action: 'list' })
});

const data = await response.json();
console.log('Резервные копии:', data.backups);
console.log('Статистика:', data.stats);
```
