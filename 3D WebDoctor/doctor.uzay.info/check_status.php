<?php
session_start();
require 'dbnormal.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT result, conclusion_rationale, detail_conclusion_rationale FROM Ills WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res) {
    echo json_encode([
        'status' => 'success', 
        'result' => $res['result'],
        'conclusion_rationale' => $res['conclusion_rationale'],
        'detail_conclusion_rationale' => $res['detail_conclusion_rationale']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Kayıt bulunamadı.']);
}
?>