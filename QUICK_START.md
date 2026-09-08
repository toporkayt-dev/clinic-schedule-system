# 🚀 Быстрый старт

## Установка и настройка

### 1️⃣ Клонирование репозитория

```bash
git clone https://github.com/toporkayt-dev/clinic-schedule-system.git
cd clinic-schedule-system
```

### 2️⃣ Создание и импорт базы данных

**Вариант A: MySQL Command Line**
```bash
# Создание базы данных
mysql -u root -p -e "CREATE DATABASE clinic_db;"

# Импорт схемы
mysql -u root -p clinic_db < backend/sql/schema.sql
```

**Вариант B: phpMyAdmin**
1. Откройте http://localhost/phpmyadmin
2. Создайте новую БД с именем `clinic_db`
3. Выберите БД и нажмите "Импорт"
4. Загрузите файл `backend/sql/schema.sql`
5. Нажмите "Выполнить"

### 3️⃣ Настройка конфигурации

```bash
# Скопируйте пример конфигурации
cp config.php.example config.php

# Отредактируйте config.php с вашими данными БД
# vim config.php
# или используйте любой текстовый редактор
```

**Основные параметры config.php:**
```php
define('DB_HOST', 'localhost');      // Хост БД
define('DB_USER', 'root');           // Пользователь БД
define('DB_PASS', 'password');       // Пароль БД
define('DB_NAME', 'clinic_db');      // Имя БД
define('JWT_SECRET', 'your_secret'); // Секретный ключ JWT
```

### 4️⃣ Запуск локального сервера

**Вариант A: PHP Built-in Server**
```bash
cd frontend
php -S localhost:8000
```

Откройте в браузере: http://localhost:8000/login.html

**Вариант B: Apache (требуется конфигурация)**

Установите DocumentRoot на папку `frontend` в конфиге Apache.

**Вариант C: Nginx (требуется конфигурация)**

Настройте location для PHP на папку `backend/api`.

---

## 🔓 Первый вход

### Тестовые аккаунты (созданы при импорте schema.sql)

| Роль | Логин | Пароль | Описание |
|------|-------|--------|----------|
| 🔴 Администратор | admin | admin123 | Полный доступ ко всем функциям |
| 🟡 Врач | doctor | doctor123 | Доступ к расписанию и заметкам |

### Первый вход:
1. Откройте http://localhost:8000/login.html
2. Введите логин и пароль
3. Нажмите "Вход"
4. Вы будете перенаправлены на главную страницу

---

## 📁 Структура проекта

```
clinic-schedule-system/
├── 📄 config.php.example        ← Пример конфигурации
├── 📄 README.md                 ← Основная документация
├── 📄 API_DOCUMENTATION.md      ← Документация API
├── 📄 QUICK_START.md            ← Этот файл
│
├── 🔧 backend/
│   ├── 📄 db.php                ← Класс работы с БД (MySQLi)
│   ├── 📄 auth.php              ← Класс авторизации (JWT токены)
│   ├── 📄 backup.php            ← Класс резервного копирования
│   │
│   ├── 📁 api/
│   │   ├── 📄 login.php         ← POST вход в систему
│   │   ├── 📄 register.php      ← POST регистрация
│   │   ├── 📄 check_token.php   ← GET проверка авторизации
│   │   ├── 📄 logout.php        ← POST выход из системы
│   │   ├── 📄 backup_data.php   ← GET/POST резервная копия
│   │   ├── 📄 user_management.php ← GET/POST/DELETE управление пользователями
│   │   └── 📄 .htaccess         ← Конфиг Apache
│   │
│   └── 📁 sql/
│       └── 📄 schema.sql        ← Схема БД
│
├── 🎨 frontend/
│   ├── 📄 login.html            ← Страница входа
│   ├── 📄 register.html         ← Страница регистрации
│   ├── 📄 index.html            ← Главная страница
│   │
│   ├── 📁 js/
│   │   ├── 📄 api.js            ← API клиент
│   │   ├── 📄 auth.js           ← Логика авторизации
│   │   └── 📄 utils.js          ← Утилиты
│   │
│   └── 📁 css/
│       └── 📄 style.css         ← Стили
│
└── 📄 .htaccess                 ← Конфиг Apache (корень)
```

---

## 🔐 Безопасность

### Реализовано:
- ✅ **Хеширование паролей** - `password_hash()` и `password_verify()` (BCRYPT)
- ✅ **JWT токены** - для безопасной авторизации
- ✅ **Prepared Statements** - защита от SQL-инъекций
- ✅ **CORS заголовки** - контроль доступа
- ✅ **Валидация входных данных** - проверка на сервере и клиенте
- ✅ **Защита от XSS** - использование `htmlspecialchars()`
- ✅ **HTTPS-ready** - поддержка HTTPS

### Рекомендации для продакшена:
1. Измените `JWT_SECRET` в `config.php` на сложный ключ
2. Используйте HTTPS вместо HTTP
3. Установите secure флаг для cookies
4. Ограничьте доступ к папке `backend` в веб-конфиге
5. Регулярно обновляйте зависимости
6. Используйте `DEBUG_MODE = false` в продакшене

---

## 📊 Работа с API из JavaScript

### Базовый класс APIClient уже подготовлен

Все примеры ниже используют глобальный объект `api`:

```javascript
// GET запрос
const response = await api.get('/endpoint');
const data = await response.json();

// POST запрос
const response = await api.post('/endpoint', { key: 'value' });
const data = await response.json();

// PUT запрос
const response = await api.put('/endpoint', { key: 'value' });

// DELETE запрос
const response = await api.delete('/endpoint', { id: 123 });
```

### Обработка ошибок

```javascript
try {
  const response = await api.post('/login.php', {
    username: 'admin',
    password: 'admin123'
  });
  
  const data = await response.json();
  
  if (data.success) {
    console.log('Успешный вход:', data.user);
  } else {
    console.error('Ошибка:', data.error);
  }
} catch (error) {
  console.error('Ошибка сети:', error);
}
```

---

## 💾 Резервное копирование

### Автоматическое копирование
Каждый день в 23:00 автоматически создается резервная копия (требуется cron job).

### Ручное копирование
1. Откройте страницу "Резервная копия"
2. Нажмите "Создать копию"
3. Выберите формат (JSON или SQL)
4. Файл скачается автоматически

### Восстановление из копии
1. Откройте страницу "Резервная копия"
2. Выберите файл из списка
3. Нажмите "Восстановить"
4. Подтвердите действие

---

## 📱 Тестирование на мобильных устройствах

Для тестирования на других устройствах в локальной сети:

```bash
# Узнайте IP вашего компьютера
ipconfig getifaddr en0  # macOS
ifconfig | grep inet   # Linux

# Запустите сервер на всех интерфейсах
cd frontend
php -S 0.0.0.0:8000

# На мобильном откройте
# http://YOUR_IP:8000/login.html
```

---

## 🐛 Решение проблем

### Ошибка: "Access denied for user 'root'@'localhost'"
**Решение:** Проверьте пароль БД в `config.php`

### Ошибка: "database clinic_db not found"
**Решение:** Создайте БД: `mysql -u root -p -e "CREATE DATABASE clinic_db;"`

### Ошибка: "Cannot modify header information"
**Решение:** Убедитесь, что перед `<?php` нет пробелов или BOM

### CORS ошибки
**Решение:** Проверьте заголовки в `backend/api/.htaccess` и убедитесь, что Apache включен `mod_headers`

### Токен не сохраняется
**Решение:** Проверьте, что браузер разрешает localStorage (Настройки → Конфиденциальность)

---

## 📞 Поддержка

Если у вас возникли проблемы:
1. Проверьте консоль браузера (F12)
2. Проверьте логи PHP (`php -S localhost:8000` выводит ошибки)
3. Проверьте БД MySQL с помощью `mysql -u root -p clinic_db`
4. Создайте Issue на GitHub

---

## 🎉 Поздравляем!

Вы готовы к работе! 🚀

Дальнейшие шаги:
- ✅ Реализуйте расписание пациентов
- ✅ Добавьте управление заметками
- ✅ Доработайте админ-панель
- ✅ Настройте дополнительные функции

---

**Успехов в разработке!** 💪
