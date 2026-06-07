<?php
session_start();

header('Content-Type: application/json');
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

require 'db.php';

$postToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (empty($postToken) || !hash_equals($sessionToken, $postToken)) {
    echo json_encode([
        "status" => "error",
        "message" => "Güvenlik doğrulaması başarısız (Geçersiz CSRF Token)."
    ]);
    exit;
}

$code = $_POST['code'] ?? '';

$stmt = $conn->prepare("SELECT name, confirmation_code FROM pharmacies");
$stmt->execute();

$pharmacy = null;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (password_verify($code, $row['confirmation_code'])) {
        $pharmacy = $row['name'];
        break;
    }
}

if ($pharmacy) {
    $_SESSION['pharmacy_verified'] = true;
    $_SESSION['pharmacy'] = $pharmacy;

    echo json_encode([
        "status" => "ok",
        "pharmacy" => $pharmacy
    ]);
} else {
    echo json_encode([
        "status" => "error"
    ]);
}
?>