# 🛠️ Установка и развертывание на сервере

## Требования

- PHP 7.4 или выше
- MySQL 5.7 или MariaDB 10.3+
- Web-сервер (Apache или Nginx)
- Composer (опционально)
- SSH доступ к серверу

## Установка на VPS/Dedicated Server

### 1. Подключение к серверу

```bash
ssh user@your_server_ip
```

### 2. Установка зависимостей

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y apache2 php php-mysql php-mbstring php-xml
sudo systemctl start apache2
sudo systemctl enable apache2
```

**CentOS/RHEL:**
```bash
sudo yum install -y httpd php php-mysql php-mbstring php-xml
sudo systemctl start httpd
sudo systemctl enable httpd
```

### 3. Установка MySQL (если еще не установлен)

**Ubuntu/Debian:**
```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

**CentOS/RHEL:**
```bash
sudo yum install -y mysql-server
sudo systemctl start mysqld
mysql_secure_installation
```

### 4. Клонирование проекта

```bash
cd /var/www
sudo git clone https://github.com/toporkayt-dev/clinic-schedule-system.git
cd clinic-schedule-system
```

### 5. Настройка прав доступа

```bash
sudo chown -R www-data:www-data /var/www/clinic-schedule-system
sudo chmod -R 755 /var/www/clinic-schedule-system
sudo chmod -R 777 /var/www/clinic-schedule-system/backups
sudo chmod -R 777 /var/www/clinic-schedule-system/logs
```

### 6. Создание конфигурации

```bash
cp config.php.example config.php
sudo nano config.php

# Отредактируйте данные БД:
# DB_HOST, DB_USER, DB_PASS, DB_NAME, JWT_SECRET
```

### 7. Создание БД и таблиц

```bash
mysql -u root -p < backend/sql/schema.sql
```

### 8. Настройка Apache VirtualHost

**Создайте конфиг:**
```bash
sudo nano /etc/apache2/sites-available/clinic.conf
```

**Содержимое конфига:**
```apache
<VirtualHost *:80>
    ServerName clinic.yourdomain.com
    ServerAlias www.clinic.yourdomain.com
    
    DocumentRoot /var/www/clinic-schedule-system/frontend
    
    <Directory /var/www/clinic-schedule-system/frontend>
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory /var/www/clinic-schedule-system/backend>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Логи
    ErrorLog ${APACHE_LOG_DIR}/clinic_error.log
    CustomLog ${APACHE_LOG_DIR}/clinic_access.log combined
    
    # Сжатие
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>
</VirtualHost>
```

**Включите сайт:**
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2ensite clinic.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### 9. Настройка SSL (HTTPS)

**Установите Certbot:**
```bash
sudo apt install -y certbot python3-certbot-apache
```

**Получите сертификат:**
```bash
sudo certbot --apache -d clinic.yourdomain.com -d www.clinic.yourdomain.com
```

### 10. Настройка Firewall

```bash
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

---

## Настройка для Nginx

### 1. Установка Nginx

```bash
sudo apt install -y nginx php-fpm
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 2. Конфигурация Nginx

**Создайте конфиг:**
```bash
sudo nano /etc/nginx/sites-available/clinic
```

**Содержимое:**
```nginx
server {
    listen 80;
    server_name clinic.yourdomain.com www.clinic.yourdomain.com;
    
    root /var/www/clinic-schedule-system/frontend;
    index index.html index.php;
    
    # Логи
    access_log /var/log/nginx/clinic_access.log;
    error_log /var/log/nginx/clinic_error.log;
    
    # SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # PHP API
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # Защита конфиг файлов
    location ~ /\.ht {
        deny all;
    }
    
    location ~ /config\.php {
        deny all;
    }
}
```

**Включите сайт:**
```bash
sudo ln -s /etc/nginx/sites-available/clinic /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## Настройка автоматического резервного копирования

### Создайте скрипт резервной копии

```bash
sudo nano /usr/local/bin/clinic_backup.sh
```

**Содержимое:**
```bash
#!/bin/bash

BACKUP_DIR="/var/www/clinic-schedule-system/backups"
DB_NAME="clinic_db"
DB_USER="root"
DB_PASS="password"
RETENTION_DAYS=30

# Создание резервной копии
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql

# Удаление старых файлов
find $BACKUP_DIR -name "backup_*.sql" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $(date)" >> /var/log/clinic_backup.log
```

**Сделайте исполняемым:**
```bash
sudo chmod +x /usr/local/bin/clinic_backup.sh
```

### Добавьте в cron

```bash
sudo crontab -e
```

**Добавьте строку (ежедневно в 23:00):**
```cron
0 23 * * * /usr/local/bin/clinic_backup.sh
```

---

## Мониторинг и логирование

### Проверка логов Apache

```bash
# Ошибки
sudo tail -f /var/log/apache2/clinic_error.log

# Доступ
sudo tail -f /var/log/apache2/clinic_access.log
```

### Проверка логов PHP-FPM

```bash
sudo tail -f /var/log/php-fpm.log
```

### Проверка логов MySQL

```bash
sudo tail -f /var/log/mysql/error.log
```

---

## Резервное копирование БД вручную

### Экспорт БД

```bash
mysqldump -u root -p clinic_db > backup_$(date +%Y%m%d).sql
```

### Импорт БД

```bash
mysql -u root -p clinic_db < backup_20260908.sql
```

---

## Оптимизация и безопасность

### Оптимизация MySQL

```bash
# Оптимизация таблиц
mysql -u root -p -e "OPTIMIZE TABLE clinic_db.*;"

# Проверка целостности
mysql -u root -p -e "CHECK TABLE clinic_db.*;"
```

### Ограничение доступа к backend

**В Apache (.htaccess):**
```apache
<Directory /var/www/clinic-schedule-system/backend>
    <FilesMatch "\.(php|sql|env)$">
        Require all granted
    </FilesMatch>
</Directory>
```

### Обновление PHP

```bash
sudo apt update
sudo apt upgrade php php-mysql php-mbstring
sudo systemctl restart php-fpm
```

---

## Проверка здоровья системы

```bash
#!/bin/bash

echo "=== PHP Version ==="
php -v

echo "\n=== MySQL Status ==="
sudo systemctl status mysql

echo "\n=== Apache Status ==="
sudo systemctl status apache2

echo "\n=== Disk Usage ==="
df -h

echo "\n=== Database Tables ==="
mysql -u root -p -e "SHOW TABLES FROM clinic_db;"
```

---

## Часто встречающиеся проблемы

### Problem: 404 при обращении к API
**Solution:** Проверьте, что mod_rewrite включен: `a2enmod rewrite`

### Problem: Permission denied при доступе к файлам
**Solution:** `sudo chown -R www-data:www-data /var/www/clinic-schedule-system`

### Problem: MySQL connection error
**Solution:** Проверьте параметры БД в config.php и статус MySQL

### Problem: Token expired быстро
**Solution:** Проверьте время сервера: `date` и отрегулируйте `TOKEN_EXPIRY` в config.php

---

**Установка завершена!** 🎉
