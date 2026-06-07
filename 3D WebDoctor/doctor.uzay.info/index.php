<?php
session_start();

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

require 'dbnormal.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$rejected_medicines = [];
$stmt = $conn->prepare("SELECT medicine FROM Ills WHERE user_id = ? AND result = '-' AND conclusion_rationale LIKE ?");
$text = "%Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır.%";
$stmt->bind_param("is", $_SESSION['user_id'], $text);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) {
    $rejected_medicines[] = $row['medicine'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
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
    <title>3D Web Doctor - İlaç Alım İşlemleri</title>
    <link class="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <div class="header">
        <a href="/" class="button">Eczaneden Yeni İlaç Alma</a>
        <a href="/medicine" class="button">Mevcut İlacın Dozunu Hesaplama</a>
        <a href="/resume" class="button">Özgeçmiş</a>
        <a href="/logout" class="button">Çıkış</a>
    </div>
    
    <div id="pharmacyGate">
        <div id="pharmacyBox">
            <h3>Onay Kodu</h3>
            <input type="password" id="pharmacyInput" placeholder="Eczanenin onay kodunu giriniz.">
            <button onclick="checkPharmacy()">Giriş Yap</button>
            <p id="pharmacyError" style="color:red;"></p>
        </div>
    </div>

    <div class="container hidden" id="mainContainer">
        <div id="question" class="question"></div>
        <div class="doctorImage">
            <img id="doctorImage" src="images/doctor.png" alt="Doctor Image">
        </div>
        <div id="optionsContainer">
            <div class="option" onclick="selectOption(1, 'Ağrı Sorunları')">Ağrı Sorunları</div>
            <div class="option" onclick="selectOption(2, 'Bulantı ve Mide Sorunları')">Bulantı ve Mide Sorunları</div>
            <div class="option" onclick="selectOption(3, 'Yüksek Ateş ve Yorgunluk')">Yüksek Ateş ve Yorgunluk</div>
            <div class="option" onclick="selectOption(4, 'Solunum Yolu ve Alerji Kaynaklı Rahatsızlıklar')">Solunum Yolu ve Alerji Kaynaklı Rahatsızlıklar</div>
        </div>
        <p id="selectedOptionText">Seçilen Rahatsızlık Belirtisi:</p>
        <div id="doseForm" class="dose-form hidden">
            <label for="weight">Kilo:</label>
            <input type="number" id="weight" name="weight" placeholder="kg < 10 ise 5.3 gibi kesirli sayı giriniz.">
            <button onclick="calculateDose()">Dozu Hesapla</button>
        </div>
        <p id="doseResult" class="dose-result"></p>
        <div id="inputContainerWrapper">
            <div id="inputContainer">
                <input type="temp" id="tempInput" placeholder="Örnek: Ateşim 37.2°C" />
                <button id="submitButton">Gönder</button>
            </div>
        </div>
    </div>
    
<script>
    const csrf_token = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    let user_id = <?php echo json_encode($_SESSION['user_id']); ?>;
    let name = <?php echo json_encode($_SESSION['name']); ?>;
    let surname = <?php echo json_encode($_SESSION['surname']); ?>;
    let information = <?php echo json_encode($_SESSION['information']); ?>;
    let age = <?php echo json_encode($_SESSION['age']); ?>;
    let customAge = <?php echo json_encode($_SESSION['customAge'] ?? ''); ?>;
    let rejectedMedicines = <?php echo json_encode($rejected_medicines); ?>;
</script>
<script src="sentences.js"></script>
<script src="script.js"></script>
</body>
</html>