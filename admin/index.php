<?php
session_start();
require_once '../config/database.php';

$admin_password = 'admin123';

if (!isset($_SESSION['admin_logged']) && (!isset($_POST['password']) || $_POST['password'] !== $admin_password)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Админ-панель | Вход</title>
        <style>
            body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f5f5f5; margin: 0; }
            form { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
            input { padding: 10px; margin: 10px 0; width: 250px; border: 1px solid #ddd; border-radius: 4px; }
            button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
            button:hover { background: #45a049; }
            h2 { margin-bottom: 20px; color: #333; }
        </style>
    </head>
    <body>
        <form method="POST">
            <h2>🔐 Вход в админ-панель</h2>
            <input type="password" name="password" placeholder="Введите пароль" required>
            <br>
            <button type="submit">Войти</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

$_SESSION['admin_logged'] = true;

if (isset($_POST['action']) && isset($_POST['item_id']) && isset($_POST['item_type'])) {
    $item_id = (int)$_POST['item_id'];
    $item_type = $_POST['item_type'];
    $status_name = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    
    try {
        $pdo->beginTransaction();
        
        if ($item_type === 'review') {
            $stmt = $pdo->prepare("SELECT id FROM review_statuses WHERE name = ?");
            $stmt->execute([$status_name]);
            $status_id = $stmt->fetchColumn();
            $stmt = $pdo->prepare("UPDATE reviews SET status_id = ?, moderated_at = NOW() WHERE id = ?");
            $stmt->execute([$status_id, $item_id]);
            $message = "Отзыв #$item_id " . ($status_name === 'approved' ? "одобрен" : "отклонен");
        } elseif ($item_type === 'feedback') {
            $stmt = $pdo->prepare("SELECT id FROM request_statuses WHERE name = ?");
            $stmt->execute([$status_name === 'approved' ? 'completed' : 'rejected']);
            $status_id = $stmt->fetchColumn();
            $stmt = $pdo->prepare("UPDATE feedback_requests SET status_id = ?, processed_at = NOW() WHERE id = ?");
            $stmt->execute([$status_id, $item_id]);
            $message = "Заявка #$item_id " . ($status_name === 'approved' ? "отмечена как выполненная" : "отклонена");
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Ошибка: " . $e->getMessage();
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$reviews = $pdo->query("
    SELECT r.*, rs.name as status_name, u.name as user_name, u.email 
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    JOIN review_statuses rs ON rs.id = r.status_id
    ORDER BY r.created_at DESC
")->fetchAll();

$feedbacks = $pdo->query("
    SELECT f.*, rs.name as status_name 
    FROM feedback_requests f
    JOIN request_statuses rs ON rs.id = f.status_id
    ORDER BY f.created_at DESC
")->fetchAll();

$reviews_stats = $pdo->query("
    SELECT rs.name, COUNT(*) as count 
    FROM reviews r
    JOIN review_statuses rs ON rs.id = r.status_id
    GROUP BY rs.name
")->fetchAll();

$feedbacks_stats = $pdo->query("
    SELECT rs.name, COUNT(*) as count 
    FROM feedback_requests f
    JOIN request_statuses rs ON rs.id = f.status_id
    GROUP BY rs.name
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель | Управление</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab { padding: 10px 20px; cursor: pointer; background: #e9ecef; border-radius: 5px 5px 0 0; text-decoration: none; color: #333; }
        .tab.active { background: #4CAF50; color: white; }
        .stats { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stats span { margin: 0 15px; padding: 5px 10px; background: #e9ecef; border-radius: 20px; display: inline-block; margin-bottom: 5px; }
        .item { background: white; margin: 15px 0; padding: 20px; border-radius: 8px; border-left: 4px solid #ccc; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .item.pending { border-left-color: #ffc107; }
        .item.new { border-left-color: #17a2b8; }
        .item.completed { border-left-color: #28a745; }
        .item.rejected { border-left-color: #dc3545; }
        .item-meta { color: #666; font-size: 12px; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .rating { color: #ffc107; font-size: 18px; margin: 10px 0; }
        .item-text { font-size: 16px; line-height: 1.5; margin: 15px 0; color: #333; }
        button { padding: 8px 16px; margin: 5px 5px 5px 0; cursor: pointer; border: none; border-radius: 4px; font-size: 14px; }
        .approve { background: #28a745; color: white; }
        .approve:hover { background: #218838; }
        .reject { background: #dc3545; color: white; }
        .reject:hover { background: #c82333; }
        .message { padding: 12px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; float: right; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-new { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .logout { float: right; background: #6c757d; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; }
        .logout:hover { background: #5a6268; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .section-title { font-size: 20px; margin: 20px 0 10px 0; color: #333; }
    </style>
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>📸 Админ-панель 
            <a href="?logout=1" class="logout" onclick="return confirm('Выйти?')">Выйти</a>
        </h1>
        
        <div class="tabs">
            <a href="#" class="tab active" onclick="showTab('reviews-tab')">📝 Отзывы</a>
            <a href="#" class="tab" onclick="showTab('feedbacks-tab')">📋 Заявки</a>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        
        <div id="reviews-tab" class="tab-content active">
            <div class="stats">
                <strong>📊 Статистика отзывов:</strong>
                <?php foreach ($reviews_stats as $stat): ?>
                    <span><?= $stat['name'] ?>: <?= $stat['count'] ?></span>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($reviews)): ?>
                <p style="text-align: center; padding: 50px; background: white; border-radius: 8px;">Нет отзывов</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="item <?= $review['status_name'] ?>">
                        <div class="item-meta">
                            <strong>#<?= $review['id'] ?></strong> | 
                            👤 <?= htmlspecialchars($review['user_name'] ?? 'Аноним') ?> | 
                            📧 <?= htmlspecialchars($review['email'] ?? '—') ?> | 
                            📅 <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                            <?php if ($review['moderated_at']): ?>
                                | ✅ <?= date('d.m.Y H:i', strtotime($review['moderated_at'])) ?>
                            <?php endif; ?>
                            <span class="status-badge status-<?= $review['status_name'] ?>">
                                <?= $review['status_name'] === 'pending' ? '⏳ Ожидает' : ($review['status_name'] === 'approved' ? '✅ Одобрен' : '❌ Отклонен') ?>
                            </span>
                        </div>
                        <div class="rating">
                            <?= str_repeat("⭐", $review['rating']) ?>
                        </div>
                        <div class="item-text">
                            "<?= htmlspecialchars($review['review_text']) ?>"
                        </div>
                        
                        <?php if ($review['status_name'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="item_id" value="<?= $review['id'] ?>">
                                <input type="hidden" name="item_type" value="review">
                                <button type="submit" name="action" value="approve" class="approve">✅ Одобрить</button>
                                <button type="submit" name="action" value="reject" class="reject">❌ Отклонить</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div id="feedbacks-tab" class="tab-content">
            <div class="stats">
                <strong>📊 Статистика заявок:</strong>
                <?php foreach ($feedbacks_stats as $stat): ?>
                    <span><?= $stat['name'] ?>: <?= $stat['count'] ?></span>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($feedbacks)): ?>
                <p style="text-align: center; padding: 50px; background: white; border-radius: 8px;">Нет заявок</p>
            <?php else: ?>
                <?php foreach ($feedbacks as $feedback): ?>
                    <div class="item <?= $feedback['status_name'] ?>">
                        <div class="item-meta">
                            <strong>#<?= $feedback['id'] ?></strong> | 
                            👤 <?= htmlspecialchars($feedback['name']) ?> | 
                            📧 <?= htmlspecialchars($feedback['email']) ?> | 
                            <?php if ($feedback['phone']): ?>📞 <?= htmlspecialchars($feedback['phone']) ?> | <?php endif; ?>
                            📅 <?= date('d.m.Y H:i', strtotime($feedback['created_at'])) ?>
                            <?php if ($feedback['processed_at']): ?>
                                | ⚙️ <?= date('d.m.Y H:i', strtotime($feedback['processed_at'])) ?>
                            <?php endif; ?>
                            <span class="status-badge status-<?= $feedback['status_name'] ?>">
                                <?= $feedback['status_name'] === 'new' ? '🆕 Новая' : ($feedback['status_name'] === 'completed' ? '✅ Выполнена' : '❌ Отклонена') ?>
                            </span>
                        </div>
                        <div class="item-text">
                            <strong>📷 Тип съемки:</strong> <?= htmlspecialchars($feedback['shooting_type']) ?>
                        </div>
                        <div class="item-text">
                            <strong>💬 Сообщение:</strong><br>
                            "<?= htmlspecialchars($feedback['message']) ?>"
                        </div>
                        
                        <?php if ($feedback['status_name'] === 'new'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="item_id" value="<?= $feedback['id'] ?>">
                                <input type="hidden" name="item_type" value="feedback">
                                <button type="submit" name="action" value="approve" class="approve">✅ Отметить выполненной</button>
                                <button type="submit" name="action" value="reject" class="reject">❌ Отклонить</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>