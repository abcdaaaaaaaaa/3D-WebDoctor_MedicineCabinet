<?php
session_start();

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

require 'dbnormal.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$recentBrands = [];
$rejectedActives = [];
$requiresPharmacy = [
    "Ibuprofen" => true,
    "Gaviscon" => true,
    "Paracetamol" => true,
    "Cetirizine" => true
];

if (isset($conn)) {
    $userId = $_SESSION['user_id'];
    try {
        $stmt1 = $conn->prepare("SELECT brand FROM Ills WHERE user_id = ? AND result = '+' AND created_at >= DATE_SUB(NOW(), INTERVAL 2 YEAR)");
        $stmt1->bind_param("i", $userId);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        while($row = $res1->fetch_assoc()) { 
            $recentBrands[] = $row['brand']; 
        }

        $stmt2 = $conn->prepare("SELECT medicine FROM Ills WHERE user_id = ? AND result = '-' AND conclusion_rationale LIKE ?");
        $text = "%Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır.%";
        $stmt2->bind_param("is", $userId, $text);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        
        while($row = $res2->fetch_assoc()) {
            $rejectedActives[] = $row['medicine'];
        }

        $actives = ["Ibuprofen", "Gaviscon", "Paracetamol", "Cetirizine"];
        foreach ($actives as $act) {
            $stmt3 = $conn->prepare("SELECT created_at FROM Ills WHERE user_id = ? AND medicine = ? AND result = '+' AND conclusion_rationale != 'Daha önceden uygun görülen ilgili ilaç tekrardan uygun görüldü.' AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH) ORDER BY created_at DESC LIMIT 1");
            $stmt3->bind_param("is", $userId, $act);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            
            if ($row3 = $res3->fetch_assoc()) {
                $lastPharm = $row3['created_at'];
                
                $stmt4 = $conn->prepare("SELECT COUNT(*) as attempt_count FROM Ills WHERE user_id = ? AND medicine = ? AND created_at > ?");
                $stmt4->bind_param("iss", $userId, $act, $lastPharm);
                $stmt4->execute();
                $row4 = $stmt4->get_result()->fetch_assoc();
                
                if ($row4['attempt_count'] < 3) {
                    $requiresPharmacy[$act] = false;
                }
            }
        }
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="3D Web Doctor ile ilaç dozajınızı hassasiyetle hesaplayın. Akıllı ilaç dolabına entegre çalışan sistemimiz, kişiselleştirilmiş verilerinizle güvenli ve otomatik dozaj iletimi sağlar.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="SpacePedia">
    <meta name="keywords" content="ilaç dozaj hesaplama, akıllı ilaç dolabı, otomatik ilaç yönetimi, hassas dozaj, IoT sağlık sistemi, 3D Web Doctor">
    <meta property="og:title" content="Akıllı Dozaj Hesaplama | 3D Web Doctor">
    <meta property="og:description" content="Kişiselleştirilmiş dozaj hesaplama ve akıllı ilaç dolabı ile otomatik dozaj teslimatı. Sağlığınız 3D Web Doctor ile kontrol altında.">
    <meta property="og:image" content="https://doctor.uzay.info/images/doctor.png">
    <meta property="og:url" content="https://doctor.uzay.info/medicine">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Akıllı Dozaj Hesaplama | 3D Web Doctor">
    <meta name="twitter:description" content="Akıllı ilaç dolabı entegreli dozaj yönetimi ve güvenli takip sistemi.">
    <meta name="twitter:image" content="https://doctor.uzay.info/images/doctor.png">
    <title>3D Web Doctor - İlaç Kullanım İşlemleri</title>
    <link class="shortcut icon" href="/images/doctor.ico">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    
    <div class="header">
        <a href="/" class="button">Eczaneden Yeni İlaç Alma</a>
        <a href="/medicine" class="button">Mevcut İlacın Dozunu Hesaplama</a>
        <a href="/resume" class="button">Özgeçmiş</a>
        <a href="/logout" class="button">Çıkış</a>
    </div>

    <div id="pharmacyGate" style="display:none;">
        <div id="pharmacyBox">
            <h3>Onay Kodu</h3>
            <input type="password" id="pharmacyInput" placeholder="Eczanenin onay kodunu giriniz.">
            <button id="pharmacyBtn">Giriş Yap</button>
            <p id="pharmacyError" style="color:red;"></p>
        </div>
    </div>
        
    <div class="container" id="mainContainer">
        <div id="question" class="question"></div>
        <div class="doctorImage">
            <img id="doctorImage" src="/images/doctor.png" alt="Doctor Image">
        </div>
        
        <div id="doseForm" class="dose-form hidden">
            <label for="weight">Kilo:</label>
            <input type="number" id="weight" name="weight" placeholder="kg < 10 ise 5.3 gibi kesirli sayı giriniz.">
            <button>Devam Et</button>
        </div>

        <div id="optionsContainer"></div>
        <p id="selectedOptionText"></p>
        <p id="doseResult" class="dose-result"></p>
        
        <div id="inputContainerWrapper" style="display:none;">
            <div id="inputContainer">
                <input type="temp" id="tempInput" placeholder="Örnek: Ateşim 37.2°C" />
                <button id="submitButton">Gönder</button>
            </div>
        </div>
    </div>
    
<script>
    const csrf_token = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    let user_id = <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>;
    let name = <?php echo json_encode($_SESSION['name'] ?? ''); ?>;
    let surname = <?php echo json_encode($_SESSION['surname'] ?? ''); ?>;
    let information = <?php echo json_encode($_SESSION['information'] ?? ''); ?>;
    let age = <?php echo json_encode($_SESSION['age'] ?? ''); ?>;
    let customAge = <?php echo json_encode($_SESSION['customAge'] ?? ''); ?>;
    let recentBrands = <?php echo json_encode($recentBrands); ?>;
    let rejectedActives = <?php echo json_encode($rejectedActives); ?>;
    let requiresPharmacy = <?php echo json_encode($requiresPharmacy); ?>;
</script>
<script src="/sentences.js"></script>
<script src="/script2.js"></script>
</body>
</html>