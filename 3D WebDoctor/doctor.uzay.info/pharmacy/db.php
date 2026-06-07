<?php
$servername = "localhost";
$dbname = "uzayinfo_healthapp";
$username = "uzayinfo_healthapp";
$password = "yumak345";

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Bağlantı hatası: " . $e->getMessage();
            die();
        }
?>
