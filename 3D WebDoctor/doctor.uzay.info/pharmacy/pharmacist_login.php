<?php
session_start();
require 'db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $TCKNO = isset($_POST['TCKNO']) ? trim($_POST['TCKNO']) : '';
    $password_raw = isset($_POST['password']) ? $_POST['password'] : '';

    if ($TCKNO === '' || $password_raw === '') {
        $error_message = "Lütfen tüm alanları doldurunuz.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, pharmacy, pharmacist_name, pharmacist_surname, pharmacist_password, pharmacist_password2 FROM pharmacist");
            $stmt->execute();
            
            $authenticated = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($TCKNO, $row['pharmacist_password']) && password_verify($password_raw, $row['pharmacist_password2'])) {
                    $_SESSION['pharmacist_id'] = $row['id'];
                    $_SESSION['pharmacy'] = $row['pharmacy'];
                    $_SESSION['pharmacist_name'] = $row['pharmacist_name'];
                    $_SESSION['pharmacist_surname'] = $row['pharmacist_surname'];
                    $authenticated = true;
                    break;
                }
            }

            if ($authenticated) {
                $p_stmt = $conn->prepare("SELECT updated_at FROM pharmacies WHERE name = :name");
                $p_stmt->execute([':name' => $_SESSION['pharmacy']]);
                $pharmacy_row = $p_stmt->fetch(PDO::FETCH_ASSOC);

                if ($pharmacy_row) {
                    $updated_at = new DateTime($pharmacy_row['updated_at']);
                    $three_months_ago = new DateTime('-3 months');

                    if ($updated_at < $three_months_ago) {
                        $error_message = "Onay kodunun süresi dolmuş, lütfen onay kodunu güncelleyin.";
                    } else {
                        header('Location: /approval');
                        exit();
                    }
                } else {
                    header('Location: /approval');
                    exit();
                }
            } else {
                $error_message = "TC Kimlik Numarası veya Parola hatalı.";
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="3D Web Doctor Eczacı Onay Paneli. Profesyonel eczacılar için geliştirilmiş, hasta geçmişini ve ilaç taleplerini güvenle inceleyip onaylayabileceğiniz dijital denetim sistemi.">
    <meta name="robots" content="noindex, follow">
    <meta name="author" content="SpacePedia">
    <meta name="keywords" content="eczacı onay paneli, ilaç güvenlik onayı, eczane yönetim sistemi, hasta geçmişi denetimi, 3D Web Doctor eczacı girişi, reçete onay sistemi">
    <meta property="og:title" content="Eczacı Onay ve Güvenlik Paneli | 3D Web Doctor">
    <meta property="og:description" content="Eczacılar için özel onay ve denetim sistemi. 3D Web Doctor üzerinden gelen ilaç taleplerini güvenle yönetin.">
    <meta property="og:image" content="https://doctor.uzay.info/images/doctor.png">
    <meta property="og:url" content="https://doctor.uzay.info/approval">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Eczacı Onay ve Güvenlik Paneli | 3D Web Doctor">
    <meta name="twitter:description" content="Eczacıların hasta ilaç taleplerini yönettiği ve güvenliği denetlediği profesyonel onay paneli.">
    <meta name="twitter:image" content="https://doctor.uzay.info/images/doctor.png">
    <title>Eczacı Girişi</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Eczacı Giriş Yap</h2>
        <form method="POST" action="" id="pharmacistLoginForm">
            <label for="TCKNO">TC Kimlik Numarası:</label>
            <div class="input-wrapper">
                <input type="text" id="TCKNO" name="TCKNO" style="-webkit-text-security: disc;" required>
                <span id="toggleTCKNO" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div>

            <label for="password">Parola:</label>
            <div class="input-wrapper">
                <input type="text" id="password" name="password" style="-webkit-text-security: disc;" required>
                <span id="togglePassword" style="position:absolute; right:10px; top:19.5px; transform:translateY(-50%); color:#888; cursor:pointer;"><i class="fa-solid fa-eye"></i></span>
            </div>

            <button type="submit">Giriş Yap</button>
        </form>
        <?php if ($error_message) echo "<p style='color: red; text-align: center;'>$error_message</p>"; ?>
        <p>Eczacı kaydınız yok mu? <a href="/pharmacist_register">Buraya tıklayarak kaydolun</a></p>
        <p>Parolanızı mı unuttunuz? <a href="/pharmacist_reset_password">Parola Yenileme</a></p>
        <p>Eczane kaydı için buraya <a href="/cupboard">tıklayınız</a></p>
        <p>Onay kodunu güncellemek için buraya <a href="/confirmationcode_update">tıklayınız</a></p>
    </div>
    
    <script src="/login.js"></script>
</body>
</html>