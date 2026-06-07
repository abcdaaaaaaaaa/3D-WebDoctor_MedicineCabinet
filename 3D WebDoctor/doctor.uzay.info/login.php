<?php
session_start();
require 'db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tckno_raw = isset($_POST['TCKNO']) ? trim($_POST['TCKNO']) : '';
    $password_raw = isset($_POST['password']) ? $_POST['password'] : '';

    if ($tckno_raw === '' || $password_raw === '') {
        $error_message = "Lütfen tüm alanları doldurunuz.";
    } else {
        try {
            $stmt = $conn->query("SELECT user_id, name, surname, date_of_birth, password, password2, information FROM users");
            $user = null;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($tckno_raw, $row['password'])) {
                    if (password_verify($password_raw, $row['password2'])) {
                        $user = $row;
                    }
                    break;
                }
            }

            if ($user) {
                date_default_timezone_set('Europe/Istanbul');
                $userdate = $user['date_of_birth'];
                $date = new DateTime($userdate);
                $today = new DateTime('now');
                $interval = $today->diff($date);
                $age = $interval->y + ($interval->m / 12) + ($interval->d / 365);
                
                $_SESSION['customAge'] = $interval->format('%y yıl %m ay %d gün');
                $_SESSION['age'] = round($age, 4);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['surname'] = $user['surname'];
                $_SESSION['information'] = $user['information'];
                
                header('Location: /');
                exit();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="3D Web Doctor: İlaç dozajı hesaplamadan akıllı dolap üzerinden otomatik teslimata kadar uçtan uca dijital eczacılık ve sağlık otomasyon platformu.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="SpacePedia">
    <meta name="keywords" content="akıllı ilaç dolabı, otomatik ilaç teslimatı, 3D Web Doctor, dijital eczacılık, sağlık otomasyonu, ilaç yönetim sistemi">
    <meta property="og:title" content="3D Web Doctor - Akıllı İlaç Yönetimi ve Otomasyon Sistemi">
    <meta property="og:description" content="İlaç dolabınızdan akıllı teslimat sürecine kadar tüm süreç 3D Web Doctor ile kontrol altında. Profesyonel sağlık otomasyonu.">
    <meta property="og:image" content="https://doctor.uzay.info/images/doctor.png">
    <meta property="og:url" content="https://doctor.uzay.info/">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <title>Kullanıcı Giriş Sistemi</title>
    <link rel="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/loginstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2>Kullanıcı Giriş Sistemi</h2>
        <form method="POST" action="" id="loginForm">
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
        <p>Hesabınız yok mu? <a href="/register">Kayıt Ol</a></p>
        <p>Parolanızı mı unuttunuz? <a href="/reset_password">Parola Yenileme</a></p>
        <p>Eczane kaydı için buraya <a href="/cupboard">tıklayınız</a></p>
    </div>
    <script src="/login.js"></script>
</body>
</html>