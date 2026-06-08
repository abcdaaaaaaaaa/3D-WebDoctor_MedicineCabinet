<?php
session_start();
require 'dbnormal.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$negative_user_id = -abs($user_id);
$positive_user_id = abs($user_id);

$stmt = $conn->prepare("SELECT name, surname, information, date_of_birth FROM users WHERE user_id IN (?, ?) LIMIT 1");
$stmt->bind_param("ii", $positive_user_id, $negative_user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT id, information, medicine, brand, age, weight, dose, daily_amount, discomfort, sub_discomfort, recommendation, guidance, urgency, pharmacy, types, conclusion_rationale, detail_conclusion_rationale, result, created_at FROM Ills WHERE user_id IN (?, ?) ORDER BY created_at DESC");
$stmt->bind_param("ii", $positive_user_id, $negative_user_id);
$stmt->execute();
$result = $stmt->get_result();
$illness_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Özgeçmişi</title>
    <link rel="shortcut icon" href="images/doctor.ico">
    <link rel="stylesheet" href="resumestyle.css">
</head>
<body>

<div class="header">
    <a href="/" class="button">Eczaneden Yeni İlaç Alma</a>
    <a href="/medicine" class="button">Mevcut İlacın Dozunu Hesaplama</a>
    <a href="/resume" class="button">Özgeçmiş</a>
    <a href="/logout" class="button">Çıkış</a>
</div>

<h2>Kullanıcı Bilgileri</h2>

<table class="header-table">
    <tr><td class="text-nowrap"><strong>Kullanıcı ID</strong></td><td class="text-nowrap"><?= htmlspecialchars($user_id) ?></td></tr>
    <tr><td class="text-nowrap"><strong>Ad</strong></td><td class="text-nowrap"><?= htmlspecialchars($user_info['name']) ?></td></tr>
    <tr><td class="text-nowrap"><strong>Soyad</strong></td><td class="text-nowrap"><?= htmlspecialchars($user_info['surname']) ?></td></tr>
    <tr><td class="text-nowrap"><strong>Güncel Özel Rahatsızlık Bilgisi</strong></td><td class="text-nowrap"><?= trim($user_info['information'] ?? '') === "" ? "Herhangi bir özel rahatsızlığı olmadığı bildirilmiştir." : htmlspecialchars($user_info['information']) ?></td></tr>
    <tr><td class="text-nowrap"><strong>Doğum Tarihi</strong></td><td class="text-nowrap"><?= htmlspecialchars($user_info['date_of_birth']) ?></td></tr>
</table>

<h2>Geçmiş Kayıtları</h2>

<?php if (empty($illness_records)): ?>
    <div class="no-records">Kayıt bulunamadı.</div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th class="text-nowrap">Hasta ID</th>
                <th >Tarih</th>
                <th class="text-nowrap">Özel Rahatsızlık Bilgisi</th>
                <th class="text-nowrap">İlaç Bilgisi</th>
                <th>Yaş</th>
                <th class="text-nowrap">Ağırlık (Kg)</th>
                <th class="text-nowrap">Doz Bilgisi</th>
                <th>Rahatsızlık</th>
                <th class="text-nowrap">Alt Rahatsızlık</th>
                <th>Öneri</th>
                <th>Rehberlik</th>
                <th>Aciliyet</th>
                <th>Eczane</th>
                <th>Tip</th>
                <th>Gerekçe</th>
                <th>Sonuç</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($illness_records as $record): 
                $status = ($record['result'] === '+') ? '✅' : (($record['result'] === '-') ? '❌' : '⏳');
                $combined_rationale = trim(($record['detail_conclusion_rationale'] ?? '') . " " . ($record['conclusion_rationale'] ?? ''));

                $urgency = $record['urgency'] ?? '';
                if ($urgency == '3' || $urgency == '4') {
                    $urgency_text = $urgency == '4' ? 'Acil' : 'Yüksek';
                    $urgency_class = 'urgency-high';
                } elseif ($urgency == '2') {
                    $urgency_text = 'Orta';
                    $urgency_class = 'urgency-medium';
                } elseif ($urgency == '1') {
                    $urgency_text = 'Düşük';
                    $urgency_class = 'urgency-low';
                } else {
                    $urgency_text = 'Derecesiz';
                    $urgency_class = 'urgency-none';
                }

                $brand_display = !empty($record['brand']) ? " <span class='brand-text'>(" . htmlspecialchars($record['brand']) . ")</span>" : "";

                $type = mb_strtolower(trim($record['types'] ?? ''));
                $type_icon = '';

                if ($type === 'eczane' || $type === 'alındı' ||  $type === 'alınmadı' || $type === 'alınamadı') {$type_icon = '🏥';} elseif ($type === 'ev') {$type_icon = '🏠';}
            ?>
                <tr>
                    <td class="cell-id"><?= htmlspecialchars($record['id']) ?></td>
                    <td class="text-nowrap"><?= htmlspecialchars($record['created_at']) ?></td>
                    <td><?= trim($record['information'] ?? '') === "" ? "Herhangi bir özel rahatsızlığı olmadığı bildirilmiştir." : htmlspecialchars($record['information']) ?></td>
                    <td class="cell-medicine"><?= htmlspecialchars($record['medicine']) . $brand_display ?></td>
                    <td class="text-nowrap"><?= htmlspecialchars($record['age']) ?></td>
                    <td><?= htmlspecialchars($record['weight']) ?></td>
                    <td class="text-nowrap">Günde <?= htmlspecialchars($record['daily_amount']) ?> kez <?= htmlspecialchars($record['dose']) ?></td>
                    <td class="cell-discomfort"><?= htmlspecialchars($record['discomfort']) ?></td>
                    <td class="cell-discomfort"><?= htmlspecialchars($record['sub_discomfort']) ?></td>
                    <td class="text-nowrap"><?= htmlspecialchars($record['recommendation']) ?></td>
                    <td><?= htmlspecialchars($record['guidance']) ?></td>
                    <td><span class="urgency-badge <?= $urgency_class ?>"><?= $urgency_text ?></span></td>
                    <td><?= htmlspecialchars($record['pharmacy'] ?? '') ?></td>
                    <td class="text-center"><?= $type_icon ?></td>
                    <td><?= htmlspecialchars($combined_rationale) ?></td>
                    <td class="text-center"><?= $status ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
