<?php
session_start();
require 'db.php';

$conn->exec("SET NAMES 'utf8mb4'");
$conn->exec("SET CHARACTER SET utf8mb4");
$conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_turkish_ci'");

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim(preg_replace('/\s+/', ' ', $_POST['name'])) : '';
    $name = mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    
    $surname = isset($_POST['surname']) ? trim(preg_replace('/\s+/', ' ', $_POST['surname'])) : '';
    $surname = mb_convert_case(mb_strtolower($surname, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

    $birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : '';
    $tckno_raw = isset($_POST['TCKNO']) ? trim($_POST['TCKNO']) : '';
    $password_raw = isset($_POST['password']) ? $_POST['password'] : '';
    $food_raw = isset($_POST['food']) ? trim($_POST['food']) : '';
    $pet_raw = isset($_POST['pet']) ? trim($_POST['pet']) : '';
    $information = isset($_POST['information']) ? trim($_POST['information']) : '';

    if (empty($_SESSION['captcha_ok'])) {
        $error_message = "Lütfen Ben robot değilim doğrulamasını yapınız.";
    } elseif ($name === '' || $surname === '' || $birthdate === '' || $tckno_raw === '' || $password_raw === '' || $food_raw === '' || $pet_raw === '') {
        $error_message = "Lütfen zorunlu tüm alanları doldurunuz.";
    } elseif (!preg_match('/^[\p{L}\s]+$/u', $name) || !preg_match('/^[\p{L}\s]+$/u', $surname)) {
        $error_message = "Ad ve Soyad sadece harflerden oluşmalıdır, rakam veya özel karakter içeremez.";
    } elseif (mb_strlen($name, 'UTF-8') > 35) {
        $error_message = "Ad en fazla 35 karakter olmalıdır.";
    } elseif (mb_strlen($surname, 'UTF-8') > 45) {
        $error_message = "Soyad en fazla 45 karakter olmalıdır.";
    } elseif (mb_strlen($food_raw, 'UTF-8') > 100) {
        $error_message = "En sevdiğiniz yiyecek en fazla 100 karakter olmalıdır.";
    } elseif (mb_strlen($pet_raw, 'UTF-8') > 100) {
        $error_message = "Favori evcil hayvanınızın ismi en fazla 100 karakter olmalıdır.";
    } elseif (mb_strlen($information, 'UTF-8') > 500) {
        $error_message = "Sağlık durumu bilgisi en fazla 500 karakter olmalıdır.";
    } elseif (strlen($tckno_raw) !== 11 || !ctype_digit($tckno_raw)) {
        $error_message = "TC Kimlik Numarası 11 haneli rakamlardan oluşmalıdır.";
    } else {
        unset($_SESSION['captcha_ok']);
        
        $checkStmt = $conn->prepare("SELECT password FROM users");
        $checkStmt->execute();
        
        $exists = false;
        while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($tckno_raw, $row['password'])) {
                $exists = true;
                break;
            }
        }
        
        if ($exists) {
            $error_message = "Bu TC Kimlik Numarası ile zaten kayıt olunmuş.";
        } else {
            $tckno_hash = password_hash($tckno_raw, PASSWORD_DEFAULT);
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $food = password_hash($food_raw, PASSWORD_DEFAULT);
            $pet = password_hash($pet_raw, PASSWORD_DEFAULT);
            $has_problem = !empty($information);

            $stmt = $conn->query("SELECT COALESCE(MAX(ABS(user_id)), 0) + 1 AS next_id FROM users"); 
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $base_id = $row['next_id'];
            $user_id = $has_problem ? -$base_id : $base_id;

            $insertStmt = $conn->prepare("INSERT INTO users (user_id, name, surname, date_of_birth, password, password2, password3, password4, information) VALUES (:user_id, :name, :surname, :birthdate, :password, :password2, :password3, :password4, :information)");
            $insertStmt->bindParam(':user_id', $user_id);
            $insertStmt->bindParam(':name', $name);
            $insertStmt->bindParam(':surname', $surname);
            $insertStmt->bindParam(':birthdate', $birthdate);
            $insertStmt->bindParam(':password', $tckno_hash);
            $insertStmt->bindParam(':password2', $password);
            $insertStmt->bindParam(':password3', $food);
            $insertStmt->bindParam(':password4', $pet);
            $insertStmt->bindParam(':information', $information);

            if ($insertStmt->execute()) {
                header('Location: /login');
                exit();
            } else {
                $error_message = "Kayıt sırasında bir hata oluştu.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Kayıt Sistemi</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="/register.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Kullanıcı Kayıt Sistemi</h2>
        <form method="POST" action="" id="registerForm" onsubmit="return checkCaptcha();">
            <label for="name">Ad:</label>
            <input type="text" id="name" name="name" maxlength="35" pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+" oninput="this.value = this.value.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');" required><br>
            
            <label for="surname">Soyad:</label>
            <input type="text" id="surname" name="surname" maxlength="45" pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+" oninput="this.value = this.value.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');" required><br>
            
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

            <label for="birthdate">Doğum Tarihi:</label>
            <input type="date" id="birthdate" name="birthdate" required><br>
            
            <label for="information">Özel bir ilaç kullanıyormusunuz veya ciddi sağlık probleminiz var mı? Kullanıyorsanız/Varsa doktorunuzdan yazınız (500 karaktere kadar).</label>
            <textarea id="information" name="information" maxlength="500"></textarea><br>

            <div class="checkbox-group">
                <label><input type="checkbox" id="ibuprofen" name="condition[]" value="Ibuprofen'e karşı özel bir rahatsızlığı var.">
                    Ibuprofen'e karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="paracetamol" name="condition[]" value="Paracetemol'e karşı özel bir rahatsızlığı var.">
                    Paracetemol'e karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="gaviscon" name="condition[]" value="Gaviscon'a karşı özel bir rahatsızlığı var.">
                    Gaviscon'a karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="cetirizine" name="condition[]" value="Cetirizine'e karşı özel bir rahatsızlığı var.">
                    Cetirizine'e karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="allergic" name="condition[]" value="Bazı ilaçlara karşı aşırı alerijik reaksiyon göstermektedir.">
                    Bazı ilaçlara karşı aşırı alerijik reaksiyon gösteriyorum.</label><br>
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
        <?php if ($error_message) echo "<p style='color: red; text-align: center;'>$error_message</p>"; ?>
        <p>Hesabınız zaten kayıtlı mı? <a href="/login">Giriş Yap</a></p>
        <p>Eczane kaydı için buraya <a href="/cupboard">tıklayınız</a></p>
    </div>
    <script src="/register.js"></script>
</body>
</html>