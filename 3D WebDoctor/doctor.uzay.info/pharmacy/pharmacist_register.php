<?php
session_start();
require 'db.php';

$conn->exec("SET NAMES 'utf8mb4'");
$conn->exec("SET CHARACTER SET utf8mb4");
$conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_turkish_ci'");

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pharmacy = isset($_POST['pharmacy']) ? trim($_POST['pharmacy']) : '';
    $confirmation_code = isset($_POST['confirmation_code']) ? trim($_POST['confirmation_code']) : '';
    $cupboard_code = isset($_POST['cupboard_code']) ? trim($_POST['cupboard_code']) : '';
    
    $pharmacist_name = isset($_POST['pharmacist_name']) ? trim(preg_replace('/\s+/', ' ', $_POST['pharmacist_name'])) : '';
    $pharmacist_name = mb_convert_case(mb_strtolower($pharmacist_name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    
    $pharmacist_surname = isset($_POST['pharmacist_surname']) ? trim(preg_replace('/\s+/', ' ', $_POST['pharmacist_surname'])) : '';
    $pharmacist_surname = mb_convert_case(mb_strtolower($pharmacist_surname, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    
    $TCKNO = isset($_POST['TCKNO']) ? trim($_POST['TCKNO']) : '';
    $password_raw = isset($_POST['password']) ? $_POST['password'] : '';
    $food_raw = isset($_POST['food']) ? trim($_POST['food']) : '';
    $pet_raw = isset($_POST['pet']) ? trim($_POST['pet']) : '';

    if (empty($_SESSION['captcha_ok'])) {
        $error_message = "Lütfen Ben robot değilim doğrulamasını yapınız.";
    } elseif ($pharmacy === '' || $confirmation_code === '' || $cupboard_code === '' || $pharmacist_name === '' || $pharmacist_surname === '' || $TCKNO === '' || $password_raw === '' || $food_raw === '' || $pet_raw === '') {
        $error_message = "Lütfen tüm alanları eksiksiz doldurunuz.";
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $pharmacist_name) || !preg_match('/^[\p{L}\s]+$/u', $pharmacist_surname)) {
        $error_message = "Ad ve Soyad sadece harflerden oluşmalıdır, rakam veya özel karakter içeremez.";
    } elseif (mb_strlen($pharmacy, 'UTF-8') > 100) {
        $error_message = "Eczane Adı en fazla 100 karakter olmalıdır.";
    } elseif (mb_strlen($pharmacist_name, 'UTF-8') > 35) {
        $error_message = "Eczacı Adı en fazla 35 karakter olmalıdır.";
    } elseif (mb_strlen($pharmacist_surname, 'UTF-8') > 45) {
        $error_message = "Eczacı Soyadı en fazla 45 karakter olmalıdır.";
    } elseif (mb_strlen($food_raw, 'UTF-8') > 100) {
        $error_message = "En sevdiğiniz yiyecek en fazla 100 karakter olmalıdır.";
    } elseif (mb_strlen($pet_raw, 'UTF-8') > 100) {
        $error_message = "Favori evcil hayvanınızın ismi en fazla 100 karakter olmalıdır.";
    } elseif (strlen($TCKNO) !== 11 || !ctype_digit($TCKNO)) {
        $error_message = "TC Kimlik Numarası 11 haneli rakamlardan oluşmalıdır.";
    } elseif (strlen($confirmation_code) !== 11 || !ctype_digit($confirmation_code)) {
        $error_message = "Eczane Onay Kodu 11 haneli rakamlardan oluşmalıdır.";
    } elseif (strlen($cupboard_code) !== 11 || !ctype_alnum($cupboard_code)) {
        $error_message = "Eczane Dolap Kodu 11 haneli harf ve rakamlardan oluşmalıdır.";
    } else {
        unset($_SESSION['captcha_ok']);

        try {
            $pharmacyStmt = $conn->prepare("SELECT confirmation_code, cupboard_code FROM pharmacies WHERE name = :name");
            $pharmacyStmt->bindParam(':name', $pharmacy);
            $pharmacyStmt->execute();
            $pharmacyRows = $pharmacyStmt->fetchAll(PDO::FETCH_ASSOC);

            $pharmacyMatch = false;
            foreach ($pharmacyRows as $row) {
                if (password_verify($confirmation_code, $row['confirmation_code']) && password_verify($cupboard_code, $row['cupboard_code'])) {
                    $pharmacyMatch = true;
                    break;
                }
            }

            if (!$pharmacyMatch) {
                $error_message = "Girdiğiniz Eczane Adı, Onay Kodu veya Dolap Kodu eşleşmiyor.";
            } else {
                $checkStmt = $conn->prepare("SELECT pharmacist_password FROM pharmacist");
                $checkStmt->execute();
                
                $exists = false;
                while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (password_verify($TCKNO, $row['pharmacist_password'])) {
                        $exists = true;
                        break;
                    }
                }

                if ($exists) {
                    $error_message = "Bu TC Kimlik Numarası ile zaten bir eczacı kaydı mevcut.";
                } else {
                    $tckno_hash = password_hash($TCKNO, PASSWORD_DEFAULT);
                    $password = password_hash($password_raw, PASSWORD_DEFAULT);
                    $food = password_hash($food_raw, PASSWORD_DEFAULT);
                    $pet = password_hash($pet_raw, PASSWORD_DEFAULT);

                    $insertStmt = $conn->prepare("INSERT INTO pharmacist (pharmacy, pharmacist_name, pharmacist_surname, pharmacist_password, pharmacist_password2, pharmacist_password3, pharmacist_password4) VALUES (:pharmacy, :pharmacist_name, :pharmacist_surname, :pharmacist_password, :pharmacist_password2, :pharmacist_password3, :pharmacist_password4)");
                    $insertStmt->bindParam(':pharmacy', $pharmacy);
                    $insertStmt->bindParam(':pharmacist_name', $pharmacist_name);
                    $insertStmt->bindParam(':pharmacist_surname', $pharmacist_surname);
                    $insertStmt->bindParam(':pharmacist_password', $tckno_hash);
                    $insertStmt->bindParam(':pharmacist_password2', $password);
                    $insertStmt->bindParam(':pharmacist_password3', $food);
                    $insertStmt->bindParam(':pharmacist_password4', $pet);

                    if ($insertStmt->execute()) {
                        header('Location: /pharmacist_login');
                        exit();
                    } else {
                        $error_message = "Kayıt işlemi sırasında teknik bir sorun oluştu.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "Bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eczacı Kayıt Sistemi</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="/register.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Eczacı Kayıt Sistemi</h2>
        <form method="POST" action="" id="pharmacistRegisterForm" onsubmit="return checkCaptcha();">
            
            <label for="pharmacy">Eczane Adı:</label>
            <input type="text" id="pharmacy" name="pharmacy" maxlength="100" required><br>

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
            
            <label for="pharmacist_name">Eczacı Adı:</label>
            <input type="text" id="pharmacist_name" name="pharmacist_name" maxlength="35" pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+" oninput="this.value = this.value.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');" required><br>
            
            <label for="pharmacist_surname">Eczacı Soyadı:</label>
            <input type="text" id="pharmacist_surname" name="pharmacist_surname" maxlength="45" pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+" oninput="this.value = this.value.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');" required><br>
            
            <label for="TCKNO">TC Kimlik Numarası:</label>
            <div class="input-wrapper">
                <input type="text" id="TCKNO" name="TCKNO" maxlength="11" pattern="\d{11}" style="-webkit-text-security: disc;" required>
                <span id="toggleTCKNO" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div><br>

            <label for="password">Parola:</label>
            <div id="passwordBarContainer">
                <div class="seg"></div>
                <div class="seg"></div>
                <div class="seg"></div>
                <div class="seg"></div>
            </div>
            <div class="input-wrapper">
                <input type="text" id="password" name="password" style="-webkit-text-security: disc;" oninput="checkPasswordStrength(this);" required>
                <span id="togglePassword" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div>
            <div id="passwordNote"></div>
            
            <label for="food">1) En sevdiğiniz yiyecek nedir?</label>
            <input type="text" id="food" name="food" maxlength="100" required>
            <div class="security-desc">Sadece parola yenileme için güvenlik sorusu olarak kullanılacaktır (Örn: Pilav, Köfte, Makarna, Brokoli).</div>

            <label for="pet">2) Favori evcil hayvanınızın ismi nedir?</label>
            <input type="text" id="pet" name="pet" maxlength="100" required>
            <div class="security-desc">Sadece parola yenileme için güvenlik sorusu olarak kullanılacaktır (Örn: Misket, Yumak, Ponçik, Karamel).</div>

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
        <?php if ($error_message) echo "<p style='color: red; text-align: center;'>$error_message</p>"; ?>
        <p>Hesabınız zaten kayıtlı mı? <a href="/pharmacist_login">Giriş yapın</a></p>
    </div>
    <script src="/register.js"></script>
    <script src="/pharmacy/cupboard.js"></script>
</body>
</html>