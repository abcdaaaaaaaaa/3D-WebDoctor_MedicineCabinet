<?php
    session_start();
    require 'db.php';
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
        $response = array("status" => "success", "message" => "");
    
        $postToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

        if (empty($sessionToken) || empty($postToken) || !hash_equals($sessionToken, $postToken)) {
            $response["status"] = "error";
            $response["message"] = "Güvenlik doğrulaması başarısız oldu (Geçersiz veya eksik CSRF Token).";
            echo json_encode($response);
            exit;
        }
    
        try {
            $user_id = (int) $_POST['user_id'];
            $medicine = $_POST['medicine'];
            $brand = $_POST['brand'];
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $information = $_POST['information'];
            $age = $_POST['age'];
            $weight = (double) $_POST['weight'];
            $dose = $_POST['dose'];
            $dailyAmount = $_POST['dailyAmount'];
            $discomfort = $_POST['discomfort'];
            $subdiscomfort = $_POST['subdiscomfort'];
            $recommendation = $_POST['recommendation'];
            $guidance = $_POST['guidance'];
            $urgency = $_POST['urgency'];
            $pharmacy = $_POST['pharmacy'];
            $types = $_POST['types'];

            $conclusion_rationale = isset($_POST['conclusion_rationale']) ? $_POST['conclusion_rationale'] : '';
            $result = isset($_POST['result']) ? $_POST['result'] : '';

            $stmt = $conn->prepare("INSERT INTO Ills (user_id, medicine, brand, name, surname, information, age, weight, dose, daily_amount, discomfort, sub_discomfort, recommendation, guidance, urgency, pharmacy, types, conclusion_rationale, result) 
                                     VALUES (:user_id, :medicine, :brand, :name, :surname, :information, :age, :weight, :dose, :dailyAmount, :discomfort, :subdiscomfort, :recommendation, :guidance, :urgency, :pharmacy, :types, :conclusion_rationale, :result)");
    
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':medicine', $medicine, PDO::PARAM_STR);
            $stmt->bindParam(':brand', $brand, PDO::PARAM_STR);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':surname', $surname, PDO::PARAM_STR);
            $stmt->bindParam(':information', $information, PDO::PARAM_STR);
            $stmt->bindParam(':age', $age, PDO::PARAM_STR);
            $stmt->bindParam(':weight', $weight, PDO::PARAM_STR);
            $stmt->bindParam(':dose', $dose, PDO::PARAM_STR);
            $stmt->bindParam(':dailyAmount', $dailyAmount, PDO::PARAM_STR);
            $stmt->bindParam(':discomfort', $discomfort, PDO::PARAM_STR);
            $stmt->bindParam(':subdiscomfort', $subdiscomfort, PDO::PARAM_STR);
            $stmt->bindParam(':recommendation', $recommendation, PDO::PARAM_STR);
            $stmt->bindParam(':guidance', $guidance, PDO::PARAM_STR);
            $stmt->bindParam(':urgency', $urgency, PDO::PARAM_STR);
            $stmt->bindParam(':pharmacy', $pharmacy, PDO::PARAM_STR);
            $stmt->bindParam(':types', $types, PDO::PARAM_STR);
            $stmt->bindParam(':conclusion_rationale', $conclusion_rationale, PDO::PARAM_STR);
            $stmt->bindParam(':result', $result, PDO::PARAM_STR);
    
            $stmt->execute();
            $last_id = $conn->lastInsertId();
            $response["last_id"] = $last_id;
            $stmt->closeCursor();
    
        } catch (PDOException $e) {
            $response["status"] = "error";
            $response["message"] = "Bir hata oluştu: " . $e->getMessage();
        }
    
        echo json_encode($response);
    }
?>