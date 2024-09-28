<?php
$servername = "localhost";
$username = "lab";
$password = "Qet2003_$";
$dbname = "Payment";

try 
{
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Ошибка подключения: " . $conn->connect_error);
    }

} 
catch (Exception $e) 
{
    echo "<div style='color: black;'>Произошла ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}
?>