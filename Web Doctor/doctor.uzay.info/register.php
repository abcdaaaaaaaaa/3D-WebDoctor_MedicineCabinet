<?php
session_start();
require 'db.php';

$conn->exec("SET NAMES 'utf8'");
$conn->exec("SET CHARACTER SET utf8");
$conn->exec("SET COLLATION_CONNECTION = 'utf8_general_ci'");

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $birthdate = $_POST['birthdate'];
    $password = password_hash($_POST['TCKNO'], PASSWORD_DEFAULT);
    $information = $_POST['information'];
    $has_problem = !empty($information);

    if ($has_problem) { $stmt = $conn->query("SELECT COALESCE(MIN(user_id), 0) - 1 AS user_id FROM users WHERE user_id < 0"); } 
    else { $stmt = $conn->query("SELECT COALESCE(MAX(user_id), 0) + 1 AS user_id FROM users WHERE user_id > 0"); }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $row['user_id'];

    $stmt = $conn->prepare("INSERT INTO users (user_id, name, surname, date_of_birth, password, information) VALUES (:user_id, :name, :surname, :birthdate, :password, :information)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':surname', $surname);
    $stmt->bindParam(':birthdate', $birthdate);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':information', $information);

    if ($stmt->execute()) {
        header('Location: login.php');
        exit();
    } else {
        $error_message = "Kayıt sırasında bir hata oluştu.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="shortcut icon" href="../images/doctor.ico">
    <link rel="stylesheet" href="loginstyle.css">
    <style>.form-container { max-width: 450px; }</style>
</head>
<body>
    <div class="form-container">
        <h2>Kayıt Ol</h2>
        <form method="POST" action="" id="registerForm">
            <label for="name">Ad:</label>
            <input type="text" id="name" name="name" required><br>
            
            <label for="surname">Soyad:</label>
            <input type="text" id="surname" name="surname" required><br>
            
            <label for="TCKNO">TC Kimlik Numarası:</label>
            <input type="text" id="TCKNO" name="TCKNO" required><br>
            
            <label for="birthdate">Doğum Tarihi:</label>
            <input type="date" id="birthdate" name="birthdate" required><br>
            
            <label for="information">Özel bir ilaç kullanıyormusunuz veya ciddi sağlık probleminiz var mı? Kullanıyorsanız/Varsa doktorunuzdan yazınız (255 karaktere kadar).</label>
            <textarea id="information" name="information" maxlength="255"></textarea><br>

            <div class="checkbox-group">
                <label><input type="checkbox" id="ibuprofen" name="condition[]" value="Ibuprofen'e karşı özel bir rahatsızlığı var.">
                    Ibuprofen'e karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="paracetamol" name="condition[]" value="Paracetemol'e karşı özel bir rahatsızlığı var.">
                    Paracetemol'e karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="gaviscon" name="condition[]" value="Gaviscon'a karşı özel bir rahatsızlığı var.">
                    Gaviscon'a karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="cetirizine" name="condition[]" value="Cetirizine'e karşı özel bir rahatsızlığı var.">
                    Cetirizine'e karşı özel bir rahatsızlığım var.</label><br>
                <label><input type="checkbox" id="allergic" name="condition[]" value="Bazı ilaçlara karşı aşırı alerijik reaksiyon göstermektedir.">
                    Bazı ilaçlara karşı aşırı alerijik reaksiyon gösteriyorum.</label><br>
            </div>
            
            <button type="submit">Kayıt Ol</button>
        </form>
        <?php if ($error_message) echo "<p style='color: red;'>$error_message</p>"; ?>
        <p>Hesabınız zaten kayıtlı mı? <a href="/login">Giriş Yap</a></p>
        <p>Eczane kaydı için buraya <a href="/cupboard">tıklaynız</a></p>
    </div>
    <script src="register.js"></script>
</body>
</html>
