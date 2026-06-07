<?php
session_start();
require 'db.php';

$conn->exec("SET NAMES 'utf8mb4'");
$conn->exec("SET CHARACTER SET utf8mb4");
$conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_turkish_ci'");

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $confirmation_raw = isset($_POST['confirmation_code']) ? $_POST['confirmation_code'] : '';
    $cupboard_raw = isset($_POST['cupboard_code']) ? $_POST['cupboard_code'] : '';
    $order = isset($_POST['order']) ? $_POST['order'] : '';

    if (empty($_SESSION['captcha_ok'])) {
        $error_message = "Lütfen Ben robot değilim doğrulamasını yapınız.";
    } elseif ($name === '' || $address === '' || $confirmation_raw === '' || $cupboard_raw === '') {
        $error_message = "Lütfen tüm alanları doldurunuz.";
    } elseif (mb_strlen($name, 'UTF-8') > 100) {
        $error_message = "Eczane Adı en fazla 100 karakter olmalıdır.";
    } elseif (strlen($confirmation_raw) !== 11 || !ctype_digit($confirmation_raw)) {
        $error_message = "Onay Kodu tam olarak 11 haneli rakamlardan oluşmalıdır.";
    } elseif (strlen($cupboard_raw) !== 11 || !ctype_alnum($cupboard_raw)) {
        $error_message = "Dolap Kodu tam olarak 11 haneli harf ve rakamlardan oluşmalıdır.";
    } else {
        unset($_SESSION['captcha_ok']);
        
        $confirmation_hash = password_hash($confirmation_raw, PASSWORD_DEFAULT);
        $cupboard_hash = password_hash($cupboard_raw, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO pharmacies (name, address, confirmation_code, cupboard_code, medicine_order) VALUES (:name, :address, :confirmation_code, :cupboard_code, :medicine_order)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':confirmation_code', $confirmation_hash);
        $stmt->bindParam(':cupboard_code', $cupboard_hash);
        $stmt->bindParam(':medicine_order', $order);

        if ($stmt->execute()) {
            header('Location: /pharmacist_register');
            exit();
        } else {
            $error_message = "Kayıt sırasında bir hata oluştu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medikal İlaç Dolabı Kayıt Sistemi</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="/pharmacy/style.css">
    <link rel="stylesheet" href="/register.css">
    <link rel="stylesheet" href="/pharmacy/cupboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="cupboard-wrapper">
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
            <h2>Medikal İlaç Dolabı Kayıt Sistemi</h2>
            <form method="POST" action="" id="registerForm" onsubmit="return checkCaptcha();">
                <label for="name">Eczane Adı:</label>
                <input type="text" id="name" name="name" maxlength="100" required>
                
                <label for="address">Eczane Adresi:</label>
                <input type="text" id="address" name="address" required>
                
                <input type="hidden" name="order" id="orderInput" value="">

                <label for="confirmation_code">Onay Kodu:</label>
                <div class="input-wrapper">
                    <input type="text" id="confirmation_code" name="confirmation_code" maxlength="11" pattern="\d{11}" style="-webkit-text-security: disc;" required>
                    <span id="toggleConfirmationCode"><i class="fa-solid fa-eye"></i></span>
                </div>
                
                <label for="cupboard_code">Dolap Kodu:</label>
                <div class="input-wrapper">
                    <input type="text" id="cupboard_code" name="cupboard_code" maxlength="11" pattern="[a-zA-Z0-9]{11}" style="-webkit-text-security: disc;" required>
                    <span id="toggleCupboardCode"><i class="fa-solid fa-eye"></i></span>
                </div>

                <div class="recaptcha">
                    <div class="top" onclick="startCaptcha()">
                        <div class="left2">
                            <div class="checkbox" id="cb"></div>
                            <div class="spinner" id="sp"></div>
                            <span>Ben robot değilim.</span>
                        </div>
                        <img src="https://www.uzay.info/static/recaptcha.png" width="28">
                    </div>
                </div>
                <div class="challenge" id="ch">
                    <canvas id="cv" width="180" height="60"></canvas><br>
                    <input type="text" id="inp" placeholder="Gördüğünüzü yazınız.">
                </div>
                
                <div style="font-size:11px;color:#666;text-align:left;">
                    Kayıt olarak <a href="/terms">Kullanım Şartları</a>'nı kabul etmiş olursunuz. <a href="/privacy">Gizlilik Politikamızı</a> okuyunuz.
                </div>
                
                <button type="submit">Kayıt Ol</button>
            </form>
            <?php if ($error_message) echo "<p style='color: red; text-align: center; margin-top: 15px;'>$error_message</p>"; ?>
            <p>Eczacı girişi için buraya <a href="/pharmacist_login">tıklayınız</a></p>
            <p>Eczacı kaydı için buraya <a href="/pharmacist_register">tıklayınız</a></p>
            <p>Onay kodunu güncellemek için buraya <a href="/confirmationcode_update">tıklayınız</a></p>
        </div>
    </div>

    <script src="/pharmacy/script.js"></script>
    <script src="/register.js"></script>
    <script src="/pharmacy/cupboard.js"></script>
</body>
</html>