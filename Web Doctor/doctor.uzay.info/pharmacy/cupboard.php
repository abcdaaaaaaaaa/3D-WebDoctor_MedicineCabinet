<?php
session_start();
require 'db.php';

$conn->exec("SET NAMES 'utf8'");
$conn->exec("SET CHARACTER SET utf8");
$conn->exec("SET COLLATION_CONNECTION = 'utf8_general_ci'");

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $password = password_hash($_POST['confirmation'], PASSWORD_DEFAULT);
    $order = $_POST['order'];

    $stmt = $conn->prepare("INSERT INTO pharmacies (name, address, confirmation_code, medicine_order) VALUES (:name, :address, :confirmation_code, :medicine_order)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':confirmation_code', $password);
    $stmt->bindParam(':medicine_order', $order);

    if ($stmt->execute()) {
        header('Location: index.php');
        exit();
    } else {
        $error_message = "Kayıt sırasında bir hata oluştu.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medikal İlaç Dolabı Kayıt Sistemi</title>
    <link rel="shortcut icon" href="https://doctor.uzay.info/images/doctor.ico">
    <link rel="stylesheet" href="https://doctor.uzay.info/loginstyle.css">
    <link rel="stylesheet" href="../pharmacy/style.css">
    <link rel="stylesheet" href="../pharmacy/setspace.css">
    <style>.form-container { max-width: 450px; }</style>
</head>
<body>
    <div class="simulator" id="simulator">
        <div class="cabinet" id="cabinet">
            <div class="shelf" data-shelf="1"></div>
            <div class="shelf" data-shelf="2"></div>
            <div class="shelf" data-shelf="3"></div>
            <div class="shelf" data-shelf="4"></div>
            <div class="left"></div>
            <div class="right"></div>
        </div>

        <div class="box-container">
            <div class="box" draggable="true" data-box="1">
                <div class="face front">Ibuprofen</div>
                <div class="face back">Ibuprofen</div>
                <div class="face left">Ibuprofen</div>
                <div class="face right">Ibuprofen</div>
                <div class="face top">Ibuprofen</div>
                <div class="face bottom">Ibuprofen</div>
            </div>
            <div class="box" draggable="true" data-box="2">
                <div class="face front">Paracetamol</div>
                <div class="face back">Paracetamol</div>
                <div class="face left">Paracetamol</div>
                <div class="face right">Paracetamol</div>
                <div class="face top">Paracetamol</div>
                <div class="face bottom">Paracetamol</div>
            </div>
            <div class="box" draggable="true" data-box="3">
                <div class="face front">Gaviscon</div>
                <div class="face back">Gaviscon</div>
                <div class="face left">Gaviscon</div>
                <div class="face right">Gaviscon</div>
                <div class="face top">Gaviscon</div>
                <div class="face bottom">Gaviscon</div>
            </div>
            <div class="box" draggable="true" data-box="4">
                <div class="face front">Cetirizine</div>
                <div class="face back">Cetirizine</div>
                <div class="face left">Cetirizine</div>
                <div class="face right">Cetirizine</div>
                <div class="face top">Cetirizine</div>
                <div class="face bottom">Cetirizine</div>
            </div>
        </div>
    </div>
    
    <div class="form-container">
        <h2>Kayıt Ol</h2>
        <form method="POST" action="" id="registerForm">
            <label for="name">Eczane Adı:</label>
            <input type="text" id="name" name="name" required><br>
            
            <label for="address">Eczane Adresi:</label>
            <input type="text" id="address" name="address" required><br>
            
            <input type="hidden" name="order" id="orderInput" value="">

            <label for="confirmation">Onay Kodu:</label>
            <input type="text" id="confirmation" name="confirmation" required><br>
            
            <button type="submit">Kayıt Ol</button>
        </form>
        <?php if ($error_message) echo "<p style='color: red;'>$error_message</p>"; ?>
        <p>Kullanıcı girişi için buraya <a href="/login">tıklaynız</a></p>
        <p>Kullanıcı kaydı için buraya <a href="/register">tıklaynız</a></p>
    </div>

    <script src="../pharmacy/script.js"></script>
</body>
</html>
