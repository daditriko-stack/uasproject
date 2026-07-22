<?php
// File: config/mailer.php
// Fungsi pengiriman email.
// - Jika RESEND_API_KEY tersedia di .env, email dikirim via Resend API (sungguhan).
// - Jika tidak ada API key (mode lokal/dev), email disimulasikan ke file log.

function sendEmail($to, $subject, $htmlBody) {
    $apiKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '');
    
    // --- MODE RESEND API (jika API key tersedia & bukan placeholder) ---
    if (!empty($apiKey) && $apiKey !== 'your_resend_api_key_here') {
        return _sendViaResend($to, $subject, $htmlBody, $apiKey);
    }
    
    // --- MODE SIMULASI / LOG (untuk development lokal) ---
    return _sendToLog($to, $subject, $htmlBody);
}

function _sendViaResend($to, $subject, $htmlBody, $apiKey) {
    $payload = json_encode([
        'from'    => 'WarungKu <onboarding@resend.dev>', // Ganti dengan domain terverifikasi Anda
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $htmlBody,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Catat hasil ke log untuk keperluan debugging
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    $logLine  = "[" . date('Y-m-d H:i:s') . "] [RESEND API] To: $to | Subject: $subject | HTTP: $httpCode";
    if ($curlError)  $logLine .= " | cURL Error: $curlError";
    if ($response)   $logLine .= " | Response: " . $response;
    file_put_contents($logDir . 'mail.log', $logLine . "\n", FILE_APPEND);

    return ($httpCode === 200 || $httpCode === 201);
}

function _sendToLog($to, $subject, $htmlBody) {
    // Mode simulasi: tulis isi email ke file log
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);

    $timestamp = date('Y-m-d H:i:s');
    $logContent  = "========================================\n";
    $logContent .= "Time: $timestamp\n";
    $logContent .= "To: $to\n";
    $logContent .= "Subject: $subject\n";
    $logContent .= "Body: \n$htmlBody\n";
    $logContent .= "========================================\n\n";

    file_put_contents($logDir . 'mail.log', $logContent, FILE_APPEND);
    return true;
}

/**
 * Fungsi helper untuk membangun template email HTML WarungKu.
 * @param string $title   Judul di dalam email
 * @param string $content Isi HTML di dalam email
 */
function buildEmailTemplate($title, $content) {
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;'>
        <h2 style='color: #10B981; text-align: center;'>$title</h2>
        $content
        <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 2rem 0;'>
        <p style='font-size: 0.8rem; color: #9ca3af; text-align: center;'>WarungKu Kelontong Modern</p>
    </div>";
}

/**
 * Helper: ambil base URL dari .env (APP_URL) atau deteksi otomatis.
 * Ini penting agar link di email bisa dibuka dari HP/device lain.
 */
function getAppUrl() {
    // Prioritaskan domain/host dari browser yang sedang digunakan pengguna
    if (!empty($_SERVER['HTTP_HOST'])) {
        $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return $proto . '://' . $_SERVER['HTTP_HOST'];
    }
    
    // Fallback jika dipanggil via CLI / background script
    $envUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
    if (!empty($envUrl)) {
        return rtrim($envUrl, '/');
    }
    
    return 'http://localhost';
}

/**
 * Fungsi helper khusus untuk email verifikasi.
 */
function sendVerificationEmail($to, $name, $token) {
    $verifyLink = getAppUrl() . base_url('auth/verify-email.php?token=' . $token);

    $content = "
        <p>Halo <strong>" . htmlspecialchars($name) . "</strong>,</p>
        <p>Terima kasih telah mendaftar di WarungKu. Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda (berlaku selama <strong>1 jam</strong>):</p>
        <div style='text-align: center; margin: 2rem 0;'>
            <a href='$verifyLink' style='background: #10B981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Verifikasi Email Saya</a>
        </div>
        <p style='font-size: 0.9rem; color: #6b7280;'>Jika tombol di atas tidak berfungsi, salin dan tempel tautan berikut ke browser Anda:</p>
        <p style='font-size: 0.9rem; color: #10B981; word-break: break-all;'>$verifyLink</p>
        <p style='font-size: 0.9rem; color: #6b7280;'>Jika Anda tidak merasa mendaftar, abaikan email ini.</p>";

    $html = buildEmailTemplate("Verifikasi Akun WarungKu", $content);
    return sendEmail($to, "Verifikasi Akun WarungKu Anda", $html);
}
