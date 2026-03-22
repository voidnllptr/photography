<?php
require_once 'config/database.php';

$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.status_id = (SELECT id FROM review_statuses WHERE name = 'approved')
    ORDER BY r.created_at DESC
");
$stmt->execute();
$approved_reviews = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT * FROM albums 
    ORDER BY created_at DESC
");
$stmt->execute();
$albums = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Портфолио фотографа | Иванна Иванова</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h1>Иванна Иванова</h1>
    <p>Профессиональная фотосъемка в Москве</p>
</header>

<main>
    <section class="about">
        <h2>Обо мне</h2>
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRcK1F2-aOQRLYcLLrk0SO-EgoZnLpLKVTk3A&s" alt="Фотограф за работой" class="about-img">
        <p>Привет! Я Иванна, профессиональный фотограф с 10-летним опытом. Специализируюсь на портретной, свадебной и семейной съемке. Моя цель — запечатлеть ваши искренние эмоции и создать уникальные кадры, которые будут радовать вас долгие годы.</p>
    </section>

    <section class="portfolio">
        <h2>Портфолио</h2>
        
        <?php if (empty($albums)): ?>
            <p>Альбомы пока не добавлены</p>
        <?php else: ?>
            <?php foreach ($albums as $album): ?>
                <div class="album">
                    <h3><?= htmlspecialchars($album['title']) ?></h3>
                    <p><?= htmlspecialchars($album['description']) ?></p>
                    <div class="gallery">
                        <?php

                        $stmt = $pdo->prepare("
                            SELECT * FROM photos 
                            WHERE album_id = ? 
                            ORDER BY sort_order, created_at
                            LIMIT 6
                        ");
                        $stmt->execute([$album['id']]);
                        $photos = $stmt->fetchAll();
                        
                        foreach ($photos as $photo): ?>
                            <img src="<?= htmlspecialchars($photo['image_url']) ?>" alt="<?= htmlspecialchars($photo['title'] ?? 'Фото') ?>">
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="gallery">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQau-wfWLn0wfxaMpChW3S-izGypYg9z-r5VA&s" alt="Свадьба 1">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ4q1c5NFCaS9XdEuHqHhUiB8OijcO44UQhSg&s" alt="Семья на природе">
            <img src="https://fullmedia.ru/500/uploaded/20092b46/5cbb7131/d56f32ed/7b2f622b.jpg" alt="Портрет девушки">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQMI-rR6lJHJlUw8Z422aOU68lXzynYmTfByg&s" alt="Детская съемка">
            <img src="https://1gai.ru/uploads/posts/2025-12/1766479405_prompty-dlja-chatgpt-dlja-professionalnyh-foto-1gai.jpg" alt="Love Story">
            <img src="https://dailystudio.ru/wp-content/uploads/2019/01/434.jpg" alt="Студийный портрет">
        </div>
    </section>

    <section class="reviews">
        <h2>Отзывы клиентов</h2>
        
        <?php if (empty($approved_reviews)): ?>
            <p>Пока нет отзывов. Станьте первым!</p>
        <?php else: ?>
            <?php foreach ($approved_reviews as $review): ?>
                <div class="review">
                    <p>"<?= htmlspecialchars($review['review_text']) ?>"</p>
                    <strong>- <?= htmlspecialchars($review['user_name'] ?? 'Аноним') ?></strong>
                    <div class="rating">Оценка: <?= str_repeat("⭐", $review['rating']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="review-form-section">
        <h2>Оставить отзыв</h2>
        
        <?php if (isset($_GET['review_success'])): ?>
            <div class="success-message">✅ Спасибо за отзыв! Он будет опубликован после модерации.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['review_error'])): ?>
            <div class="error-message">❌ Ошибка при отправке отзыва. Попробуйте еще раз.</div>
        <?php endif; ?>
        
        <form action="includes/add_review.php" method="POST" class="review-form">
            <div class="form-group">
                <label>Ваше имя:</label>
                <input type="text" name="name" required placeholder="Иван Иванов">
            </div>
            
            <div class="form-group">
                <label>Ваш email:</label>
                <input type="email" name="email" required placeholder="ivan@example.com">
            </div>
            
            <div class="form-group">
                <label>Оценка:</label>
                <select name="rating" required>
                    <option value="5">5 - Отлично ⭐⭐⭐⭐⭐</option>
                    <option value="4">4 - Хорошо ⭐⭐⭐⭐</option>
                    <option value="3">3 - Нормально ⭐⭐⭐</option>
                    <option value="2">2 - Плохо ⭐⭐</option>
                    <option value="1">1 - Ужасно ⭐</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Ваш отзыв:</label>
                <textarea name="review_text" rows="5" required placeholder="Поделитесь впечатлениями..."></textarea>
            </div>
            
            <?php include 'includes/captcha.php'; ?>
            
            <button type="submit">Отправить отзыв</button>
        </form>
    </section>

    <section class="feedback-form-section">
        <h2>Заказать съемку</h2>
        
        <?php if (isset($_GET['feedback_success'])): ?>
            <div class="success-message">✅ Спасибо за заявку! Я свяжусь с вами в ближайшее время.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['feedback_error'])): ?>
            <div class="error-message">❌ Ошибка при отправке заявки. Попробуйте еще раз.</div>
        <?php endif; ?>
        
        <form action="includes/add_feedback.php" method="POST" class="feedback-form">
            <div class="form-group">
                <label>Ваше имя:</label>
                <input type="text" name="name" required placeholder="Иван Иванов">
            </div>
            
            <div class="form-group">
                <label>Ваш email:</label>
                <input type="email" name="email" required placeholder="ivan@example.com">
            </div>
            
            <div class="form-group">
                <label>Телефон:</label>
                <input type="tel" name="phone" placeholder="+7 (999) 123-45-67">
            </div>
            
            <div class="form-group">
                <label>Тип съемки:</label>
                <select name="shooting_type" required>
                    <option value="">Выберите тип</option>
                    <option value="portrait">Портретная</option>
                    <option value="wedding">Свадебная</option>
                    <option value="family">Семейная</option>
                    <option value="children">Детская</option>
                    <option value="love_story">Love Story</option>
                    <option value="product">Предметная</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Ваши пожелания:</label>
                <textarea name="message" rows="5" required placeholder="Расскажите о ваших идеях..."></textarea>
            </div>
            
            <button type="submit">Отправить заявку</button>
        </form>
    </section>

    <section class="contacts">
        <h2>Контакты</h2>
        <p>📞 Телефон: +7 (999) 123-45-67</p>
        <p>📧 Email: ivanna.i@mail.com</p>
        <p>📍 Город: Москва</p>
        <a href="#" class="btn">Заказать съемку</a>
    </section>
</main>

<footer>
    <p>© 2026 Иванна Иванова. Все права защищены.</p>
</footer>

<script src="script.js"></script>
</body>
</html>