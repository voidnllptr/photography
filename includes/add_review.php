<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['captcha_result']) || (int)$_POST['captcha'] !== $_SESSION['captcha_result']) {
        header("Location: ../index.php?review_error=captcha");
        exit;
    }
    
    $review_text = htmlspecialchars(trim($_POST['review_text']));
    $rating = (int)$_POST['rating'];
    $user_name = htmlspecialchars(trim($_POST['name']));
    $user_email = htmlspecialchars(trim($_POST['email']));
    
    if (empty($review_text) || empty($user_name) || empty($user_email) || $rating < 1 || $rating > 5) {
        header("Location: ../index.php?review_error=validation");
        exit;
    }
    
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../index.php?review_error=email");
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user_email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user_id = $user['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?) RETURNING id");
            $stmt->execute([$user_name, $user_email]);
            $user_id = $stmt->fetchColumn();
        }
        
        $stmt = $pdo->prepare("SELECT id FROM review_statuses WHERE name = 'pending'");
        $stmt->execute();
        $status_id = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, review_text, rating, status_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $review_text, $rating, $status_id]);
        
        $pdo->commit();
        unset($_SESSION['captcha_result']);
        header("Location: ../index.php?review_success=1");
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: ../index.php?review_error=database");
    }
    exit;
}
?>