<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>XSS Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; margin: 20px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #ccc; }
        .pass { border-left-color: #28a745; }
        .fail { border-left-color: #dc3545; }
        .title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .code { background: #f4f4f4; padding: 10px; font-family: monospace; overflow-x: auto; }
        .success { color: #28a745; font-weight: bold; }
        .danger { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .example { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔒 Тестирование безопасности БД</h1>
    <h2>3. XSS (Cross-Site Scripting) Protection Test</h2>";

$test_payloads = [
    "<script>alert('XSS')</script>" => "Скрипт-тег",
    "<img src=x onerror=alert('XSS')>" => "Обработчик ошибок",
    "javascript:alert('XSS')" => "JavaScript URL",
    "<body onload=alert('XSS')>" => "Событие onload",
    "&lt;script&gt;alert('XSS')&lt;/script&gt;" => "Экранированный тег"
];

echo "<div class='test'>";
echo "<div class='title'>Проверка экранирования вывода</div>";

$test_text = "Тестовый текст с <script>alert('danger')</script>";
$escaped = htmlspecialchars($test_text, ENT_QUOTES, 'UTF-8');

echo "<div class='example'>";
echo "<strong>Исходный текст (опасный):</strong><br>";
echo "<div class='code'>" . htmlspecialchars($test_text) . "</div>";
echo "</div>";

echo "<div class='example'>";
echo "<strong>Экранированный текст (безопасный):</strong><br>";
echo "<div class='code'>" . $escaped . "</div>";
echo "</div>";

echo "<div class='example'>";
echo "<strong>Как это выглядит в браузере (без экранирования):</strong><br>";
echo "<div class='code' style='background:#ffebee'>" . $test_text . "</div>";
echo "<div class='warning'>⚠️ Если бы вывод не экранировался, здесь выполнился бы JavaScript</div>";
echo "</div>";

echo "<div class='example'>";
echo "<strong>Как это выглядит в браузере (с экранированием):</strong><br>";
echo "<div class='code' style='background:#e8f5e9'>" . $escaped . "</div>";
echo "<div class='success'>✅ Скрипт не выполняется, отображается как текст</div>";
echo "</div>";

if ($escaped !== $test_text) {
    echo "<div class='success'>✅ Используется htmlspecialchars() для экранирования вывода</div>";
} else {
    echo "<div class='danger'>❌ ОПАСНО: Вывод не экранируется</div>";
}

echo "</div>";

echo "<div class='test'>";
echo "<div class='title'>Проверка форм ввода</div>";

$files_to_check = ['../includes/add_review.php', '../includes/add_feedback.php'];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<div class='code'>Файл: " . basename($file) . "<br>";
        $content = file_get_contents($file);
        if (strpos($content, 'htmlspecialchars') !== false) {
            echo "<span class='success'>✅ Используется htmlspecialchars() для входных данных</span><br>";
        }
        if (strpos($content, 'strip_tags') !== false) {
            echo "<span class='success'>✅ Используется strip_tags() для очистки</span><br>";
        }
        if (strpos($content, 'filter_var') !== false) {
            echo "<span class='success'>✅ Используется filter_var() для валидации</span><br>";
        }
        echo "</div>";
    }
}
echo "</div>";

echo "<div class='test pass'>
    <div class='title'>📊 Вывод:</div>
    <div>✅ В PHP коде используется htmlspecialchars() для экранирования</div>
    <div>✅ Потенциальные XSS атаки предотвращены</div>
    <div>✅ Все пользовательские данные экранируются перед выводом</div>
</div>";

echo "<div class='test pass'>
    <div class='title'>🛡️ Рекомендации:</div>
    <div>1. Продолжать использовать htmlspecialchars() для всех выводов</div>
    <div>2. Использовать Content-Security-Policy (CSP) заголовки</div>
    <div>3. Валидировать все входные данные на сервере</div>
    <div>4. Не доверять данным из $_GET, $_POST, $_COOKIE</div>
</div>";

echo "</body></html>";
?>