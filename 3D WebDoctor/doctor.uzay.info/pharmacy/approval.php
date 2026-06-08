<?php
session_start();
require 'dbnormal.php';

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['pharmacist_id'])) {
    header('Location: /pharmacist_login');
    exit;
}

if (!isset($_SESSION['locked_pharmacist_pharmacy']) && isset($_SESSION['pharmacy'])) {
    $_SESSION['locked_pharmacist_pharmacy'] = $_SESSION['pharmacy'];
}

$current_pharmacy = $_SESSION['locked_pharmacist_pharmacy'] ?? '';

if (!empty($current_pharmacy)) {
    $timeStmt = $conn->prepare("UPDATE Ills SET result = '-', conclusion_rationale = 'Talep, zaman aşımına uğradı.', detail_conclusion_rationale = '' WHERE (result = '' OR result IS NULL) AND created_at < NOW() - INTERVAL 15 MINUTE AND pharmacy = ?");
    $timeStmt->bind_param('s', $current_pharmacy);
    $timeStmt->execute();
    $timeStmt->close();
}

function getUrgencyText($urgency) {
    return ['1' => 'Düşük', '2' => 'Orta', '3' => 'Yüksek', '4' => 'Acil'][$urgency] ?? 'Derecesiz';
}

function formatIllRow($row, $includeStatus = false) {
    $type_val = mb_strtolower($row['types'] ?? '', 'UTF-8');
    $type_icon = '-';
    if ($type_val === 'eczane' || $type_val === 'alındı' || $type_val === 'alınmadı' || $type_val === 'alınamadı') {
        $type_icon = '🏥';
    } elseif ($type_val === 'ev') {
        $type_icon = '🏠';
    }

    $data = [
        "id" => $row['id'],
        "date" => $row['date_formatted'],
        "name" => $row['name'] . " " . $row['surname'],
        "age" => $row['age'],
        "weight" => $row['weight'],
        "userId" => $row['user_id'],
        "patientId" => $row['id'],
        "drugName" => $row['medicine'],
        "drugBrand" => $row['brand'] ?? '-',
        "dose" => $row['dose'],
        "dailyAmount" => $row['daily_amount'],
        "urgency" => getUrgencyText($row['urgency']),
        "type" => $type_icon,
        "mainDisease" => $row['discomfort'],
        "subDisease" => $row['sub_discomfort'],
        "specialInfo" => $row['information'] ?: ""
    ];

    if ($includeStatus) {
        $data["status"] = $row['result'];
        $data["rationale"] = trim(($row['detail_conclusion_rationale'] ?? '') . " " . ($row['conclusion_rationale'] ?? ''));
    } else {
        $data["recommendation"] = $row['recommendation'] ?: "";
        $data["guidance"] = $row['guidance'];
    }

    return $data;
}

function fetchIlls($conn, $current_pharmacy, $condition, $includeStatus = false) {
    $data = [];
    $query = "SELECT *, IFNULL(DATE_FORMAT(created_at, '%d.%m.%Y %H:%i'), DATE_FORMAT(NOW(), '%d.%m.%Y %H:%i')) as date_formatted FROM Ills WHERE $condition AND pharmacy = ? ORDER BY id DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $current_pharmacy);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) $data[] = formatIllRow($row, $includeStatus);

    $stmt->close();

    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    header('Content-Type: application/json');
    
    $token = $_GET['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        echo json_encode(["status" => "error", "message" => "Güvenlik doğrulaması başarısız."]);
        exit;
    }
    
    echo json_encode([
        "pending" => fetchIlls($conn, $current_pharmacy, "(result = '' OR result IS NULL)"),
        "past" => fetchIlls($conn, $current_pharmacy, "(result = '+' OR result = '-')", true)
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Güvenlik doğrulaması başarısız."]);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        header('Content-Type: application/json');
        $response = ["status" => "error", "message" => "İşlem gerçekleştirilemedi."];
        $record_id = (int)$_POST['id'];
        $new_result = $_POST['result'];
        $conclusion_rationale = $_POST['conclusion_rationale'] ?? '';
        $detail_conclusion_rationale = $_POST['detail_conclusion_rationale'] ?? '';

        if (mb_strlen($detail_conclusion_rationale, 'UTF-8') > 500) {
            echo json_encode(["status" => "error", "message" => "Detaylı gerekçe 500 karakterden uzun olamaz."]);
            exit;
        }

        if ($record_id > 0 && ($new_result === '+' || $new_result === '-')) {
            $stmt = $conn->prepare("UPDATE Ills SET result = ?, conclusion_rationale = ?, detail_conclusion_rationale = ? WHERE id = ? AND pharmacy = ?");
            $stmt->bind_param('sssis', $new_result, $conclusion_rationale, $detail_conclusion_rationale, $record_id, $current_pharmacy);

            if ($stmt->execute()) {
                $response["status"] = "success";
                $response["message"] = "Karar ve gerekçeler başarıyla sisteme işlendi.";
            }
            $stmt->close();
        }
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_information') {
        header('Content-Type: application/json');
        $response = ["status" => "error", "message" => "İşlem gerçekleştirilemedi."];
        $record_id = (int)$_POST['id'];
        $new_info = trim($_POST['information'] ?? '');
        $is_risky = isset($_POST['is_risky']) && ($_POST['is_risky'] === 'true' || $_POST['is_risky'] === '1');

        if (mb_strlen($new_info, 'UTF-8') > 500) {
            echo json_encode(["status" => "error", "message" => "Özel rahatsızlık bilgisi en fazla 500 karakter olabilir."]);
            exit;
        }

        if ($is_risky && empty($new_info)) {
            echo json_encode(["status" => "error", "message" => "Özel rahatsızlık bilgisi girilmeden hastanın Riskli durumuna getirilmesine izin verilmez."]);
            exit;
        }

        if ($record_id > 0) {
            $stmt = $conn->prepare("SELECT user_id FROM Ills WHERE id = ? AND pharmacy = ?");
            $stmt->bind_param('is', $record_id, $current_pharmacy);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $current_user_id = (int)$row['user_id'];
                $abs_id = abs($current_user_id);
                $new_user_id = $is_risky ? -$abs_id : $abs_id;

                $stmt1 = $conn->prepare("UPDATE users SET information = ?, user_id = ? WHERE user_id = ?");
                $stmt1->bind_param('sii', $new_info, $new_user_id, $current_user_id);
                $stmt1->execute();
                $stmt1->close();

                $stmt2 = $conn->prepare("UPDATE Ills SET user_id = ? WHERE user_id = ?");
                $stmt2->bind_param('ii', $new_user_id, $current_user_id);
                $stmt2->execute();
                $stmt2->close();

                $stmt3 = $conn->prepare("UPDATE Ills SET information = ? WHERE id = ?");
                $stmt3->bind_param('si', $new_info, $record_id);
                if ($stmt3->execute()) {
                    $response["status"] = "success";
                    $response["message"] = "Başarıyla güncellenmiştir.";
                }
                $stmt3->close();
            }
            $stmt->close();
        }
        echo json_encode($response);
        exit;
    }
}

$pendingData = fetchIlls($conn, $current_pharmacy, "(result = '' OR result IS NULL)");
$pastData = fetchIlls($conn, $current_pharmacy, "(result = '+' OR result = '-')", true);
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
    <title>Eczanenin Yönetim Paneli</title>
    <link class="shortcut icon" href="/images/doctor.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <header class="mb-8 border-b border-slate-200 pb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid text-indigo-600 fa-prescription-bottle-medical"></i>
                    <?php echo htmlspecialchars($current_pharmacy); ?> Yöntetim Paneli
                </h1>
                <p class="text-sm text-slate-900 mt-1">Merhaba, <?php echo htmlspecialchars($_SESSION['pharmacist_name'] . ' ' . $_SESSION['pharmacist_surname']); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <a href="/pharmacist_logout" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-medium px-4 py-2.5 rounded-xl text-sm transition-colors border border-rose-150">Çıkış Yap</a>
            </div>
        </header>

        <section class="mb-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 bg-amber-500 rounded-full animate-pulse"></span>
                    Eczaneye Gelen Onay Bekleyen İstekler
                </h2>
                <div class="flex items-center gap-2">
                    <button onclick="changePage('pending', -1)" id="btn-pending-prev" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <span id="page-info-pending" class="text-xs font-medium text-slate-500 min-w-[40px] text-center">1 / 1</span>
                    <button onclick="changePage('pending', 1)" id="btn-pending-next" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-4 px-6 w-32">Tarih</th>
                                <th class="py-4 px-6">Hasta Bilgileri</th>
                                <th class="py-4 px-6">Rahatsızlık Bilgisi</th>
                                <th class="py-4 px-6">İlaç Bilgisi</th>
                                <th class="py-4 px-6">Doz</th>
                                <th class="py-4 px-6">Aciliyet</th>
                                <th class="py-4 px-6 text-center">Tip</th>
                                <th class="py-4 px-6 text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="pending-table-body" class="divide-y divide-slate-100 text-sm"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
                    Eczanenin Geçmiş Onay Kayıtları
                </h2>
                <div class="flex items-center gap-2">
                    <button onclick="changePage('past', -1)" id="btn-past-prev" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <span id="page-info-past" class="text-xs font-medium text-slate-500 min-w-[40px] text-center">1 / 1</span>
                    <button onclick="changePage('past', 1)" id="btn-past-next" class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-4 px-6 w-32">Tarih</th>
                                <th class="py-4 px-6">Hasta Bilgileri</th>
                                <th class="py-4 px-6">Rahatsızlık Bilgisi</th>
                                <th class="py-4 px-6">İlaç Bilgisi</th>
                                <th class="py-4 px-6">Doz</th>
                                <th class="py-4 px-6">Aciliyet</th>
                                <th class="py-4 px-6 text-center">Tip</th>
                                <th class="py-4 px-6">Gerekçe</th>
                                <th class="py-4 px-6">Sonuç</th>
                            </tr>
                        </thead>
                        <tbody id="past-table-body" class="divide-y divide-slate-100 text-sm text-slate-600"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div id="detail-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-100 transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh] relative">
            
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 text-white flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-user-shield text-xl"></i>
                    <div>
                        <h3 class="font-bold text-base leading-tight">Hasta Bilgileri ve Karar Sihirbazı</h3>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-indigo-100 hover:text-white bg-white/10 hover:bg-white/20 p-1.5 rounded-full transition-colors">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                    <div><strong>Hasta Adı Soyadı:</strong> <span id="modal-patient-name" class="text-slate-900 font-medium"></span></div>
                    <div><strong>İlaç:</strong> <span id="modal-drug" class="text-slate-900 font-medium"></span></div>
                    <div><strong>Yaş:</strong> <span id="modal-age"></span> | <strong>Kg:</strong> <span id="modal-weight"></span></div>
                    <div><strong>Doz:</strong> <span id="modal-dose"></span> | <strong>Günlük Miktar:</strong> <span id="modal-daily-amount"></span></div>
                    
                    <div class="col-span-2 border-t border-slate-200 pt-3 mt-1"><strong>Rahatsızlık:</strong> <span id="modal-disease" class="text-slate-900 font-medium"></span></div>
                    
                    <div class="col-span-2 flex flex-col gap-1">
                        <strong>Özel Rahatsızlık Bilgisi:</strong>
                        <div class="flex items-center gap-3 bg-white p-2 border border-slate-200 rounded-xl shadow-sm w-full">
                            <textarea id="modal-special-info" maxlength="500" placeholder="Herhangi bir özel rahatsızlığı olmadığı bildirilmiştir." class="flex-1 p-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none text-slate-700 bg-white" rows="2"></textarea>
                            <div class="flex items-center gap-1.5 border-l border-slate-200 pl-3 shrink-0">
                                <span class="text-xs font-semibold text-slate-700 whitespace-nowrap">Negatif ID:</span>
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" id="modal-risk-toggle" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                                </label>
                            </div>
                            <button onclick="updateInformation()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3 rounded-xl text-xs transition-colors h-9 shrink-0">Güncelle</button>
                        </div>
                    </div>

                    <div class="col-span-2"><strong>Öneri:</strong> <span id="modal-recommendation" class="text-indigo-900"></span></div>
                    <div class="col-span-2"><strong>Rehberlik:</strong> <span id="modal-guidance" class="text-indigo-900"></span></div>
                </div>

                <hr class="border-slate-200">

                <div id="resultContainer" class="text-center py-4 space-y-4">
                    <p class="font-semibold text-slate-800 text-base">Sonuçlar Medikal İlaç Dolabına iletilsin mi?</p>
                    <div class="flex justify-center gap-4">
                        <button class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2 rounded-xl text-sm shadow-sm transition-transform active:scale-95" onclick="sendYes()">Evet</button>
                        <button class="bg-rose-600 hover:bg-rose-700 text-white font-medium px-6 py-2 rounded-xl text-sm shadow-sm transition-transform active:scale-95" onclick="sendNo()">Hayır</button>
                    </div>
                </div>

                <div id="approvalReasonContainer" class="hidden space-y-4">
                    <label class="block text-sm font-semibold text-slate-700">Kabul Gerekçesini Düzenleyin:</label>
                    <textarea id="approvalText" maxlength="500" class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" rows="3">Sonuçlar Web Doktor tarafından uygun görüldü.</textarea>
                    <div class="flex justify-end gap-2">
                        <button onclick="resetWizard()" class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50">Geri Dön</button>
                        <button onclick="sendApprovalReason()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-xl text-sm">Kabul Gerekçesini Gönder</button>
                    </div>
                </div>

                <div id="detailedRejectionQuestionContainer" class="hidden text-center py-4 space-y-4">
                    <p class="font-semibold text-slate-800 text-sm">Detaylı Red Gerekçesini doldurmak ister misiniz?</p>
                    <div class="flex justify-center gap-4">
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2 rounded-xl text-xs transition-transform active:scale-95" onclick="detailedRejectionYes()">Evet</button>
                        <button class="bg-slate-600 hover:bg-slate-700 text-white font-medium px-5 py-2 rounded-xl text-xs transition-transform active:scale-95" onclick="detailedRejectionNo()">Hayır</button>
                    </div>
                </div>

                <div id="detailedRejectionReasonContainer" class="hidden space-y-4">
                    <label class="block text-sm font-semibold text-slate-700">Detaylı Red Gerekçesini Yazın:</label>
                    <textarea id="detailedRejectionText" maxlength="500" class="w-full p-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" rows="3" placeholder="Gerekçenizi detaylandırın..."></textarea>
                    <div class="flex justify-end gap-2">
                        <button onclick="sendDetailedRejectionReason()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-xl text-sm">İlerle ve Checkbox Listesini Aç</button>
                    </div>
                </div>

                <div id="rejectionReasonContainer" class="hidden space-y-4">
                    <span class="block text-sm font-bold text-slate-700 mb-2">Red Gerekçelerini Seçiniz:</span>
                    <div class="space-y-2 text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200" id="rejectionOptions">
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReason" value="Bu ilaç hastada halen kullanıma yeterli olacak kadar bulunuyor." class="mt-0.5 rounded text-indigo-600"> <span>Bu ilaç hastada halen kullanıma yeterli olacak kadar bulunuyor.</span></label>
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReason" value="İstenilen ilaç eczanede bulunamadı." class="mt-0.5 rounded text-indigo-600"> <span>İstenilen ilaç eczanede bulunamadı.</span></label>
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReason" value="Hasta bu ilacı doktor kontrolünde tüketmesi gerekiyor." class="mt-0.5 rounded text-indigo-600"> <span>Hasta bu ilacı doktor kontrolünde tüketmesi gerekiyor.</span></label>
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReason" value="Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır." class="mt-0.5 rounded text-indigo-600"> <span>Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır.</span></label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button onclick="sendRejectionReason()" class="bg-rose-600 hover:bg-rose-700 text-white font-medium px-4 py-2 rounded-xl text-sm">Red Gerekçesini Gönder</button>
                    </div>
                </div>

                <div id="rejectionReasonContainerFilled" class="hidden space-y-4">
                    <span class="block text-sm font-bold text-slate-700 mb-2">Ek Red Gerekçelerini Seçiniz (İsteğe Bağlı):</span>
                    <div class="space-y-2 text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200" id="rejectionOptionsFilled">
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReasonFilled" value="Bu ilaç hastada halen kullanıma yeterli olacak kadar bulunuyor." class="mt-0.5 rounded text-indigo-600"> <span>Bu ilaç hastada halen kullanıma yeterli olacak kadar bulunuyor.</span></label>
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReasonFilled" value="İstenilen ilaç eczanede bulunamadı." class="mt-0.5 rounded text-indigo-600"> <span>İstenilen ilaç eczanede bulunamadı.</span></label>
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReasonFilled" value="Hasta bu ilacı doktor kontrolünde tüketmesi gerekiyor." class="mt-0.5 rounded text-indigo-600"> <span>Hasta bu ilacı doktor kontrolünde tüketmesi gerekiyor.</span></label>
                        <label class="flex items-start gap-2 cursor-pointer py-1"><input type="checkbox" name="rejectionReasonFilled" value="Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır." class="mt-0.5 rounded text-indigo-600"> <span>Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır.</span></label>
                        <label class="flex items-start gap-2 py-1 bg-amber-50 p-2 rounded border border-amber-200 text-amber-900 font-medium"><input type="checkbox" name="rejectionReasonFilled" value="Web Doktor tarafından önerilen ilaç, Detaylı Red Gerekçesinde belirttiğimiz nedenlerden dolayı kullanıma uygun görülmedi." checked disabled class="mt-0.5 rounded text-amber-600"> <span>Web Doktor tarafından önerilen ilaç, Detaylı Red Gerekçesinde belirttiğimiz nedenlerden dolayı kullanıma uygun görülmedi.</span></label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button onclick="sendRejectionReasonFilled()" class="bg-rose-600 hover:bg-rose-700 text-white font-medium px-4 py-2 rounded-xl text-sm">Red Gerekçesini Gönder</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        let pendingData = <?php echo json_encode($pendingData); ?>;
        let pastData = <?php echo json_encode($pastData); ?>;
        const csrf_token = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    </script>
    <script src="/pharmacy/savescript.js"></script>
</body>
</html>
