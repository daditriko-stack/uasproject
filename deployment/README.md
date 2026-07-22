# Panduan Deployment WarungKu ke VPS (Ubuntu/Debian)

Panduan ini akan membantu Anda mengkonfigurasi dan mendeploy aplikasi kasir web **WarungKu** ke Virtual Private Server (VPS) yang menjalankan Ubuntu atau Debian.

## Persyaratan
- VPS dengan sistem operasi Ubuntu 20.04/22.04 atau Debian 11/12.
- Akses `root` atau pengguna dengan hak `sudo`.
- Domain atau IP Publik.

## Langkah 1: Persiapan Server

Akses server Anda menggunakan SSH:
```bash
ssh root@YOUR_SERVER_IP
```

Unduh atau pindahkan seluruh file proyek ini ke server Anda, misalnya ke direktori `/home/user/uasproject`.

## Langkah 2: Menggunakan Script Deployment Otomatis

Kami telah menyediakan script bash sederhana (`deploy.sh`) untuk mengotomatiskan proses instalasi LAMP stack (Linux, Apache, MySQL, PHP) dan mengonfigurasi proyek.

1. Masuk ke direktori proyek:
   ```bash
   cd /home/user/uasproject/deployment
   ```

2. Berikan izin eksekusi pada script:
   ```bash
   chmod +x deploy.sh
   ```

3. Jalankan script (pastikan Anda menggunakan sudo atau root):
   ```bash
   ./deploy.sh
   ```

Script ini akan melakukan hal berikut:
- Mengupdate repository sistem.
- Menginstal **Apache**, **MySQL**, dan **PHP** beserta ekstensinya.
- Menyalin file proyek ke `/var/www/html/uasproject`.
- Menyesuaikan izin (*permissions*) untuk folder `backups/` dan `logs/`.
- Membuat database `warungku_db` dan pengguna MySQL.
- Mengimpor struktur tabel dari `database.sql`.

## Langkah 3: Konfigurasi Lanjutan (Manual)

### 1. Konfigurasi Database

Jika Anda ingin mengubah kredensial database (disarankan untuk *production*), edit file `config/db.php`:

```bash
nano /var/www/html/uasproject/config/db.php
```
Sesuaikan parameter koneksi `DB_USER` dan `DB_PASS`.

### 2. Mengaktifkan Pengiriman Email (SMTP)

Secara default, aplikasi menggunakan *simulated email* yang dicatat di `logs/mail.log`. Untuk mengirim email sesungguhnya (Verifikasi Akun, Lupa Password, Invoice), edit `config/mail_settings.php` dan isi dengan kredensial SMTP Anda (misal: Gmail, SendGrid, Mailtrap).

```php
// config/mail_settings.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'email_anda@gmail.com');
define('SMTP_PASS', 'password_aplikasi_anda');
define('SMTP_PORT', 587);
```

### 3. Keamanan Tambahan

- **SSL/HTTPS:** Sangat disarankan untuk memasang SSL (misalnya dengan Let's Encrypt / Certbot) agar koneksi aman (HTTPS).
- **Hide Errors:** Pastikan untuk mematikan *display errors* PHP di lingkungan *production* dengan mengedit `php.ini`.

## Selesai!
Aplikasi WarungKu Anda sekarang dapat diakses melalui browser:
`http://YOUR_SERVER_IP/uasproject`

Gunakan kredensial default untuk masuk:
- **Admin**: `admin` / `admin123`
- **Kasir**: `kasir` / `kasir123`
