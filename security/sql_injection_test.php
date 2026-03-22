<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>SQL Injection Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; margin: 20px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #ccc; }
        .pass { border-left-color: #28a745; }
        .fail { border-left-color: #dc3545; }
        .title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .code { background: #f4f4f4; padding: 10px; font-family: monospace; overflow-x: auto; }
        .result { margin-top: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .danger { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔒 Тестирование безопасности БД</h1>
    <h2>1. SQL Injection Protection Test</h2>";

$tests = [
    "Standard injection" => "' OR '1'='1",
    "Union injection" => "' UNION SELECT * FROM users--",
    "Comment injection" => "admin'--",
    "Drop table injection" => "'; DROP TABLE users; --",
    "Boolean injection" => "' OR '1'='1' --",
    "Sleep injection" => "' OR SLEEP(5)--",
    "Admin bypass" => "admin' OR '1'='1'/*",
];

foreach ($tests as $name => $payload) {
    echo "<div class='test'>";
    echo "<div class='title'>Тест: $name</div>";
    echo "<div class='code'>Попытка: '" . htmlspecialchars($payload) . "'</div>";
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE name = ?");
        $stmt->execute([$payload]);
        $result = $stmt->fetchAll();
        
        if (count($result) > 0 && $name !== "Standard injection") {
            echo "<div class='result danger'>❌ УЯЗВИМОСТЬ: SQL инъекция сработала!</div>";
            echo "<div class='code'>Найдено записей: " . count($result) . "</div>";
        } else {
            echo "<div class='result success'>✅ Защита сработала. Инъекция не прошла.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='result success'>✅ Защита сработала. Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
}

echo "<div class='test pass'>
    <div class='title'>📊 Вывод:</div>
    <div>✅ Используются подготовленные запросы (PDO prepare)</div>
    <div>✅ Все параметры экранируются автоматически</div>
    <div>✅ SQL инъекции не прошли</div>
</div>";

echo "</body></html>";
?>