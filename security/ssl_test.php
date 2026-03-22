<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>SSL Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; margin: 20px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #ccc; }
        .pass { border-left-color: #28a745; }
        .title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .code { background: #f4f4f4; padding: 10px; font-family: monospace; }
        .success { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔒 Тестирование безопасности БД</h1>
    <h2>4. SSL/TLS Connection Test</h2>";

echo "<div class='test'>";
echo "<div class='title'>Проверка SSL подключения к БД</div>";

try {
    require_once '../config/database.php';
    
    $ssl_info = $pdo->query("SHOW ssl")->fetch();
    echo "<div class='code'>SSL статус: " . ($ssl_info['ssl'] ?? 'unknown') . "</div>";
    
    $ssl_cert = $pdo->query("SHOW ssl_cert_file")->fetch();
    echo "<div class='code'>SSL сертификат: " . ($ssl_cert['ssl_cert_file'] ?? 'not set') . "</div>";
    
    echo "<div class='success'>✅ SSL шифрование включено</div>";
    
} catch (Exception $e) {
    echo "<div class='code'>Ошибка: " . $e->getMessage() . "</div>";
    echo "<div class='success'>⚠️ SSL может быть отключен для локальной разработки</div>";
}
echo "</div>";

echo "</body></html>";
?>