<?php
session_start();
require 'db.php';

$error_message = '';
$success_message = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        $pharmacist_name = isset($_POST['pharmacist_name']) ? trim($_POST['pharmacist_name']) : '';
        $pharmacist_surname = isset($_POST['pharmacist_surname']) ? trim($_POST['pharmacist_surname']) : '';
        $TCKNO = isset($_POST['TCKNO']) ? trim($_POST['TCKNO']) : '';
        $food_raw = isset($_POST['food']) ? trim($_POST['food']) : '';
        $pet_raw = isset($_POST['pet']) ? trim($_POST['pet']) : '';

        if ($pharmacist_name === '' || $pharmacist_surname === '' || $TCKNO === '' || $food_raw === '' || $pet_raw === '') {
            $error_message = "Lütfen tüm alanları doldurunuz.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, pharmacist_password, pharmacist_password3, pharmacist_password4 FROM pharmacist WHERE pharmacist_name = :pharmacist_name AND pharmacist_surname = :pharmacist_surname");
                $stmt->bindParam(':pharmacist_name', $pharmacist_name);
                $stmt->bindParam(':pharmacist_surname', $pharmacist_surname);
                $stmt->execute();
                
                $verified_pharmacist_id = null;

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (password_verify($TCKNO, $row['pharmacist_password']) && 
                        password_verify($food_raw, $row['pharmacist_password3']) && 
                        password_verify($pet_raw, $row['pharmacist_password4'])) {
                        $verified_pharmacist_id = $row['id'];
                        break;
                    }
                }

                if ($verified_pharmacist_id !== null) {
                    $_SESSION['reset_pharmacist_id'] = $verified_pharmacist_id;
                    $step = 2;
                } else {
                    $error_message = "Girdiğiniz bilgiler hiçbir eczacı kaydı ile eşleşmedi.";
                }
            } catch (PDOException $e) {
                $error_message = "Bir hata oluştu: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        if (isset($_SESSION['reset_pharmacist_id'])) {
            $new_password = isset($_POST['password']) ? $_POST['password'] : '';
            
            if ($new_password === '') {
                $error_message = "Lütfen yeni parolanızı giriniz.";
                $step = 2;
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE pharmacist SET pharmacist_password2 = :password WHERE id = :id");
                    $updateStmt->bindParam(':password', $hashed_password);
                    $updateStmt->bindParam(':id', $_SESSION['reset_pharmacist_id']);
                    
                    if ($updateStmt->execute()) {
                        unset($_SESSION['reset_pharmacist_id']);
                        $success_message = "Parolanız başarıyla güncellendi. Giriş sayfasına yönlendiriliyorsunuz.";
                        header("Refresh: 3; url=/pharmacist_login");
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
    <title>Eczacı Parola Yenileme</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="/register.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Eczacı Parola Yenileme</h2>
        
        <?php if ($step === 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="verify">
                
                <label for="TCKNO">TC Kimlik Numarası:</label>
                <div class="input-wrapper">
                    <input type="text" id="TCKNO" name="TCKNO" style="-webkit-text-security: disc;" required>
                    <span id="toggleTCKNO"><i class="fa-solid fa-eye"></i></span>
                </div>

                <label for="pharmacist_name">Eczacı Adı:</label>
                <input type="text" id="pharmacist_name" name="pharmacist_name" required>
                
                <label for="pharmacist_surname">Eczacı Soyadı:</label>
                <input type="text" id="pharmacist_surname" name="pharmacist_surname" required>
                
                <label for="food">En sevdiğiniz yiyecek nedir?</label>
                <input type="text" id="food" name="food" required>

                <label for="pet">Favori evcil hayvanınızın ismi nedir?</label>
                <input type="text" id="pet" name="pet" required>

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
                    <span id="togglePassword"><i class="fa-solid fa-eye"></i></span>
                </div>
                <div id="passwordNote"></div>

                <button type="submit">Parolayı Güncelle</button>
            </form>
        <?php endif; ?>

        <?php if ($error_message) echo "<p style='color: red; text-align: center; margin-top: 15px;'>$error_message</p>"; ?>
        <?php if ($success_message) echo "<p style='color: green; text-align: center; margin-top: 15px;'>$success_message</p>"; ?>
        
        <p style="margin-top: 20px; text-align: center;"><a href="/pharmacist_login">Giriş Yap sayfasına dön</a></p>
    </div>

    <script src="/register.js"></script>
</body>
</html>