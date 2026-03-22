<?php
session_start();
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$captcha_result = $num1 + $num2;
$_SESSION['captcha_result'] = $captcha_result;
?>
<div class="captcha-field">
    <label>Сколько будет <?= $num1 ?> + <?= $num2 ?>?</label>
    <input type="text" name="captcha" required placeholder="Введите ответ">
</div>