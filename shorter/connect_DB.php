<?php

try {
    $pdo = new PDO('mysql:host=localhost;port=3308;dbname=test', 'root', 'root', array(
        PDO::ATTR_PERSISTENT => true
    ));
} catch (PDOException $e) {
    echo "Неудалось установить соединение с базой данных";
}
