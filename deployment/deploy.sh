#!/bin/bash

# Deployment Script for WarungKu (Ubuntu/Debian)
# Run as root or with sudo

echo "Memulai proses instalasi dan deployment WarungKu..."

# 1. Update sistem
echo "Memperbarui paket sistem..."
apt-get update
apt-get upgrade -y

# 2. Instal Apache, MySQL, PHP, dan ekstensi yang diperlukan
echo "Menginstal Apache, MySQL, dan PHP..."
apt-get install -y apache2 mysql-server php libapache2-mod-php php-mysql php-cli php-curl php-json php-mbstring

# 3. Konfigurasi direktori web
echo "Mengatur direktori web..."
WEB_DIR="/var/www/html/uasproject"
mkdir -p $WEB_DIR

# (Diasumsikan script ini dijalankan di dalam root project yang akan dideploy)
cp -r ./* $WEB_DIR/

# Atur permission
chown -R www-data:www-data $WEB_DIR
chmod -R 755 $WEB_DIR
chmod -R 777 $WEB_DIR/backups # Pastikan folder backup bisa ditulisi
chmod -R 777 $WEB_DIR/logs # Pastikan folder logs bisa ditulisi

# 4. Setup Database
echo "Mengonfigurasi Database MySQL..."
# Peringatan: Gunakan password yang kuat di production!
mysql -e "CREATE DATABASE IF NOT EXISTS warungku_db;"
mysql -e "CREATE USER IF NOT EXISTS 'warungku_user'@'localhost' IDENTIFIED BY 'password_kuat';"
mysql -e "GRANT ALL PRIVILEGES ON warungku_db.* TO 'warungku_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Impor database awal
mysql -u warungku_user -ppassword_kuat warungku_db < $WEB_DIR/database.sql

# 5. Konfigurasi Apache Virtual Host (Opsional)
echo "Mengaktifkan mod_rewrite Apache..."
a2enmod rewrite
systemctl restart apache2

echo "========================================="
echo "Deployment selesai!"
echo "Silakan akses aplikasi Anda di: http://YOUR_SERVER_IP/uasproject"
echo "Jangan lupa untuk mengedit konfigurasi database di config/db.php jika menggunakan kredensial yang berbeda."
echo "========================================="
