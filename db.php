<?php
// submit_registration.php

// Подключение к базе данных
$servername = "localhost";
$username = "lab";
$password = "Qet2003_$";
$dbname = "Payment";

$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}