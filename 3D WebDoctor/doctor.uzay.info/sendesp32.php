<?php
session_start();
require 'db.php';

try {
    $conn->exec("SET NAMES 'utf8'");
    $conn->exec("SET CHARACTER SET utf8");
    $conn->exec("SET COLLATION_CONNECTION = 'utf8_general_ci'");

    $esp32_cupboard = $_GET['cupboard_code'] ?? '';
    $action = $_GET['action'] ?? 'get';

    if (empty($esp32_cupboard)) {
        echo "Hata: Dolap kodu eksik!";
        exit;
    }

    if ($action === 'pay') {
        $ills_id = $_GET['ills_id'] ?? '';
        if (!empty($ills_id)) {
            $sql_check = "SELECT p.cupboard_code FROM Ills i JOIN pharmacies p ON i.pharmacy = p.name WHERE i.id = :id";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute(['id' => $ills_id]);
            $row = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($esp32_cupboard, $row['cupboard_code'])) {
                $update_sql = "UPDATE Ills SET types = 'alındı' WHERE id = :id AND types = 'eczane'";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->execute(['id' => $ills_id]);
                echo "OK";
            } else {
                echo "Hata: Yetkisiz işlem veya geçerli kayıt yok!";
            }
        }
        exit;
    }

    $sql = "SELECT i.id AS ills_id, i.name, i.surname, i.brand, i.medicine, i.dose, i.daily_amount, i.urgency, p.medicine_order, p.cupboard_code 
            FROM Ills i 
            JOIN pharmacies p ON i.pharmacy = p.name 
            WHERE i.result = '+' AND i.types = 'eczane' AND i.created_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE) 
            ORDER BY i.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $medicinesList = ["Ibuprofen", "Paracetamol", "Gaviscon", "Zyrtec"];
    $outputArray = [];

    foreach ($results as $row) {
        if (!password_verify($esp32_cupboard, $row['cupboard_code'])) {
            continue;
        }

        $medicineOrder = str_split($row['medicine_order']);
        $orderMap = [];

        foreach ($medicineOrder as $index => $orderNumber) {
            $drugName = $medicinesList[$index];
            $orderMap[$drugName] = (int)$orderNumber;
        }

        $medicineGenericName = $row['medicine'];

        foreach ($orderMap as $medName => $num) {
            if (stripos($medicineGenericName, $medName) !== false) {
                $number = $num;
                break;
            }
        }
        
        $fields = [
            $row['name'], 
            $row['surname'], 
            $row['brand'], 
            $row['dose'], 
            $row['daily_amount'], 
            $row['urgency'], 
            $number,
            $row['ills_id']
        ];
        
        $outputArray[] = implode(',', $fields);
        break;
    }

    if (!empty($outputArray)) {
        echo implode(';', $outputArray);
    }

} catch (PDOException $e) {
    echo "Veritabanı Hatası: " . $e->getMessage();
    exit;
}
?>