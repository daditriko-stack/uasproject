<?php
// File: config/env.php
// Parser .env sederhana untuk PHP Native tanpa Composer.
// Di-include sekali dari db.php sehingga semua file yang require db.php otomatis kebagian.

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Lewati baris komentar
        if (strpos(trim($line), '#') === 0) continue;
        // Butuh format KEY=VALUE
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Buang tanda kutip jika ada: "nilai" atau 'nilai'
        if (preg_match('/^"(.*)"$/s', $value, $m) || preg_match("/^'(.*)'$/s", $value, $m)) {
            $value = $m[1];
        }

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Muat .env secara otomatis — letaknya satu folder di atas config/
loadEnv(__DIR__ . '/../.env');
?>
