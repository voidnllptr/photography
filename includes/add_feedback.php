<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $shooting_type = htmlspecialchars(trim($_POST['shooting_type']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    if (empty($name) || empty($email) || empty($message) || empty($shooting_type)) {
        header("Location: ../index.php?feedback_error=validation");
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../index.php?feedback_error=email");
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM request_statuses WHERE name = 'new'");
        $stmt->execute();
        $status_id = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO feedback_requests (name, email, phone, message, shooting_type, status_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $message, $shooting_type, $status_id]);
        
        header("Location: ../index.php?feedback_success=1");
        
    } catch (PDOException $e) {
        header("Location: ../index.php?feedback_error=database");
    }
    exit;
}
?>