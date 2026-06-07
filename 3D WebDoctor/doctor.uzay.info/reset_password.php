<?php
session_start();
require 'db.php';

$error_message = '';
$success_message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $surname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
        $tckno_raw = isset($_POST['TCKNO']) ? trim($_POST['TCKNO']) : '';
        $food_raw = isset($_POST['food']) ? trim($_POST['food']) : '';
        $pet_raw = isset($_POST['pet']) ? trim($_POST['pet']) : '';

        if ($name === '' || $surname === '' || $tckno_raw === '' || $food_raw === '' || $pet_raw === '') {
            $error_message = "Lütfen tüm alanları doldurunuz.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT user_id, password, password3, password4 FROM users WHERE name = :name AND surname = :surname");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':surname', $surname);
                $stmt->execute();
                
                $verified_user_id = null;

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (password_verify($tckno_raw, $row['password']) && 
                        password_verify($food_raw, $row['password3']) && 
                        password_verify($pet_raw, $row['password4'])) {
                        $verified_user_id = $row['user_id'];
                        break;
                    }
                }

                if ($verified_user_id !== null) {
                    $_SESSION['reset_user_id'] = $verified_user_id;
                    $step = 2;
                } else {
                    $error_message = "Girdiğiniz bilgiler hiçbir kullanıcı ile eşleşmedi.";
                }
            } catch (PDOException $e) {
                $error_message = "Bir hata oluştu: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        if (isset($_SESSION['reset_user_id'])) {
            $new_password = isset($_POST['password']) ? $_POST['password'] : '';
            
            if ($new_password === '') {
                $error_message = "Lütfen yeni parolanızı giriniz.";
                $step = 2;
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE users SET password2 = :password WHERE user_id = :user_id");
                    $updateStmt->bindParam(':password', $hashed_password);
                    $updateStmt->bindParam(':user_id', $_SESSION['reset_user_id']);
                    
                    if ($updateStmt->execute()) {
                        unset($_SESSION['reset_user_id']);
                        $success_message = "Parolanız başarıyla güncellendi. Giriş sayfasına yönlendiriliyorsunuz.";
                        header("Refresh: 3; url=login.php");
                        $step = 3;
                    } else {
                        $error_message = "Parola güncellenirken bir hata oluştu.";
                        $step = 2;
                    }
                } catch (PDOException $e) {
                    $error_message = "Bir hata oluştu: " . $e->getMessage();
                    $step = 2;
                }
            }
        } else {
            $error_message = "Oturum süresi doldu, lütfen işlemi baştan başlatın.";
            $step = 1;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parola Yenileme</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="/register.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Parola Yenileme</h2>
        
        <?php if ($step === 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="verify">
                
                <label for="TCKNO">TC Kimlik Numarası:</label>
                <div class="input-wrapper">
                    <input type="text" id="TCKNO" name="TCKNO" style="-webkit-text-security: disc;" required>
                    <span id="toggleTCKNO" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
                </div><br>

                <label for="name">Ad:</label>
                <input type="text" id="name" name="name" required><br>
                
                <label for="surname">Soyad:</label>
                <input type="text" id="surname" name="surname" required><br>
                
                <label for="food">En sevdiğiniz yiyecek nedir?</label>
                <input type="text" id="food" name="food" required><br>

                <label for="pet">Favori evcil hayvanınızın ismi nedir?</label>
                <input type="text" id="pet" name="pet" required><br>

                <button type="submit">Devam Et</button>
            </form>
        <?php endif; ?>

        <?php if ($step === 2): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                
                <label for="password">Yeni Parola:</label>
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

                <button type="submit">Parolayı Güncelle</button>
            </form>
        <?php endif; ?>

        <?php if ($error_message) echo "<p style='color: red; text-align: center; margin-top: 15px;'>$error_message</p>"; ?>
        <?php if ($success_message) echo "<p style='color: green; text-align: center; margin-top: 15px;'>$success_message</p>"; ?>
        
        <p style="margin-top: 20px; text-align: center;"><a href="/login" class="blue">Giriş Yap sayfasına dön</a></p>
    </div>

    <script src="/register.js"></script>
</body>
</html>