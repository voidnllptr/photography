<?php

$host = 'localhost';
$port = '54321';
$dbname = 'photography';
$user = 'aleph';
$password = 'ily_aleph';

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    
    $pdo->exec("SET NAMES 'UTF8'");
    $pdo->exec("SET client_encoding TO 'UTF8'");
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage());
        return false;
    }
}

function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function insertAndGetId($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt ? $pdo->lastInsertId() : false;
}
?>