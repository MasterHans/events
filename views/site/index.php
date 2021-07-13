<?php

/* @var $this yii\web\View */

$this->title = 'Service Locator';
?>
    <div class="site-index">
        <div class="jumbotron">
            <h1>Сервис Локатор</h1>
        </div>
    </div>
<h2>Содержимое сессий ключ = primary-cart</h2>
<?php \yii\helpers\VarDumper::dump($_SESSION['primary-cart'], 10, true);?>