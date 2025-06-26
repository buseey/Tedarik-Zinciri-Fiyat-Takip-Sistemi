<?php
session_start();
include("baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION["user_id"])) {
    die("Geçersiz erişim.");
}

$user_id = $_SESSION["user_id"];
$zincir_id = $_POST["zincir_id"] ?? null;
$issueType = $_POST["issueType"] ?? '';
$description = $_POST["description"] ?? '';
$dosya_yolu = null;

if (!$zincir_id || trim($description) === '') {
    echo json_encode([
        "success" => false,
        "message" => "Zincir ID veya açıklama boş bırakılamaz."
    ]);
    exit;
}

// Opsiyonel görsel yükleme
if (isset($_FILES["fileUpload"]) && is_uploaded_file($_FILES["fileUpload"]["tmp_name"])) {
    $ext = strtolower(pathinfo($_FILES["fileUpload"]["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (in_array($ext, $allowed)) {
        $dosya_yolu = "uploads/" . time() . "_" . basename($_FILES["fileUpload"]["name"]);
        move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $dosya_yolu);
    }
}



// Şikayet kaydı
$stmt = mysqli_prepare($baglanti, "
    INSERT INTO ihbarlar (zincir_id, user_id, aciklama, dosya_yolu, durum, kaynak, created_at)
    VALUES (?, ?, ?, ?, 'Bekliyor', 'kullanici', NOW())
");

mysqli_stmt_bind_param($stmt, "siss", $zincir_id, $user_id, $description, $dosya_yolu);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şikayet Kaydedildi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 5 saniyede e-Devlet sayfasına yönlendir -->
    <meta http-equiv="refresh" content="5;url=https://www.turkiye.gov.tr/ticaret-bakanligi-haksiz-fiyat-artisi-sikayet-bildirimi">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            text-align: center;
            padding: 50px;
        }
        .card {
            background: white;
            display: inline-block;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #4CAF50;
        }
        p {
            font-size: 16px;
            color: #444;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>✅ Şikayetiniz kaydedildi.</h2>
        <p>Resmi olarak Ticaret Bakanlığı'na şikayet bildiriminde bulunmak için e-Devlet sistemine yönlendiriliyorsunuz.</p>
    </div>
</body>
</html>
