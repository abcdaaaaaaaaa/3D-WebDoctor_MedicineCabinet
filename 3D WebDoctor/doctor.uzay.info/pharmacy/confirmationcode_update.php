<?php
session_start();
require 'db.php';

$conn->exec("SET NAMES 'utf8mb4'");
$conn->exec("SET CHARACTER SET utf8mb4");
$conn->exec("SET COLLATION_CONNECTION = 'utf8mb4_turkish_ci'");

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_step'])) {
        $pharmacy_name = isset($_POST['pharmacy_name']) ? trim($_POST['pharmacy_name']) : '';
        $confirmation_code = isset($_POST['confirmation_code']) ? trim($_POST['confirmation_code']) : '';
        $cupboard_code = isset($_POST['cupboard_code']) ? trim($_POST['cupboard_code']) : '';

        if ($pharmacy_name === '' || $confirmation_code === '' || $cupboard_code === '') {
            $error_message = "Lütfen tüm alanları doldurunuz.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT confirmation_code, cupboard_code FROM pharmacies WHERE name = :name");
                $stmt->execute([':name' => $pharmacy_name]);
                $current_pharmacy = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$current_pharmacy) {
                    $error_message = "Girilen eczane adı bulunamadı.";
                } else {
                    if (!password_verify($confirmation_code, $current_pharmacy['confirmation_code']) || 
                        !password_verify($cupboard_code, $current_pharmacy['cupboard_code'])) {
                        $error_message = "Onay Kodu veya Dolap Kodu hatalı.";
                    } else {
                        $_SESSION['verified_pharmacy_for_update'] = $pharmacy_name;
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Bir hata oluştu: " . $e->getMessage();
            }
        }
    } 
    elseif (isset($_POST['update_step'])) {
        $new_confirmation_code = isset($_POST['new_confirmation_code']) ? trim($_POST['new_confirmation_code']) : '';

        if (!isset($_SESSION['verified_pharmacy_for_update'])) {
            $error_message = "Lütfen önce doğrulama adımını tamamlayınız.";
        } elseif ($new_confirmation_code === '') {
            $error_message = "Lütfen yeni onay kodunu giriniz.";
        } elseif (strlen($new_confirmation_code) !== 11 || !ctype_digit($new_confirmation_code)) {
            $error_message = "Yeni Onay Kodu tam olarak 11 haneli rakamlardan oluşmalıdır.";
        } else {
            $pharmacy_name = $_SESSION['verified_pharmacy_for_update'];

            try {
                $stmt = $conn->prepare("SELECT confirmation_code FROM pharmacies WHERE name = :name");
                $stmt->execute([':name' => $pharmacy_name]);
                $current_pharmacy = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($new_confirmation_code, $current_pharmacy['confirmation_code'])) {
                    $error_message = "Yeni onay kodu eskisinden farklı olmalıdır.";
                } else {
                    $all_stmt = $conn->prepare("SELECT confirmation_code FROM pharmacies");
                    $all_stmt->execute();
                    
                    $is_unique = true;
                    while ($row = $all_stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (password_verify($new_confirmation_code, $row['confirmation_code'])) {
                            $is_unique = false;
                            break;
                        }
                    }

                    if (!$is_unique) {
                        $error_message = "Bu onay kodu başka bir eczane tarafından kullanılmaktadır. Lütfen benzersiz bir kod oluşturunuz.";
                    } else {
                        $new_hash = password_hash($new_confirmation_code, PASSWORD_DEFAULT);
                        
                        $update_stmt = $conn->prepare("UPDATE pharmacies SET confirmation_code = :new_code, updated_at = NOW() WHERE name = :name");
                        $update_stmt->execute([
                            ':new_code' => $new_hash,
                            ':name' => $pharmacy_name
                        ]);

                        $success_message = "Onay kodu başarıyla güncellendi! Giriş sayfasına yönlendiriliyorsunuz...";
                        unset($_SESSION['verified_pharmacy_for_update']);
                        header("refresh:3;url=/pharmacist_login");
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Bir hata oluştu: " . $e->getMessage();
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
    <title>Onay Kodu Yenileme</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Onay Kodu Yenileme</h2>
        
        <?php if (!isset($_SESSION['verified_pharmacy_for_update']) && empty($success_message)): ?>
        <form method="POST" action="">
            <label for="pharmacy_name">Eczane Adı:</label>
            <div class="input-wrapper">
                <input type="text" id="pharmacy_name" name="pharmacy_name" required>
            </div>

            <label for="confirmation_code">Onay Kodu:</label>
            <div class="input-wrapper">
                <input type="text" id="confirmation_code" name="confirmation_code" style="-webkit-text-security: disc;" required>
                <span id="toggleConfirmationCode" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div>
            
            <label for="cupboard_code">Dolap Kodu:</label>
            <div class="input-wrapper">
                <input type="text" id="cupboard_code" name="cupboard_code" style="-webkit-text-security: disc;" required>
                <span id="toggleCupboardCode" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div>

            <button type="submit" name="verify_step">Devam Et</button>
        </form>
        <?php elseif (empty($success_message)): ?>
        <form method="POST" action="">
            <label for="new_confirmation_code">Yeni Onay Kodu:</label>
            <div class="input-wrapper">
                <input type="text" id="new_confirmation_code" name="new_confirmation_code" maxlength="11" pattern="\d{11}" style="-webkit-text-security: disc;" required>
                <span id="toggleNewConfirmationCode" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div>

            <button type="submit" name="update_step">Onay Kodunu Güncelle</button>
        </form>
        <?php endif; ?>

        <?php 
        if ($error_message) {
            echo "<p style='color: red; text-align: center; margin-top: 15px;'>$error_message</p>";
        }
        if ($success_message) {
            echo "<p style='color: green; text-align: center; margin-top: 15px;'>$success_message</p>";
        }
        ?>
        <p>Eczacı girişi için buraya <a href="/pharmacist_login">tıklayınız</a></p>
    </div>

    <script src="/pharmacy/cupboard.js"></script>
</body>
</html>