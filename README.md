# 🏥 Clinic Schedule System

Система управления расписанием стоматологической клиники с авторизацией, управлением пациентами и резервным копированием данных.

## 🎯 Функционал

- 🔐 **Авторизация** - регистрация, вход, проверка токенов
- 📅 **Расписание** - управление записями пациентов
- 📝 **Заметки** - личные заметки для каждого пользователя
- 💾 **Резервное копирование** - сохранение данных пациентов
- 🔧 **Админ-панель** - управление пользователями
- 🗄️ **MySQL база данных** - синхронизация со всех устройств

## 📋 Требования

- PHP 7.4+
- MySQL 5.7+ или MariaDB
- Web-сервер (Apache/Nginx)
- Современный браузер

## 🚀 Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/toporkayt-dev/clinic-schedule-system.git
cd clinic-schedule-system
```

### 2. Настройка базы данных

**Создайте новую БД:**
```sql
CREATE DATABASE clinic_db;
```

**Импортируйте схему:**
```bash
mysql -u root -p clinic_db < backend/sql/schema.sql
```

### 3. Настройка конфигурации

**Скопируйте файл конфига:**
```bash
cp config.php.example config.php
```

**Отредактируйте `config.php` с вашими данными БД:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'clinic_db');
```

### 4. Запуск локального сервера

**Вариант 1: Встроенный PHP сервер**
```bash
cd frontend
php -S localhost:8000
```

**Вариант 2: Apache/Nginx** - укажите корневую папку `frontend`

### 5. Первый вход

1. Откройте http://localhost:8000/login.html
2. Зарегистрируйтесь или используйте тестовый аккаунт (см. ниже)
3. Начните работу!

## 🧪 Тестовые аккаунты

После импорта SQL схемы:

| Роль | Логин | Пароль | Статус |
|------|-------|--------|--------|
| Администратор | admin | admin123 | Активен |
| Врач | doctor | doctor123 | Активен |

## 📁 Структура проекта

```
clinic-schedule-system/
├── backend/
│   ├── api/
│   │   ├── login.php              # POST авторизация
│   │   ├── register.php           # POST регистрация
│   │   ├── check_token.php        # GET проверка токена
│   │   ├── logout.php             # POST выход
│   │   ├── backup_data.php        # GET/POST резервная копия
│   │   └── user_management.php    # API управления пользователями
│   ├── db.php                     # Класс подключения БД
│   ├── auth.php                   # Класс авторизации
│   ├── backup.php                 # Класс резервного копирования
│   └── sql/
│       └── schema.sql             # Схема БД
├── frontend/
│   ├── index.html                 # Главная страница
│   ├── login.html                 # Страница входа
│   ├── register.html              # Страница регистрации
│   ├── schedule.html              # Расписание
│   ├── backup.html                # Резервная копия
│   ├── admin.html                 # Админ-панель
│   ├── js/
│   │   ├── auth.js                # Логика авторизации
│   │   ├── api.js                 # API клиент
│   │   ├── backup.js              # Логика резервной копии
│   │   └── utils.js               # Утилиты
│   └── css/
│       └── style.css              # Стили
├── config.php                     # Конфигурация БД (НЕ коммитить!)
├── config.php.example             # Пример конфигурации
└── README.md                      # Этот файл
```

## 🔐 API Endpoints

### Авторизация

**POST /backend/api/login.php**
```json
{
  "username": "admin",
  "password": "admin123"
}
```
Ответ:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

**POST /backend/api/register.php**
```json
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "password123"
}
```

**GET /backend/api/check_token.php**
- Заголовок: `Authorization: Bearer <token>`
- Проверяет валидность токена

**POST /backend/api/logout.php**
- Заголовок: `Authorization: Bearer <token>`
- Очищает токен

### Резервное копирование

**GET /backend/api/backup_data.php**
- Скачивает JSON с записями пациентов
- Заголовок: `Authorization: Bearer <token>`

**POST /backend/api/backup_data.php**
- Восстанавливает данные из резервной копии
- Заголовок: `Authorization: Bearer <token>`

## 💾 Резервное копирование

### Автоматическое
Кажджый день в 23:00 создаётся резервная копия в папке `/backups/`

### Ручное
1. Перейдите на страницу "Резервная копия"
2. Нажмите "Скачать копию" - получите JSON файл
3. Нажмите "Восстановить" - загрузите JSON файл

## 🔒 Безопасность

- ✅ Пароли хешируются с `password_hash()` и `password_verify()`
- ✅ JWT токены для авторизации
- ✅ CORS защита
- ✅ Валидация входных данных
- ✅ Защита от SQL-инъекций (prepared statements)
- ✅ Защита от XSS (htmlspecialchars)

## 📊 Модель данных

### users
```sql
id          INT PRIMARY KEY AUTO_INCREMENT
username    VARCHAR(255) UNIQUE
password    VARCHAR(255)
email       VARCHAR(255)
token       VARCHAR(500)
role        ENUM('admin', 'doctor', 'user')
is_active   BOOLEAN DEFAULT FALSE
created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### records
```sql
id          INT PRIMARY KEY AUTO_INCREMENT
name        VARCHAR(255)
date        DATE
time        TIME
phone       VARCHAR(20)
service     VARCHAR(255)
notes       TEXT
created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### notes
```sql
id          INT PRIMARY KEY AUTO_INCREMENT
user_id     INT FOREIGN KEY
text        TEXT
created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

## 🛠️ Полезные команды

```bash
# Запуск локального сервера
php -S localhost:8000

# Подключение к БД
mysql -u root -p clinic_db

# Экспорт БД
mysqldump -u root -p clinic_db > backup.sql

# Импорт БД
mysql -u root -p clinic_db < backup.sql
```

## 📝 Лицензия

MIT License

## 👨‍💻 Автор

toporkayt-dev

## 📞 Поддержка

Если у вас есть вопросы или проблемы, создайте Issue на GitHub.
